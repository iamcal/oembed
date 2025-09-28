const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');
const fetch = require('node-fetch');
const { XMLParser } = require('fast-xml-parser');

const BOT_USER_AGENT = 'oEmbed Validator Bot/1.0 (https://github.com/iamcal/oembed)';
// Avoid false positives by making sure second time that the request has failed.
const SPOOFED_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.106 Safari/537.36';
const xmlParser = new XMLParser();

async function validateUrl(url) {
    let response;
    try {
        response = await fetch(url, { timeout: 15000, headers: { 'User-Agent': BOT_USER_AGENT } });

        if (response.status === 403) {
            console.error(`  - INFO: Received 403. Retrying with spoofed user agent for ${url}`);
            response = await fetch(url, { timeout: 15000, headers: { 'User-Agent': SPOOFED_USER_AGENT } });
        }

        if (!response.ok) {
            const errorType = response.status === 403 ? 'FORBIDDEN' : (response.status === 404 ? 'NOT_FOUND' : 'OTHER_ERROR');
            return { isValid: false, message: `HTTP Error: ${response.status} ${response.statusText}`, type: errorType };
        }

        const body = await response.text();
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json') || url.includes('format=json')) {
            JSON.parse(body);
        } else if (contentType.includes('text/xml') || contentType.includes('application/xml') || url.includes('format=xml')) {
            if (!xmlParser.parse(body)) throw new Error('Invalid XML format');
        } else {
            try { JSON.parse(body); } catch (e) {
                if (!xmlParser.parse(body)) throw new Error('Response is not valid JSON or XML');
            }
        }
        return { isValid: true, message: 'OK', type: 'SUCCESS' };
    } catch (error) {
        const isTimeout = error.type === 'request-timeout';
        const isDnsError = error.code === 'ENOTFOUND';
        const errorType = (isTimeout || isDnsError) ? 'NO_RESPONSE' : 'OTHER_ERROR';
        return { isValid: false, message: `Error: ${error.message}`, type: errorType };
    }
}

async function main() {
    const providersDir = 'providers';
    const disabledDir = 'providers-disabled';
    const failedProviders = [];
    const disabledProviders = [];
    const cliArgs = process.argv.slice(2);
    let totalUrlsTested = 0;

    if (!fs.existsSync(providersDir)) {
        console.error(`Error: Directory '${providersDir}' not found.`);
        process.exit(1);
    }

    let allProviderFiles = fs.readdirSync(providersDir).filter(f => f.endsWith('.yml')).sort();
    let filesToTest = [];

    if (cliArgs.length > 0) {
        const firstArgNum = parseInt(cliArgs[0], 10);
        if (cliArgs.length === 1 && !isNaN(firstArgNum)) {
            console.error(`Testing the first ${firstArgNum} provider file(s) as requested.`);
            filesToTest = allProviderFiles.slice(0, firstArgNum);
        } else {
            console.error(`Testing specific provider file(s): ${cliArgs.join(', ')}`);
            filesToTest = cliArgs;
        }
    } else {
        console.error('Testing all provider files.');
        filesToTest = allProviderFiles;
    }

    for (const filename of filesToTest) {
        const filepath = path.join(providersDir, filename);
        if (!fs.existsSync(filepath)) {
            console.error(`  - SKIP: ${filename} (not found)`);
            continue;
        }
        console.error(`Processing ${filename}...`);

        try {
            const content = fs.readFileSync(filepath, 'utf8');
            const providers = yaml.load(content);
            if (!providers) continue;

            let providerResults = [];
            let allUrlsFailed = true;

            for (const provider of providers) {
                const providerName = provider.provider_name || 'Unknown Provider';
                for (const endpoint of provider.endpoints || []) {
                    for (const exampleUrl of endpoint.example_urls || []) {
                        totalUrlsTested++;
                        const result = await validateUrl(exampleUrl);
                        providerResults.push(result);
                        if (result.isValid) {
                            allUrlsFailed = false;
                            console.error(`  - PASS: ${exampleUrl}`);
                        } else {
                            failedProviders.push({ name: providerName, url: exampleUrl, reason: result.message });
                            console.error(`  - FAIL: ${exampleUrl} (${result.message})`);
                        }
                    }
                }
            }

            const shouldDisable = allUrlsFailed && providerResults.every(r => r.type === 'NO_RESPONSE' || r.type === 'FORBIDDEN');
            if (shouldDisable && providerResults.length > 0) {
                const reason = providerResults[0].type === 'NO_RESPONSE' ? 'Endpoint or domain is unreachable (DNS or timeout error).' : 'Endpoint is blocking requests (403 Forbidden).';
                console.error(`  - ACTION: Disabling ${filename}. Reason: ${reason}`);

                const providersData = yaml.load(content);
                const humanReadableDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

                providersData.forEach(provider => {
                    provider.endpoints.forEach(endpoint => {
                        if (!endpoint.notes) {
                            endpoint.notes = [];
                        }
                        endpoint.notes.push(`Disabled on ${humanReadableDate}: ${reason}`);
                    });
                });
                
                const newContent = yaml.dump(providersData);

                fs.writeFileSync(path.join(disabledDir, filename), newContent);
                fs.unlinkSync(filepath);
                disabledProviders.push({ name: filename, reason });
            }

        } catch (e) {
            const errorMessage = `YAML Parsing Error: ${e.message}`;
            failedProviders.push({ name: filename, url: 'N/A', reason: errorMessage });
            console.error(`  - ${errorMessage}`);
        }
    }

    const passedCount = totalUrlsTested - failedProviders.length;
    let summary;

    if (failedProviders.length > 0 || disabledProviders.length > 0) {
        summary = `## oEmbed Provider Validation Report

- ✅ **Passed:** ${passedCount}
- ❌ **Failed:** ${failedProviders.length}
- 🤖 **Disabled:** ${disabledProviders.length}
- Total URLs Tested: ${totalUrlsTested}
`;

        if (disabledProviders.length > 0) {
            summary += `\n### 🤖 Automatically Disabled Providers\n\n`;
            summary += `| File Name | Reason for Disabling |\n`;
            summary += `|-----------|----------------------|\n`;
            disabledProviders.forEach(({ name, reason }) => {
                summary += `| \`${name}\` | ${reason} |\n`;
            });
        }

        if (failedProviders.length > 0) {
            const failureRows = failedProviders.map(({ name, url, reason }) => {
                const safeUrl = url.replace(/\|/g, '\\|');
                const safeReason = reason.replace(/\|/g, '\\|');
                return `| ${name} | \`${safeUrl}\` | ${safeReason} |`;
            }).join('\n');

            summary += `\n### 🚨 Failed URLs (Manual Review Required)\n\n`;
            summary += `| Provider Name | Failing URL | Reason for Failure |\n`;
            summary += `|---------------|-------------|--------------------|\n`;
            summary += `${failureRows}\n`;
        }
    } else {
        summary = `## oEmbed Provider Validation Report

- ✅ **Passed:** ${passedCount}
- ❌ **Failed:** 0
- 🤖 **Disabled:** 0
- Total URLs Tested: ${totalUrlsTested}

---

### 🎉 All providers passed!
`;
    }
    
    console.log(summary);

    if (failedProviders.length > 0) {
        console.error('\n--- Validation Summary: Failures Detected ---');
        process.exit(1);
    } else {
        console.error('\n--- Validation Summary: All providers passed! ---');
        process.exit(0);
    }
}

main();