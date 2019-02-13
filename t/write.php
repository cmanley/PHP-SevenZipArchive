<?php
require_once(__DIR__ . '/../SevenZipArchive.php');

$archive = new SevenZipArchive('new.7z', [
	#'debug' => true
]);
# Add a file from contents.
$add_map = array(
	'The €U/sucks/file.txt' => "This is the contents.\n",
	'/hello/world.txt' => "Hello?\n",
	'hello.txt' => "hello\n",
	'Hello.txt' => "Hello\n",
);
#$archive->setDebug(false);
foreach ($add_map as $localname => $contents) {
	print "addFromString: $localname ";
	$result = $archive->addFromString($localname, $contents);
	print $result ? "OK\n" : "FAIL\n";
}

// Iterate over all the contained files in archive
print "All the files:\n";
foreach ($archive as $entry) {
	print_r($entry);
}
