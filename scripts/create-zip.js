#!/usr/bin/env node
/**
 * Create a production-ready ZIP file for WordPress plugin distribution
 * 
 * The ZIP file will be created in the releases/ directory with the version
 * number in the filename.
 */

const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const rootDir = path.join(__dirname, '..');
const packageJson = JSON.parse(fs.readFileSync(path.join(rootDir, 'package.json'), 'utf8'));
const version = packageJson.version;
const pluginSlug = 'popups-nekuda';

// Create releases directory if it doesn't exist
const releasesDir = path.join(rootDir, 'releases');
if (!fs.existsSync(releasesDir)) {
    fs.mkdirSync(releasesDir);
}

const zipPath = path.join(releasesDir, `${pluginSlug}-v${version}.zip`);

// Files and directories to include
const include = [
    'popup.php',
    'assets/',
    'includes/',
    'templates/',
    'vendor/plugin-update-checker/',
    'readme.txt',
    'LICENSE',
];

// Files and patterns to exclude
const exclude = [
    '*.map',
    '.DS_Store',
    'Thumbs.db',
];

// Create the archive
const output = fs.createWriteStream(zipPath);
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', () => {
    const sizeKB = (archive.pointer() / 1024).toFixed(2);
    console.log(`✓ Created ${zipPath}`);
    console.log(`  Size: ${sizeKB} KB`);
});

archive.on('error', (err) => {
    throw err;
});

archive.pipe(output);

// Add files to archive under plugin directory name
include.forEach((item) => {
    const itemPath = path.join(rootDir, item);
    
    if (!fs.existsSync(itemPath)) {
        console.log(`  ⚠ Skipping ${item} (not found)`);
        return;
    }
    
    const stats = fs.statSync(itemPath);
    
    if (stats.isDirectory()) {
        archive.directory(itemPath, `${pluginSlug}/${item}`, (data) => {
            // Exclude unwanted files
            for (const pattern of exclude) {
                if (pattern.startsWith('*.')) {
                    const ext = pattern.slice(1);
                    if (data.name.endsWith(ext)) {
                        return false;
                    }
                } else if (data.name === pattern) {
                    return false;
                }
            }
            return data;
        });
    } else {
        archive.file(itemPath, { name: `${pluginSlug}/${item}` });
    }
});

archive.finalize();

