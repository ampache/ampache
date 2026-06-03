import fs from 'node:fs';

const requiredFiles = [
    'public/lib/components/jquery/jquery.min.js',
    'public/lib/components/bootstrap/js/bootstrap.bundle.min.js',
    'public/lib/components/bootstrap/css/bootstrap.min.css',
    'public/lib/components/filepond/filepond.min.js',
    'public/lib/components/wunderbaum/wunderbaum.umd.js'
];

const missing = requiredFiles.filter((file) => !fs.existsSync(file));

if (missing.length > 0) {
    console.error('Required files missing:');
    missing.forEach((file) => console.error(`  ${file}`));
    process.exit(1);
}
