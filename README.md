SevenZipArchive
===============

PHP 7zip archive class.
Currently it's a front end to the 7zr CLI executable. It's been tested on Linux only.
It can list and extract from existing archives, as well as add files using string content since that's the only functionality I need so far.

Synopsis:
---------
```
// Open an existing archive.
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
$archive->extractTo('.', 'test.txt');	// emulates http://www.php.net/manual/en/ziparchive.extractto.php

// Extract all contained files:
$archive->extractTo('.');
```

```
// Open an existing or create a new archive
$archive = new SevenZipArchive('new.7z');

// Add some files using string content (emulates same named method in ZipArchive: http://www.php.net/manual/en/ziparchive.addfromstring.php)
$archive->addFromString('Hello.txt', 'Hello, hello, turn your radio on.');
$archive->addFromString('The €U/sucks/file.txt', "This is the contents.\n");
```

Most useful public methods:
---------------------------
```
public function addFromString($localname, $contents): bool
public function extractTo($destination, $names = null): bool
public function entries(): array
