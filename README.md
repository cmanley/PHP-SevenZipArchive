SevenZipArchive
===============

PHP 7zip archive class.
Currently it's a front end to the 7zr CLI executable. It's been tested on Linux only.
It can list and extract from existing archives, add directory contents, or add individual files using string content.

### Example of extracting from an existing archive
```php
# Open an existing archive.
$archive = new SevenZipArchive('docs.7z');

# Print the archive file name passed to the constructor:
print $archive->filename . "\n";	# prints docs.7z

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
$archive = new SevenZipArchive('new.7z', [
	'unlink' => true,	# automatically delete docs.7z when this object is destroyed
]);

# Adds the contents of the given directory to the archive.
# This sometimes offers significantly better compression results than adding files individually.
$archive->addDir('/path/to/add/');

# Add some files using string content.
# Not recommended as addDir() results in better compression and performance.
# This emulates same named method in ZipArchive: http://www.php.net/manual/en/ziparchive.addfromstring.php)
$archive->addFromString('Hello.txt', 'Hello, hello, turn your radio on.');
$archive->addFromString('/path/file.txt', "This is the contents.\n");
```

### Trick using tempnam() to create a temporary archive file name
If you create a unique temporary file name using tempnam() to pass as archive name to SevenZipArchive, then all methods will fail.
This is because the underlying 7z or 7zr binary expects given archive names to be valid 7z files and not empty files.
To work around this you can either create a unique directory and work in that, or use tempnam() and then write some data into it first to make it a valid but empty 7z archive.
```php
$tmp7z_file = tempnam(sys_get_temp_dir(), 'test_7z_');	# it won't have a .7z extension, but that's ok
file_put_contents($tmp7z_file, base64_decode('N3q8ryccAASNm9UPAAAAAAAAAAAAAAAAAAAAAAAAAAA='));	# the binary data of a valid but empty 7z file
$archive = new \SevenZipArchive($tmp7z_file, [
	'debug' => true,
	'unlink' => true,	# causes $tmp7z_file to deleted when the object is destroyed
]);
# ... add files to it
# ... then do something with $archive->filename before the object is destroyed and the temporary file is removed.
```

### Most useful public methods and properties
```php
public readonly string $filename;
public function addDir(string $realdir): bool
public function addFromString(string $localname, string $contents): bool
public function extractTo(string $destination, array|string $names = null): bool
public function entries(): array
public function test(): bool
