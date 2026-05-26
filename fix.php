<?php
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('views'));
foreach($iterator as $file) {
    if($file->isFile() && $file->getExtension() == 'php') {
        $c = file_get_contents($file->getPathname());
        $f = str_replace(
            ['Ã³', 'Ã­', 'Ã¡', 'Ã©', 'Ãº', 'Ã±', 'Ã‘', 'Ã ', 'Ã‰', 'Ã“', 'Ãš'],
            ['ó', 'í', 'á', 'é', 'ú', 'ñ', 'Ñ', 'Á', 'É', 'Ó', 'Ú'],
            $c
        );
        if($c !== $f) {
            file_put_contents($file->getPathname(), $f);
            echo 'Fixed ' . $file->getPathname() . PHP_EOL;
        }
    }
}
?>
