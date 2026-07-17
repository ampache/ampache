import fs from 'node:fs';

const requiredFiles = [
    'public/client/lib/components/jquery/jquery.min.js',
    'public/client/lib/components/bootstrap/js/bootstrap.bundle.min.js',
    'public/client/lib/components/bootstrap/css/bootstrap.min.css',
    'public/client/lib/components/filepond/filepond.min.js',
    'public/client/lib/components/wunderbaum/wunderbaum.umd.js',
    'public/client/lib/components/material-symbols/folder.svg'
];

const missing = requiredFiles.filter((file) => !fs.existsSync(file));

if (missing.length > 0) {
    console.error('Required files missing:');
    missing.forEach((file) => console.error(`  ${file}`));
    process.exit(1);
}
