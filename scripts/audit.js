const fs = require('fs')
const path = require('path')
const { spawnSync } = require('child_process')
const yaml = require('js-yaml')
const reachableUrl = require('reachable-url')
const { isReachable } = reachableUrl

const CONCURRENCY = 10
const ENABLED_DIR = process.env.OEMBED_ENABLED_DIR || path.join(__dirname, '..', 'providers')
const DISABLED_DIR = process.env.OEMBED_DISABLED_DIR || path.join(__dirname, '..', 'providers-disabled')
const PROVIDERS_DIR = ENABLED_DIR
const TEST_PHP = process.env.OEMBED_TEST_PHP || path.join(__dirname, '..', 'test.php')

const OPTS = {
  timeout: { request: 15_000 },
  headers: { 'user-agent': 'Slackbot 1.0 (+https://api.slack.com/robots)' }
}

async function checkUrl (url) {
  try {
    const res = await reachableUrl(url, OPTS)
    return { statusCode: res.statusCode, reachable: isReachable(res) }
  } catch {
    return { statusCode: null, reachable: false }
  }
}

function extractContentUrl (exampleUrl) {
  try {
    const parsed = new URL(exampleUrl)
    const encoded = parsed.searchParams.get('url')
    if (!encoded) return null
    return decodeURIComponent(encoded)
  } catch {
    return null
  }
}

async function checkEndpoint (endpoint) {
  const endpointCheck = await checkUrl(endpoint.url)
  if (endpointCheck.reachable) {
    return { url: endpoint.url, verdict: 'ok', endpointCheck, via: 'endpoint' }
  }

  const examples = endpoint.example_urls || []
  let hasExamples = false

  for (const exampleUrl of examples) {
    const contentUrl = extractContentUrl(exampleUrl)
    if (!contentUrl) continue

    hasExamples = true
    const contentCheck = await checkUrl(contentUrl)
    if (!contentCheck.reachable) continue

    const exampleCheck = await checkUrl(exampleUrl)
    if (exampleCheck.reachable) {
      return {
        url: endpoint.url,
        verdict: 'ok',
        endpointCheck,
        via: 'example',
        contentUrl,
        contentCheck,
        exampleUrl,
        exampleCheck
      }
    }

    return {
      url: endpoint.url,
      verdict: 'fail',
      endpointCheck,
      via: 'example',
      contentUrl,
      contentCheck,
      exampleUrl,
      exampleCheck
    }
  }

  if (!hasExamples) {
    return { url: endpoint.url, verdict: 'inconclusive', endpointCheck, via: 'none' }
  }

  return { url: endpoint.url, verdict: 'fail', endpointCheck, via: 'all_dead' }
}

async function auditProvider (file, baseDir = PROVIDERS_DIR) {
  const content = fs.readFileSync(path.join(baseDir, file), 'utf8')
  const entries = yaml.load(content)
  const results = []

  for (const entry of entries) {
    const endpointResults = []

    for (const endpoint of entry.endpoints) {
      endpointResults.push(await checkEndpoint(endpoint))
    }

    let verdict
    if (endpointResults.some(e => e.verdict === 'ok')) {
      verdict = 'OK'
    } else if (endpointResults.some(e => e.verdict === 'fail')) {
      verdict = 'FAIL'
    } else {
      verdict = 'INCONCLUSIVE'
    }

    results.push({
      file,
      baseDir,
      name: entry.provider_name,
      providerUrl: entry.provider_url,
      endpoints: endpointResults,
      verdict
    })

    const indicator = { OK: '.', FAIL: 'X', INCONCLUSIVE: '?' }[verdict]
    process.stderr.write(indicator)
  }

  return results
}

async function runWithConcurrency (tasks, concurrency) {
  const results = []
  let index = 0

  async function worker () {
    while (index < tasks.length) {
      const i = index++
      results[i] = await tasks[i]()
    }
  }

  await Promise.all(Array.from({ length: concurrency }, worker))
  return results.flat()
}

