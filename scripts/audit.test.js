'use strict'

// Gate test for the audit sync's schema check. It exercises the real test.php
// validator against throwaway provider dirs, so it stays deterministic, local,
// and free (no network — the reachability audit is not touched here).

const test = require('node:test')
const assert = require('node:assert')
const fs = require('fs')
const os = require('os')
const path = require('path')
const { spawnSync } = require('child_process')

const enabledDir = fs.mkdtempSync(path.join(os.tmpdir(), 'oembed-enabled-'))
const disabledDir = fs.mkdtempSync(path.join(os.tmpdir(), 'oembed-disabled-'))

// audit.js reads these at require time.
process.env.OEMBED_ENABLED_DIR = enabledDir
process.env.OEMBED_DISABLED_DIR = disabledDir

const { revertRecoveredFailures, checkEndpoint } = require('./audit.js')

// The gate shells out to `php test.php`; skip cleanly where php/yaml is absent
// so a dev box without the extension does not report a false failure. CI (build
// and audit workflows) sets up php+yaml, so the gate is always covered there.
const phpReady = (() => {
  const res = spawnSync('php', ['-r', 'exit(function_exists("yaml_parse_file") ? 0 : 1);'])
  return !res.error && res.status === 0
})()

const VALID = [
  '- provider_name: Good',
  '  provider_url: https://good.example',
  '  endpoints:',
  '    - schemes:',
  '        - https://good.example/*',
  '      url: https://good.example/oembed'
].join('\n') + '\n'

// Reachable in the wild, but the endpoint url carries a wildcard — exactly the
// slateapp case that broke the build in PR #901.
const WILDCARD_URL = [
  '- provider_name: Bad',
  '  provider_url: https://bad.example',
  '  endpoints:',
  '    - schemes:',
  '        - https://*.bad.example/work/*',
  '      url: https://*.bad.example/oembed'
].join('\n') + '\n'

function reset () {
  for (const dir of [enabledDir, disabledDir]) {
    for (const f of fs.readdirSync(dir)) fs.rmSync(path.join(dir, f))
  }
}

function writeEnabled (name, body) {
  fs.writeFileSync(path.join(enabledDir, name), body)
}

test('reverts a recovered provider that fails the schema gate', { skip: !phpReady && 'php with yaml extension not available' }, () => {
  reset()
  writeEnabled('good.yml', VALID)
  writeEnabled('bad.yml', WILDCARD_URL)

  const reverted = revertRecoveredFailures(new Set(['good.yml', 'bad.yml']))

  assert.deepStrictEqual(reverted, ['bad.yml'])
  assert.ok(fs.existsSync(path.join(disabledDir, 'bad.yml')), 'invalid file moved back to disabled')
  assert.ok(!fs.existsSync(path.join(enabledDir, 'bad.yml')), 'invalid file no longer enabled')
  assert.ok(fs.existsSync(path.join(enabledDir, 'good.yml')), 'valid file stays enabled')
})

test('leaves valid recoveries untouched', { skip: !phpReady && 'php with yaml extension not available' }, () => {
  reset()
  writeEnabled('good.yml', VALID)

  const reverted = revertRecoveredFailures(new Set(['good.yml']))

  assert.deepStrictEqual(reverted, [])
  assert.ok(fs.existsSync(path.join(enabledDir, 'good.yml')))
})

test('throws when the failing file is not one it enabled', { skip: !phpReady && 'php with yaml extension not available' }, () => {
  reset()
  writeEnabled('good.yml', VALID)
  writeEnabled('preexisting.yml', WILDCARD_URL) // invalid, but not in the recovery set

  assert.throws(
    () => revertRecoveredFailures(new Set(['good.yml'])),
    /did not enable/
  )
  // Must not touch a file outside its own recovery set.
  assert.ok(fs.existsSync(path.join(enabledDir, 'preexisting.yml')))
})

// --- Endpoint liveness classification (no network: global.fetch is stubbed) ---
//
// An oEmbed endpoint is an API that legitimately answers 4xx. These tests lock
// in the rule that decides enable/disable: any HTTP response => ok (live), a
// DNS/connection error => fail (gone), anything else (TLS, timeout) =>
// inconclusive (never disabled). Regression guard for iamcal/oembed#904, where
// live providers like Vimeo were disabled because the old audit read a 4xx (and
// a soft-404 on stale example content) as "endpoint broken".

const realFetch = global.fetch

function stubFetchStatus (status) {
  global.fetch = async () => ({ status })
}

function stubFetchThrow (code) {
  global.fetch = async () => {
    const err = new Error(`stub network error ${code}`)
    err.cause = { code }
    throw err
  }
}

test.afterEach(() => { global.fetch = realFetch })

for (const status of [200, 301, 400, 401, 403, 404]) {
  test(`endpoint answering HTTP ${status} is ok (alive)`, async () => {
    stubFetchStatus(status)
    const res = await checkEndpoint({ url: 'https://vimeo.com/api/oembed.{format}' })
    assert.strictEqual(res.verdict, 'ok')
    assert.strictEqual(res.statusCode, status)
  })
}

for (const code of ['ENOTFOUND', 'EAI_AGAIN', 'ECONNREFUSED']) {
  test(`endpoint with ${code} is fail (gone)`, async () => {
    stubFetchThrow(code)
    const res = await checkEndpoint({ url: 'https://gone.example/oembed' })
    assert.strictEqual(res.verdict, 'fail')
    assert.strictEqual(res.code, code)
  })
}

for (const code of ['CERT_HAS_EXPIRED', 'ERR_TLS_CERT_ALTNAME_INVALID', 'UND_ERR_CONNECT_TIMEOUT']) {
  test(`endpoint with ${code} is inconclusive (never disabled)`, async () => {
    stubFetchThrow(code)
    const res = await checkEndpoint({ url: 'https://weird.example/oembed' })
    assert.strictEqual(res.verdict, 'inconclusive')
  })
}

test('a transient throw is retried once before giving up', async () => {
  let calls = 0
  global.fetch = async () => {
    calls++
    if (calls === 1) {
      const err = new Error('transient')
      err.cause = { code: 'ECONNRESET' }
      throw err
    }
    return { status: 200 }
  }
  const res = await checkEndpoint({ url: 'https://flaky.example/oembed' })
  assert.strictEqual(res.verdict, 'ok')
  assert.strictEqual(calls, 2)
})

test.after(() => {
  fs.rmSync(enabledDir, { recursive: true, force: true })
  fs.rmSync(disabledDir, { recursive: true, force: true })
})
