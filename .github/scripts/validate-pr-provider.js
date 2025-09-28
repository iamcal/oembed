const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');
const fetch = require('node-fetch');
const { XMLParser } = require('fast-xml-parser');

const USER_AGENT = 'oEmbed Validator Bot/1.0 (https://github.com/iamcal/oembed)';
const xmlParser = new XMLParser();

// --- Validation Logic ---

async function fetchUrl(url, isJson = false) {
    const headers = { 'User-Agent': USER_AGENT };
    if (isJson) headers['Accept'] = 'application/json';

    const response = await fetch(url, { timeout: 15000, headers });
    if (!response.ok) {
        throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
    }
    return response;
}

function validateOembedResponse(data, type) {
    const requiredKeys = {
        'photo': ['url', 'width', 'height'],
        'video': ['html', 'width', 'height'],
        'rich': ['html', 'width', 'height'],
        'link': [],
    };
    if (!data.version || data.version !== '1.0') return `Missing or invalid "version" property. Must be "1.0".`;
    if (!data.type || !requiredKeys.hasOwnProperty(data.type)) return `Missing or invalid "type" property.`;
    
    for (const key of requiredKeys[data.type]) {
        if (!data[key]) return `Missing required property "${key}" for type "${data.type}".`;
    }
    return null;
}

async function validateProviderFile(filepath) {
    const results = { passes: [], fails: [] };
    const content = fs.readFileSync(filepath, 'utf8');
    const providers = yaml.load(content);

    for (const provider of providers) {
        const providerName = provider.provider_name || 'Unnamed Provider';
        
        if (!provider.provider_name) results.fails.push(`Missing \`provider_name\`.`);
        if (!provider.provider_url) results.fails.push(`Missing \`provider_url\`.`);
        if (!provider.endpoints || provider.endpoints.length === 0) {
            results.fails.push(`Missing or empty \`endpoints\` array.`);
            continue;
        }

        for (const endpoint of provider.endpoints) {
            if (!endpoint.schemes || endpoint.schemes.length === 0) results.fails.push(`Endpoint for ${providerName} is missing \`schemes\`.`);
            if (!endpoint.url) results.fails.push(`Endpoint for ${providerName} is missing an oEmbed \`url\`.`);
            if (!endpoint.example_urls || endpoint.example_urls.length === 0) {
                results.fails.push(`Endpoint for ${providerName} is missing \`example_urls\`.`);
                continue;
            }
            results.passes.push(`YAML structure for **${providerName}** is valid.`);

            for (const exampleUrl of endpoint.example_urls) {
                try {
                    const response = await fetchUrl(exampleUrl);
                    const body = await response.text();
                    let data;
                    
                    if (body.trim().startsWith('<')) {
                        data = xmlParser.parse(body).oembed;
                    } else {
                        data = JSON.parse(body);
                    }

                    const validationError = validateOembedResponse(data);
                    if (validationError) {
                        results.fails.push(`oEmbed response for \`${exampleUrl}\` is invalid: ${validationError}`);
                    } else {
                        results.passes.push(`oEmbed response for \`${exampleUrl}\` is valid.`);
                    }
                } catch (e) {
                    results.fails.push(`Failed to fetch or parse \`${exampleUrl}\`: ${e.message}`);
                }
            }

            if (endpoint.discovery) {
                try {
                    const schemeUrl = endpoint.schemes[0].replace('*', '');
                    const response = await fetchUrl(schemeUrl);
                    const html = await response.text();
                    const hasOembedLink = /<link[^>]+(type="application\/json\+oembed"|type="text\/xml\+oembed")[^>]+href/i.test(html);
                    if (hasOembedLink) {
                        results.passes.push(`oEmbed discovery link found on **${schemeUrl}**.`);
                    } else {
                        results.fails.push(`oEmbed discovery link **not found** on **${schemeUrl}**.`);
                    }
                } catch (e) {
                    results.fails.push(`Failed to check discovery on \`${endpoint.schemes[0]}\`: ${e.message}`);
                }
            }
        }
    }
    return results;
}

async function main() {
    const filesToTest = process.argv.slice(2);
    let allResults = { passes: [], fails: [] };
    let fileCount = 0;

    for (const file of filesToTest) {
        if (fs.existsSync(file)) {
            fileCount++;
            const result = await validateProviderFile(file);
            allResults.passes.push(...result.passes.map(msg => `**${path.basename(file)}**: ${msg}`));
            allResults.fails.push(...result.fails.map(msg => `**${path.basename(file)}**: ${msg}`));
        }
    }

    let markdown = `## Pull Request Provider Validation\n\n`;
    markdown += `Tested **${fileCount}** provider file(s) in this pull request.\n\n`;

    if (allResults.fails.length > 0) {
        markdown += `### ❌ ${allResults.fails.length} Check(s) Failed\n\n`;
        allResults.fails.forEach(fail => {
            markdown += `- ${fail}\n`;
        });
        markdown += '\n---\n\n';
    }

    if (allResults.passes.length > 0) {
        markdown += `### ✅ ${allResults.passes.length} Check(s) Passed\n\n`;
        allResults.passes.forEach(pass => {
            markdown += `- ${pass}\n`;
        });
    }

    if (allResults.fails.length > 0) {
        markdown += `\n**Please address the failed checks before this pull request can be merged.**`;
    } else {
        markdown += `\n**All checks passed! Thank you for your contribution.**`;
    }

    console.log(markdown);

    if (allResults.fails.length > 0) {
        process.exit(1);
    }
}

main();