function getFilesToAudit () {
  const args = process.argv.slice(2)

  if (args.length > 0) {
    return args
      .map(f => path.basename(f))
      .filter(f => f.endsWith('.yml'))
      .filter(f => fs.existsSync(path.join(PROVIDERS_DIR, f)))
  }

  return fs.readdirSync(PROVIDERS_DIR).filter(f => f.endsWith('.yml'))
}

function markdownTable (results) {
  const fail = results.filter(r => r.verdict === 'FAIL')
  const inconclusive = results.filter(r => r.verdict === 'INCONCLUSIVE')
  const ok = results.filter(r => r.verdict === 'OK')
  const lines = []

  if (fail.length > 0) {
    lines.push(`### Failed (${fail.length})`)
    lines.push('')
    lines.push('oEmbed endpoint is confirmed broken.')
    lines.push('')
    lines.push('| Provider | Endpoint | Status | Reason |')
    lines.push('| --- | --- | --- | --- |')
    for (const r of fail) {
      for (const ep of r.endpoints.filter(e => e.verdict === 'fail')) {
        const reason = ep.via === 'all_dead'
          ? 'endpoint, content, and oEmbed URLs all unreachable'
          : `content ${ep.contentCheck.statusCode} → oEmbed ${ep.exampleCheck.statusCode}`
        lines.push(`| ${r.name} | ${ep.url} | ${ep.endpointCheck.statusCode} | ${reason} |`)
      }
    }
  }

  if (inconclusive.length > 0) {
    lines.push('')
    lines.push(`### Inconclusive (${inconclusive.length})`)
    lines.push('')
    lines.push('Could not verify — example content is stale, missing, or blocked.')
    lines.push('')
    lines.push('| Provider | Endpoint | Status | Reason |')
    lines.push('| --- | --- | --- | --- |')
    for (const r of inconclusive) {
      for (const ep of r.endpoints) {
        const reason = !ep.endpointCheck.reachable && ep.via === 'none' ? 'no verifiable examples' : ep.via
        lines.push(`| ${r.name} | ${ep.url} | ${ep.endpointCheck.statusCode} | ${reason} |`)
      }
    }
  }

  lines.push('')
  lines.push(`### Summary`)
  lines.push('')
  lines.push(`| Status | Count |`)
  lines.push(`| --- | --- |`)
  lines.push(`| OK | ${ok.length} |`)
  lines.push(`| Failed | ${fail.length} |`)
  lines.push(`| Inconclusive | ${inconclusive.length} |`)
  lines.push(`| **Total** | **${results.length}** |`)

  return lines.join('\n')
}

function auditDir (baseDir) {
  const files = fs.readdirSync(baseDir).filter(f => f.endsWith('.yml'))
  return files.map(file => () => auditProvider(file, baseDir))
}

// A file may hold several provider entries; collapse them to one verdict.
// FAIL wins (any broken endpoint), otherwise OK only when everything is OK,
// otherwise INCONCLUSIVE.
function fileVerdicts (results) {
  const byFile = new Map()
  for (const r of results) {
    if (!byFile.has(r.file)) byFile.set(r.file, [])
    byFile.get(r.file).push(r.verdict)
  }
  const out = new Map()
  for (const [file, verdicts] of byFile) {
    let verdict
    if (verdicts.includes('FAIL')) verdict = 'FAIL'
    else if (verdicts.every(v => v === 'OK')) verdict = 'OK'
    else verdict = 'INCONCLUSIVE'
    out.set(file, verdict)
  }
  return out
}

