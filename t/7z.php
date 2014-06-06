<?php
/**
* Test script for SevenZipArchive class.
*
* @author   Craig Manley
* @version  $Id: 7z.php,v 1.1 2014/06/06 23:18:04 cmanley Exp $
* @package  cmanley
*/
require_once(__DIR__ . '/../SevenZipArchive.php');

$archive = new SevenZipArchive('test.7z', array('debug' => true));

print "Meta data:\n";
print_r($archive->metadata());

// Show number of contained files:
print $archive->count() . " file(s) in archive\n";

// Show info about the first contained file:
$entry = $archive->get(0);
print 'First file name: ' . $entry['Name'] . "\n";

// Iterate over all the contained files in archive
print "All the files:\n";
foreach ($archive as $entry) {
	print_r($entry);
}

// Extract a single contained file by name:
$archive->extractTo('.', 'test.txt');

// Extract all contained files:
$archive->extractTo('./');
