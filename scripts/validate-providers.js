const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');
const fetch = require('node-fetch');
const { XMLParser } = require('fast-xml-parser');

const USER_AGENT = 'oEmbed Validator Bot/1.0 (https://github.com/iamcal/oembed)';
const xmlParser = new XMLParser();

async function validateUrl(url) {
    try {
        const response = await fetch(url, {
            timeout: 15000,
            headers: { 'User-Agent': USER_AGENT }
        });

        if (!response.ok) {
            return { isValid: false, message: `HTTP Error: ${response.status} ${response.statusText}` };
        }

        const contentType = response.headers.get('content-type') || '';
        const body = await response.text();

        if (contentType.includes('application/json') || url.includes('format=json')) {
            JSON.parse(body);
        } else if (contentType.includes('text/xml') || contentType.includes('application/xml') || url.includes('format=xml')) {
            if (!xmlParser.parse(body)) {
                 throw new Error('Invalid XML format');
            }
        } else {
             try {
                JSON.parse(body);
            } catch (e) {
                if (!xmlParser.parse(body)) {
                    throw new Error('Response is not valid JSON or XML');
                }
            }
        }
        return { isValid: true, message: 'OK' };
    } catch (error) {
        return { isValid: false, message: `Error: ${error.message}` };
    }
}

async function main() {
    const providersDir = 'providers';
    const failedProviders = [];
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
            console.log(`Testing the first ${firstArgNum} provider file(s) as requested.`);
            filesToTest = allProviderFiles.slice(0, firstArgNum);
        } else {
            console.log(`Testing specific provider file(s): ${cliArgs.join(', ')}`);
            filesToTest = cliArgs;
        }
    } else {
        console.log('Testing all provider files.');
        filesToTest = allProviderFiles;
    }

    for (const filename of filesToTest) {
        const filepath = path.join(providersDir, filename);
        if (!fs.existsSync(filepath)) {
            console.log(`  - SKIP: ${filename} (not found)`);
            continue;
        }
        console.log(`Processing ${filename}...`);

        try {
            const providers = yaml.load(fs.readFileSync(filepath, 'utf8'));
            if (!providers) continue;

            for (const provider of providers) {
                const providerName = provider.provider_name || 'Unknown Provider';
                for (const endpoint of provider.endpoints || []) {
                    for (const exampleUrl of endpoint.example_urls || []) {
                        totalUrlsTested++;
                        const { isValid, message } = await validateUrl(exampleUrl);
                        if (!isValid) {
                            failedProviders.push({ name: providerName, url: exampleUrl, reason: message });
                            console.log(`  - FAIL: ${exampleUrl} (${message})`);
                        } else {
                            console.log(`  - PASS: ${exampleUrl}`);
                        }
                    }
                }
            }
        } catch (e) {
            const errorMessage = `YAML Parsing Error: ${e.message}`;
            failedProviders.push({ name: filename, url: 'N/A', reason: errorMessage });
            console.log(`  - ${errorMessage}`);
        }
    }

    const passedCount = totalUrlsTested - failedProviders.length;
    let summary;

    if (failedProviders.length > 0) {
        const failureRows = failedProviders.map(({ name, url, reason }) => {
            const safeUrl = url.replace(/\|/g, '\\|');
            const safeReason = reason.replace(/\|/g, '\\|');
            return `| ${name} | \`${safeUrl}\` | ${safeReason} |`;
        }).join('\n');

        summary = `## oEmbed Provider Validation Report

- ✅ **Passed:** ${passedCount}
- ❌ **Failed:** ${failedProviders.length}
- Total URLs Tested: ${totalUrlsTested}

---

### 🚨 Failed Providers

| Provider Name | Failing URL | Reason for Failure |
|---------------|-------------|--------------------|
${failureRows}
`;
    } else {
        summary = `## oEmbed Provider Validation Report

- ✅ **Passed:** ${passedCount}
- ❌ **Failed:** 0
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
        console.log('\n--- Validation Summary: All providers passed! ---');
        process.exit(0);
    }
}

main();