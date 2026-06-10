const fs = require('fs')
const path = require('path')
const yaml = require('js-yaml')
const reachableUrl = require('reachable-url')
const { isReachable } = reachableUrl

const CONCURRENCY = 10
const PROVIDERS_DIR = path.join(__dirname, '..', 'providers')

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

async function auditProvider (file) {
  const content = fs.readFileSync(path.join(PROVIDERS_DIR, file), 'utf8')
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

async function main () {
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

main().catch(err => {
  console.error(err)
  process.exit(1)
})
