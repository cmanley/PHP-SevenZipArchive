<?php
require_once(__DIR__ . '/../SevenZipArchive.php');

$file = __DIR__ . '/this_must_not_exist.7z';

$archive = new SevenZipArchive($file, [
	'debug'  => true,
	'unlink' => true,
]);

if (!$archive->addFromString('text.txt', 'Hello')) {
	throw new \Exception("Failed to addFromString() to $file");
}

error_log("All the files:\n");
foreach ($archive as $entry) {
	error_log(print_r($entry, true));
}

error_log('SevenZipArchive object still exists, file exists should be 1: ' . intval(file_exists($file)));
unset($archive);
error_log('SevenZipArchive object destroyed   , file exists should be 0: ' . intval(file_exists($file)));
