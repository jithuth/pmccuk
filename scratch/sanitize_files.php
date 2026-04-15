<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/../app');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        
        // Remove BOM if exists
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        
        // Trim leading whitespace
        $content = ltrim($content);
        
        // Ensure namespace is on the same line as <?php
        $content = preg_replace('/<\?php\s+namespace/s', '<?php namespace', $content);
        
        file_put_contents($path, $content);
        echo "Sanitized: $path\n";
    }
}
