#!/usr/bin/env node
/**
 * Sync version from package.json to popup.php
 * 
 * This script reads the version from package.json and updates popup.php
 * to ensure both files have the same version number.
 */

const fs = require('fs');
const path = require('path');

const rootDir = path.join(__dirname, '..');
const packageJsonPath = path.join(rootDir, 'package.json');
const pluginFilePath = path.join(rootDir, 'popup.php');

// Read package.json
const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
const version = packageJson.version;

// Read popup.php
let pluginContent = fs.readFileSync(pluginFilePath, 'utf8');

// Update Version in plugin header
pluginContent = pluginContent.replace(
    /(\* Version:\s*).*/,
    `$1${version}`
);

// Update POPUPS_NEKUDA_VERSION constant
pluginContent = pluginContent.replace(
    /(define\('POPUPS_NEKUDA_VERSION',\s*').*(';)/,
    `$1${version}$2`
);

// Write back
fs.writeFileSync(pluginFilePath, pluginContent);

console.log(`✓ Synced version ${version} to popup.php`);

