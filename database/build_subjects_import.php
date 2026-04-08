<?php

$src = 'C:\\Users\\Jessie S Mahinay\\Downloads\\subjects.sql';
$out = __DIR__ . '/subjects_import.sql';

$c = file_get_contents($src);
if (!preg_match('/INSERT INTO `subjects`[^;]+;/s', $c, $m)) {
    fwrite(STDERR, "Could not find INSERT in source file.\n");
    exit(1);
}
$block = $m[0];
$block = preg_replace('/INSERT INTO `subjects` \(`id`, /', 'INSERT IGNORE INTO `subjects` (', $block);
$block = preg_replace('/^\(\d+, /m', '(', $block);
$block = str_replace("', 1, '", "', @dept_ccis, '", $block);

$header = <<<'SQL'
SET NAMES utf8mb4;
SET @dept_ccis = (SELECT id FROM departments WHERE code = 'CCIS' LIMIT 1);

SQL;

file_put_contents($out, $header . $block);
echo "Wrote " . strlen(file_get_contents($out)) . " bytes to {$out}\n";
