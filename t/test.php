<?php
require_once(__DIR__ . '/../SevenZipArchive.php');

$tests = array(	# file => expect
	'test.7z'	=> true,
	__FILE__	=> false,
);

foreach ($tests as $file => $expect) {
	$archive = new SevenZipArchive($file, array(
		#'debug' => true,
	));
	$actual = $archive->test($file);
	print "test('$file') returns " . var_export($actual, true) . ' ';
	if ($actual == $expect) {
		print "as expected. OK\n";
	}
	else {
		print "FAIL\n";
	}
	#print "\n";
}
