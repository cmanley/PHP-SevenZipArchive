SevenZipArchive
===============

PHP 7zip archive class.
Currently it's a front end to the 7zr CLI executable. It's been tested on Linux only.
It can list and extract from existing archives, add directory contents, or add individual files using string content.

### Example of extracting from an existing archive
```php
# Open an existing archive.
$archive = new SevenZipArchive('docs.7z');

# Test the integrity of the archive:
print 'Archive is ' . ($archive->test() ? 'OK' : 'broken') . "\n";

# Show number of contained files:
print $archive->count() . " file(s) in archive\n";

# Show info about the first contained file:
$entry = $archive->get(0);
print 'First file name: ' . $entry['Name'] . "\n";

# Iterate over all the contained files in archive, and dump all their info:
foreach ($archive as $entry) {
	print_r($entry);
}

# Extract a single contained file by name into the current directory:
$archive->extractTo('.', 'test.txt');	# emulates http://www.php.net/manual/en/ziparchive.extractto.php

# Extract all contained files:
$archive->extractTo('.');
```

### Example of adding files to a new/existing archive
```php
# Open an existing or create a new archive
$archive = new SevenZipArchive('new.7z');

# Add some files using string content (emulates same named method in ZipArchive: http://www.php.net/manual/en/ziparchive.addfromstring.php)
$archive->addFromString('Hello.txt', 'Hello, hello, turn your radio on.');
$archive->addFromString('The €U/sucks/file.txt', "This is the contents.\n");

# Adds the contents of the given directory to the archive.
# This sometimes offers significantly better compression results than adding files individually.
$archive->addDir('/path/to/add/');
```

### Most useful public methods
```php
public function addDir($realdir): bool
public function addFromString($localname, $contents): bool
public function extractTo($destination, $names = null): bool
public function entries(): array
public function test(): bool
