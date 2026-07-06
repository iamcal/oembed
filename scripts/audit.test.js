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

const { revertRecoveredFailures } = require('./audit.js')

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

test.after(() => {
  fs.rmSync(enabledDir, { recursive: true, force: true })
  fs.rmSync(disabledDir, { recursive: true, force: true })
})
