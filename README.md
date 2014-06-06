SevenZipArchive
===============

PHP 7zip archive class.
Currently it's a front end to the 7zr CLI executable. It's been tested on Linux only.
Currently it can only list and extract from existing archives, since that's the only functionality I need.

Synopsis:
---------
```
// Open an archive.
$archive = new SevenZipArchive('docs.7z');

// Show number of contained files:
print $archive->count() . " file(s) in archive\n";

// Show info about the first contained file:
$entry = $archive->get(0);
print 'First file name: ' . $entry['Name'] . "\n";

// Iterate over all the contained files in archive, and dump all their info:
foreach ($archive as $entry) {
	print_r($entry);
}

// Extract a single contained file by name into the current directory:
$archive->extractTo('.', 'test.txt');

// Extract all contained files:
$archive->extractTo('.');
```
