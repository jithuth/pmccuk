<?php
$src = "d:/uk project/pmcc-laravel/storage/app/public/uploads/69beb14d8c430.jpg/";
$dest = "d:/uk project/pmcc-laravel/storage/app/public/";

if (!is_dir($src)) {
    die("Source directory not found: $src");
}

$files = scandir($src);
foreach ($files as $file) {
    if ($file == "." || $file == "..") continue;
    rename($src . $file, $dest . $file);
    echo "Moved: $file\n";
}
echo "Migration complete!";