// Reachability only proves the endpoint answers — the build gate (test.php)
// still enforces the provider schema. A file can be reachable yet invalid (e.g.
// a wildcard in the endpoint url), so re-enabling it on reachability alone would
// break the build. Run the same gate over the enabled dir and revert any file
// this sync just enabled that fails it. test.php exits non-zero on the first
// offending file and names it, so loop until the gate passes. Returns the files
// reverted back to disabled.
function revertRecoveredFailures (enabledFiles) {
  const reverted = []

  while (enabledFiles.size > 0) {
    const res = spawnSync('php', [TEST_PHP, ENABLED_DIR], { encoding: 'utf8' })

    if (res.error) {
      throw new Error(`Could not run schema gate (${TEST_PHP}): ${res.error.message}`)
    }
    if (res.status === 0) break

    const out = `${res.stdout || ''}${res.stderr || ''}`
    const match = out.match(/\/([^/\s]+\.yml)\b/)
    if (!match) {
      throw new Error(`Schema gate failed but named no provider file:\n${out}`)
    }

    const file = match[1]
    if (!enabledFiles.has(file)) {
      // A pre-existing enabled provider is failing the gate — not ours to
      // revert. Surface it instead of silently touching another file.
      throw new Error(`Schema gate failed on ${file}, which this sync did not enable:\n${out}`)
    }

    fs.renameSync(path.join(ENABLED_DIR, file), path.join(DISABLED_DIR, file))
    enabledFiles.delete(file)
    reverted.push(file)
  }

  return reverted
}

// Audit both directories and move files across the enabled/disabled line:
// confirmed-broken providers get disabled, recovered ones get re-enabled.
// INCONCLUSIVE never moves. Returns the list of moves performed.
async function sync () {
  console.error('Auditing enabled providers...\n')
  const enabled = await runWithConcurrency(auditDir(ENABLED_DIR), CONCURRENCY)
  console.error('\n\nAuditing disabled providers...\n')
  const disabled = await runWithConcurrency(auditDir(DISABLED_DIR), CONCURRENCY)
  console.error('\n')

  const moves = []

  for (const [file, verdict] of fileVerdicts(enabled)) {
    if (verdict === 'FAIL') {
      fs.renameSync(path.join(ENABLED_DIR, file), path.join(DISABLED_DIR, file))
      moves.push({ file, action: 'disabled' })
    }
  }

  for (const [file, verdict] of fileVerdicts(disabled)) {
    if (verdict === 'OK') {
      fs.renameSync(path.join(DISABLED_DIR, file), path.join(ENABLED_DIR, file))
      moves.push({ file, action: 'enabled' })
    }
  }

  // Gate recoveries on the build's schema validator, reverting any that fail.
  const enabledNow = new Set(moves.filter(m => m.action === 'enabled').map(m => m.file))
  const reverted = new Set(revertRecoveredFailures(enabledNow))
  const applied = moves.filter(m => !(m.action === 'enabled' && reverted.has(m.file)))

  // Report against the full set so AUDIT.md documents everything we checked.
  console.log(markdownTable([...enabled, ...disabled]))

  if (reverted.size > 0) {
    console.error(`\nKept ${reverted.size} reachable provider(s) disabled — they fail the schema gate:`)
    for (const file of reverted) console.error(`  ✗ rejected  ${file}`)
  }

  if (applied.length === 0) {
    console.error('\nNo provider moves required.')
  } else {
    console.error(`\nMoved ${applied.length} provider file(s):`)
    for (const m of applied) console.error(`  ${m.action === 'disabled' ? '→ disabled' : '← enabled '} ${m.file}`)
  }

  return applied
}

async function main () {
  const args = process.argv.slice(2)

  if (args.includes('--sync')) {
    await sync()
    return
  }

  const files = getFilesToAudit()

  if (files.length === 0) {
    console.log('No provider files to audit.')
    process.exit(0)
  }

  console.error(`Auditing ${files.length} provider file(s)...\n`)

  const tasks = files.map(file => () => auditProvider(file))
  const results = await runWithConcurrency(tasks, CONCURRENCY)

  console.error('\n')

  console.log(markdownTable(results))

  const fail = results.filter(r => r.verdict === 'FAIL')

  if (fail.length > 0) {
    process.exit(1)
  }
}

if (require.main === module) {
  main().catch(err => {
    console.error(err)
    process.exit(1)
  })
}

module.exports = { revertRecoveredFailures, ENABLED_DIR, DISABLED_DIR, TEST_PHP }
