#!/usr/bin/env node
/**
 * Create a GitHub release with the plugin ZIP
 * 
 * Prerequisites:
 * 1. Install GitHub CLI: brew install gh
 * 2. Authenticate: gh auth login
 * 
 * Usage:
 *   node scripts/github-release.js              # Creates release for current version
 *   node scripts/github-release.js --draft      # Creates draft release
 *   node scripts/github-release.js --prerelease # Creates prerelease
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const rootDir = path.join(__dirname, '..');
const packageJson = JSON.parse(fs.readFileSync(path.join(rootDir, 'package.json'), 'utf8'));
const version = packageJson.version;
const tag = `v${version}`;
const zipPath = path.join(rootDir, 'releases', `popups-nekuda-v${version}.zip`);

// Parse arguments
const args = process.argv.slice(2);
const isDraft = args.includes('--draft');
const isPrerelease = args.includes('--prerelease');

// Check if ZIP exists
if (!fs.existsSync(zipPath)) {
    console.error(`❌ Release ZIP not found: ${zipPath}`);
    console.error('   Run "npm run release" first to create the ZIP file.');
    process.exit(1);
}

// Check if gh CLI is available
try {
    execSync('gh --version', { stdio: 'ignore' });
} catch (e) {
    console.error('❌ GitHub CLI (gh) not found.');
    console.error('   Install with: brew install gh');
    console.error('   Then authenticate: gh auth login');
    process.exit(1);
}

// Build release command
let cmd = `gh release create ${tag} "${zipPath}"`;
cmd += ` --title "Popups Nekuda ${tag}"`;
cmd += ` --notes "Release ${tag}"`;

if (isDraft) {
    cmd += ' --draft';
}
if (isPrerelease) {
    cmd += ' --prerelease';
}

console.log(`\n📦 Creating GitHub release ${tag}...`);
console.log(`   ZIP: ${path.basename(zipPath)}`);
if (isDraft) console.log('   Mode: Draft');
if (isPrerelease) console.log('   Mode: Prerelease');

try {
    execSync(cmd, { cwd: rootDir, stdio: 'inherit' });
    console.log(`\n✅ Release ${tag} created successfully!`);
    console.log(`   View at: https://github.com/$(gh repo view --json nameWithOwner -q .nameWithOwner)/releases/tag/${tag}`);
} catch (error) {
    console.error('\n❌ Failed to create release');
    process.exit(1);
}

