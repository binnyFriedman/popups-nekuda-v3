#!/usr/bin/env node
/**
 * Bump version in package.json and sync to popup.php
 * 
 * Usage:
 *   node scripts/bump-version.js          # patch bump (3.0.0 -> 3.0.1)
 *   node scripts/bump-version.js minor    # minor bump (3.0.0 -> 3.1.0)
 *   node scripts/bump-version.js major    # major bump (3.0.0 -> 4.0.0)
 *   node scripts/bump-version.js 3.2.1    # set specific version
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const rootDir = path.join(__dirname, '..');
const packageJsonPath = path.join(rootDir, 'package.json');

// Read package.json
const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
const currentVersion = packageJson.version;

const arg = process.argv[2] || 'patch';

function bumpVersion(version, type) {
    const parts = version.split('.').map(Number);
    
    switch (type) {
        case 'major':
            return `${parts[0] + 1}.0.0`;
        case 'minor':
            return `${parts[0]}.${parts[1] + 1}.0`;
        case 'patch':
        default:
            return `${parts[0]}.${parts[1]}.${parts[2] + 1}`;
    }
}

// Determine new version
let newVersion;
if (/^\d+\.\d+\.\d+$/.test(arg)) {
    // Specific version provided
    newVersion = arg;
} else {
    // Bump type provided
    newVersion = bumpVersion(currentVersion, arg);
}

// Update package.json
packageJson.version = newVersion;
fs.writeFileSync(packageJsonPath, JSON.stringify(packageJson, null, 2) + '\n');

console.log(`✓ Bumped version: ${currentVersion} → ${newVersion}`);

// Sync to popup.php
try {
    execSync('node scripts/sync-version.js', { cwd: rootDir, stdio: 'inherit' });
} catch (error) {
    console.error('Failed to sync version:', error.message);
    process.exit(1);
}

console.log(`\n📦 Ready for release v${newVersion}`);

