const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');
const { Octokit } = require('@octokit/rest');

async function syncLabels() {
    const octokit = new Octokit({ auth: process.env.GITHUB_TOKEN });
    const [owner, repo] = process.env.GITHUB_REPOSITORY.split('/');
    const labelsFilePath = path.join('.github', 'labels.yml');

    console.log(`Syncing labels for repository: ${owner}/${repo}`);

    // Read the desired labels from the YAML file
    const desiredLabels = yaml.load(fs.readFileSync(labelsFilePath, 'utf8'));
    if (!desiredLabels) {
        console.log('labels.yml is empty. No labels to sync.');
        return;
    }

    // Get existing labels from the repository
    const { data: existingLabels } = await octokit.issues.listLabelsForRepo({ owner, repo });
    const existingLabelsMap = new Map(existingLabels.map(label => [label.name, label]));

    console.log(`Found ${existingLabels.length} existing labels.`);
    console.log(`Found ${desiredLabels.length} desired labels in config.`);

    for (const desiredLabel of desiredLabels) {
        const { name, color, description } = desiredLabel;
        const existingLabel = existingLabelsMap.get(name);

        if (!existingLabel) {
            // Label does not exist, so create it
            console.log(`- Creating label: "${name}"`);
            await octokit.issues.createLabel({
                owner,
                repo,
                name,
                color,
                description,
            });
        } else {
            // Label exists, check if it needs an update
            if (existingLabel.color !== color || existingLabel.description !== description) {
                console.log(`- Updating label: "${name}"`);
                await octokit.issues.updateLabel({
                    owner,
                    repo,
                    name,
                    color,
                    description,
                });
            } else {
                console.log(`- Label "${name}" is already up to date.`);
            }
        }
    }

    console.log('\nLabel sync complete!');
}

syncLabels().catch(error => {
    console.error('Error during label sync:', error);
    process.exit(1);
});