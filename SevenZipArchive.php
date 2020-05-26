<?php
/**
* Contains the SevenZipArchive and SevenZipArchiveException classes.
*
* Dependencies:
* <pre>
* PHP 5.3 or higher
* </pre>
*
* @author    Craig Manley
* @copyright Copyright © 2014, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
* @version   1.8
* @package   cmanley
*/


# TODO: support compression levels and method options
# TODO: support -scc{UTF-8|WIN|DOS} : set charset for console input/output; however not all versions of 7zr support it: 9.20 (on Debian 7 and 8) doesn't, 16.02 (on Debian 9) does.
# TODO: support -scs option which doesn't seem to work with 7zr 9.20; this option does not exist in version 9.04 (Debian 6)
# TODO: use namespaces, proper unit tests, a composer file, and make it suitable for packgist.
# TODO: for simplification, perhaps drop Iterator interface support or use a getIterator() method to return a separate iterable object.
# TODO: gather motivation and time to do all the above.


/**
* Custom Exception class.
*
* @package  cmanley
*/
class SevenZipArchiveException extends Exception {}



/**
* 7-Zip archive class.
* Front end to 7za or 7zr executable.
*
* Example(s):
* <pre>
*	$archive = new SevenZipArchive('docs.7z');
*
*	# Test integrity of archive:
*	print 'Archive is ' . ($archive->test() ? 'OK' : 'broken') . "\n";
*
*	# Show number of contained files:
*	print $archive->count() . " file(s) in archive\n";
*
*	# Show info about the first contained file:
*	$entry = $archive->get(0);
*	print 'First file name: ' . $entry['Name'] . "\n";
*
*	# Iterate over all the contained files in archive, and dump all their info:
*	foreach ($archive as $entry) {
*		print_r($entry);
*	}
*
*	# Extract a single contained file by name into the current directory:
*	$archive->extractTo('.', 'test.txt');
*
*	# Extract all contained files:
*	$archive->extractTo('.');
* </pre>
*
* Which binary:
*	For Windows there are 2 possible binaries to download from http://www.7-zip.org/download.html
*		If you install the CLI version, then the binary is called 7za.
*		If you install the GUI version, then the binary is called 7z and it is installed in the path "c:\Program Files\7-Zip" by default.
*	For other OS's, the minimal CLI version is called 7zr, and the full version is called 7za.
*
* @package  cmanley
*/
class SevenZipArchive implements Countable, Iterator {

	protected $file = null; # archive file
	protected $key = -1; # iterator key
	protected $entries = null; # Array of associative arrays
	protected $meta = null; # Associative array of meta data from last list command.

	# Options:
	protected $debug = false;
	protected $internal_encoding = null;
	protected $binary = null;

	/**
	* Constructor.
	*
	* @param string $file - The file name of the ZIP archive to open.
	* @param array $option optional associative array of any of these options:
	*	- debug: boolean, if true, then debug messages are emitted using error_log()
	*	- binary: default is "7za" for Windows, else "7zr"
	*	- internal_encoding: default is mb_internal_encoding()
	* @throws SevenZipArchiveException
	* @throws \InvalidArgumentException
	*/
	function __construct($file, array $options = null) {
		if (!is_string($file)) {
			throw new \InvalidArgumentException(gettype($file) . ' is not a legal file argument type');
		}
		if (!strlen($file)) {
			throw new \InvalidArgumentException('Missing file argument');
		}
		#if (!file_exists($file)) {
		#	throw new \InvalidArgumentException("File '$file' not found"); # TODO: support create mode
		#}
		$this->file = $file;
		if (!is_array($options)) {
			$options = array();
		}

		# Get the options.
		if ($options) {
			foreach ($options as $key => $value) {
				if (in_array($key, array('debug'))) {
					if (!(is_null($value) || is_bool($value) || is_int($value))) {
						throw new \InvalidArgumentException("The '$key' option must be a boolean");
					}
					$this->$key = $value;
				}
				elseif (in_array($key, array('binary', 'internal_encoding'))) {
					if (!(is_string($value) && strlen($value))) {
						throw new \InvalidArgumentException("The '$key' option must be a non-empty string");
					}
					$this->$key = $value;
				}
				else {
					throw new \InvalidArgumentException("Unknown option '$key'");
				}
			}
		}
		if (!$this->internal_encoding) {
			$this->internal_encoding = mb_internal_encoding();
		}
		if (is_null($this->binary)) {
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$this->binary = '7za';
			}
			else {
				$this->binary = '7zr'; # minimal version of 7za
			}
		}
		$this->debug && error_log(__METHOD__ . ' Archive file: ' . $this->file);
		$this->debug && error_log(__METHOD__ . ' Binary: ' . $this->binary);
		$this->debug && error_log(__METHOD__ . ' Internal encoding: ' . $this->internal_encoding);

		# Load entries.
		#$this->entries = $this->_list();
		#$this->rewind();
	}


	/**
	* Executes the list command and returns the entries.
	*
	* @return array
	*/
	protected function _list() {
		if (!file_exists($this->file)) {
			return array();
		}
		$cmd = $this->binary . ' l';
		/* TODO: this doesn't seem to work:
		if (preg_match('/^UTF-/i', $this->internal_encoding)) {
			$cmd .= ' -scsUTF-8';
		}
		elseif (preg_match('/^(?:Windows-1252|cp1252|ISO-8859-1)$/i', $this->internal_encoding)) {
			$cmd .= ' -scsWIN';
		}
		elseif (strcasecmp($this->internal_encoding, 'ASCII') == 0) {
			$cmd .= ' -scsDOS';
		}
		*/
		$cmd .= ' ' . escapeshellarg($this->file);
		$this->debug && error_log(__METHOD__ . ' Command: ' . $cmd);
		$rc = null;
		$output = array();
		exec($cmd, $output, $rc);
		$this->debug && error_log(__METHOD__ . ' rc: ' . $rc);
		if ($rc) {
			$this->debug && error_log(__METHOD__ . ' output: ' . join("\n", $output));
		}
		if ($rc) {
			throw new Exception("\"$cmd\" call failed with return code: $rc");
		}
		/*
		7-Zip (A) [64] 9.20  Copyright (c) 1999-2010 Igor Pavlov  2010-11-18
		p7zip Version 9.20 (locale=en_US.UTF-8,Utf16=on,HugeFiles=on,2 CPUs)

		Listing archive: 20140602_Website_(Sjabloon CDE) Zonder klantgegevens (His).csv.7z

		--
		Path = 20140602_Website_(Sjabloon CDE) Zonder klantgegevens (His).csv.7z
		Type = 7z
		Method = LZMA
		Solid = -
		Blocks = 1
		Physical Size = 152909
		Headers Size = 225

		   Date      Time    Attr         Size   Compressed  Name
		------------------- ----- ------------ ------------  ------------------------
		2014-06-06 00:08:39 ....A      3301358       152684  20140602_Website_(Sjabloon CDE) Zonder klantgegevens (His).csv
		------------------- ----- ------------ ------------  ------------------------
		                               3301358       152684  1 files, 0 folders
		*/
		$errors = array();
		$meta_started = false;
		$meta = array();
		$entries_started = false;
		$entries_field_widths = array();
		$entries = array();
		foreach ($output as $line) {
			#$line = trim($line);

			# Read until meta data starts
			if (!$meta_started) {
				if ($line == '--') {
					$meta_started = true;
				}
				elseif (preg_match('/^Error:\s*(.+)/', $line, $matches)) {
					$errors []= $matches[1];
				}
				continue;
			}

			# Read meta data until entries start
			if (!$entries_started) {
				if (preg_match('/^(Type|Method|Solid|Blocks|Physical Size|Headers Size) = (.*)/', $line, $matches)) {
					$meta[$matches[1]] = $matches[2];
				}
				elseif (preg_match('/^
					(-+\s+)	# DateTime
					(-+\s+)	# Attr
					(-+\s+)	# Size
					(-+\s+)	# Compressed
					(-+)	# Name
				$/x', $line, $matches)) {
					$entries_started = true;
					$entries_field_widths['DateTime']		= strlen($matches[1]);
					$entries_field_widths['Attr']			= strlen($matches[2]);
					$entries_field_widths['Size']			= strlen($matches[3]);
					$entries_field_widths['Compressed']	= strlen($matches[4]);
					$entries_field_widths['Name']			= null;
				}
				continue;
			}

			# Read entries until end
			if (substr($line,0,1) == '-') {
				break;
			}
			$x = 0;
			$entry = array();
			foreach ($entries_field_widths as $k => $w) {
				$entry[$k] = trim($w ? substr($line, $x, $w) : substr($line, $x));
				$x += $w;
			}
			$entries []= $entry;
		}
		if ($errors) {
			throw new SevenZipArchiveException("Error(s) listing archive contents:\n" . join("\n", $errors));
		}
		$this->meta = $meta;
		return $entries;
	}


	/**
	* Adds the contents of the given directory to the archive.
	* This sometimes offers significantly better compression results than adding files individually.
	*
	* @param string $realdir
	* @return bool
	*/
	public function addDir($realdir) {
		if (!is_string($realdir)) {
			throw new \InvalidArgumentException(gettype($realdir) . ' is not a legal realdir argument type');
		}
		if (!strlen($realdir)) {
			throw new \InvalidArgumentException('Missing realdir argument');
		}
		if (!is_dir($realdir)) {
			throw new \InvalidArgumentException('Directory "' . $realdir . '" does not exist');
		}
		# Make sure $realdir ends with '/.'
		if (substr($realdir,-1) == '/') {
			$realdir .= '.';
		}
		elseif (substr($realdir,-2) != '/.') {
			$realdir .= '/.';
		}

		# How it's done:
		# 7zr a -bb0 -bd -y -snl archive.7z /dir/containing/files/.
		# -bb0 set output log level switch supported in 16.02, but not in 9.20.
		# -snl store symbolic links as links switch supported in 16.02, but not in 9.20.
		$rc = null;
		$output = array();
		$cmd = escapeshellcmd($this->binary) . ' a -bd -y ' . escapeshellarg($this->file) . ' ' . escapeshellarg($realdir);
		$this->debug && error_log(__METHOD__ . ' Command: ' . $cmd);
		$rc = null;
		$output = array();
		exec("$cmd 2>&1", $output, $rc);
		$this->debug && error_log(__METHOD__ . ' rc: ' . $rc);
		$this->debug && error_log(__METHOD__ . ' Output: ' . join("\n", $output) . "\n");
		if ($rc) {
			trigger_error("\"$cmd\" call failed with return code $rc and output: " . join("\n", $output), E_USER_ERROR);
			return false;
		}
		$this->entries = null; $this->key = -1;
		return true;
	}


	/**
	* Adds a file to the archive using it's contents. Similar to http://www.php.net/manual/en/ziparchive.addfromstring.php
	*
	* @param string $localname name to add/update in the archive; may contain path parts
	* @param string $contents
	* @return bool
	*/
	public function addFromString($localname, $contents) {
		if (!is_string($localname)) {
			throw new \InvalidArgumentException(gettype($localname) . ' is not a legal localname argument type');
		}
		if (!strlen($localname)) {
			throw new \InvalidArgumentException('Missing localname argument');
		}
		if (!is_string($contents)) {
			throw new \InvalidArgumentException(gettype($contents) . ' is not a legal contents argument type');
		}

		# How it's done:
		# cat .gitignore | 7zr a -si'The €U/sucks/file.txt' test.7z
		$rc = null;
		$output = array();
		$cmd = escapeshellcmd($this->binary) . ' a -bd -y -si' . escapeshellarg($localname) . ' ' . escapeshellarg($this->file);
		$this->debug && error_log(__METHOD__ . ' Command: ' . $cmd);
		$rc = null;
		$output = array();
		exec("$cmd 2>&1", $output, $rc);
		$this->debug && error_log(__METHOD__ . ' rc: ' . $rc);
		$this->debug && error_log(__METHOD__ . ' output: ' . join("\n", $output) . "\n");
		if ($rc) {
			trigger_error("\"$cmd\" call failed with return code $rc and output: " . join("\n", $output), E_USER_ERROR);
			return false;
		}
		$this->entries = null; $this->key = -1;
		return true;
	}


	/**
	* Dummy method for interchangeability with ZipArchive.
	*
	* @return bool
	*/
	public function close() {
		return true;
	}


	/**
	* Extract the complete archive or the given files to the specified destination.
	*
	* @param string $destination
	* @param string|array $names
	* @return bool
	*/
	public function extractTo($destination, $names = null) {
		if (!is_string($destination)) {
			throw new \InvalidArgumentException(gettype($destination) . ' is not a legal destination argument type');
		}
		if (!strlen($destination)) {
			throw new \InvalidArgumentException('Missing destination argument');
		}
		if (!is_dir($destination)) {
			throw new \InvalidArgumentException("Destination '$destination' not found or not a directory");
		}
		if (!(is_array($names) || is_string($names) || is_null($names))) {
			throw new \InvalidArgumentException(gettype($names) . ' is not a legal names argument type');
		}
		if (is_string($names)) {
			if (strlen($names)) {
				$names = array($names);
			}
		}
		if ($names) {
			$name_to_index = array();
			$entries = $this->entries();
			for ($i = 0; $i < count($entries); $i++) {
				$name_to_index[$entries[$i]['Name']] = $i;
			}
			foreach ($names as $name) {
				if (!array_key_exists($name, $name_to_index)) {
					trigger_error("Entry '$name' not found in archive", E_USER_WARNING);
					return false;
				}
			}
		}

		# TODO: make path and executable configurable
		$rc = null;
		$output = array();
		$cmd = $this->binary . ' x -bd -y -o' . escapeshellarg($destination) . ' ' . escapeshellarg($this->file);
		if ($names) {
			$cmd .= ' ' . join(' ', array_map(function($x) { return escapeshellarg($x); }, $names));
		}
		$this->debug && error_log(__METHOD__ . ' Command: ' . $cmd);
		$rc = null;
		$output = array();
		exec("$cmd 2>&1", $output, $rc);
		$this->debug && error_log(__METHOD__ . ' rc: ' . $rc);
		$this->debug && error_log(__METHOD__ . ' Output: ' . join("\n", $output) . "\n");
		if ($rc) {
			#throw new Exception("\"$cmd\" call failed with return code: $rc");
			trigger_error("\"$cmd\" call failed with return code: $rc", E_USER_ERROR);
			return false;
		}
		return true;
	}


	/**
	* Returns an associative array of archive meta data.
	*
	* @return array|null
	*/
	public function metadata() {
		if (is_null($this->meta)) {
			if (file_exists($this->file)) {
				$this->_list();	# Sets $this->meta
			}
		}
		return $this->meta;
	}


	/**
	* Returns the entries as an array
	*
	* @return array
	*/
	public function entries() {
		if (is_null($this->entries)) {
			if (file_exists($this->file)) {
				$this->entries = $this->_list();
			}
		}
		return is_null($this->entries) ? array() : $this->entries;
	}


	/**
	* Tests the archive's integrity.
	*
	* @return boolean
	*/
	public function test() {
		if (!file_exists($this->file)) {
			return false;
		}
		$cmd = $this->binary . ' t';
		$cmd .= ' ' . escapeshellarg($this->file) . ' 2>&1';
		$this->debug && error_log(__METHOD__ . ' Command: ' . $cmd);
		$rc = null;
		$output = array();
		exec($cmd, $output, $rc);
		$this->debug && error_log(__METHOD__ . ' rc: ' . $rc);
		$result = false;
		if ($rc) {
			$this->debug && error_log(__METHOD__ . ' output: ' . join("\n", $output));
			if ($rc != 2) {	# 2 is the exit code when the file is corrupt or unreadable
				throw new Exception("\"$cmd\" call failed with return code: $rc");
			}
		}
		else {
			$result = true;
			#foreach ($output as $line) {
			#	if ($line == 'Everything is Ok') {	# verification was successful
			#		$result = true;
			#		break;
			#	}
			#}
		}
		return $result;
	}


	/**
	* Returns the numer of entries.
	* Required Countable interface method.
	*
	* @return int
	*/
	public function count() {
		return count($this->entries());
	}


	/**
	* Returns the entry at the given index.
	*
	* @param int $index
	* @return int
	*/
	public function get($index) {
		return isset($this->entries[$index]) ? $this->entries[$index] : null;
	}


	/**
	* Sets debug mode on/off.
	*
	* @param boolean $value
	* @return void
	*/
	public function setDebug($value) {
		$this->debug = !!$value;
	}


	/**
	* Required Iterator interface method.
	*/
	public function current() {
		return is_array($this->entries) ? $this->entries[$this->key] : null;
	}


	/**
	* Required Iterator interface method.
	*/
	public function key() {
		return $this->key;
	}


	/**
	* Required Iterator interface method.
	*/
	public function next() {
		if (is_array($this->entries)) {
			$this->key++;
		}
	}


	/**
	* Required Iterator interface method.
	*/
	public function rewind() {
		if (is_null($this->entries)) {
			if (file_exists($this->file)) {
				$this->entries = $this->_list();
			}
		}
		$this->key = is_array($this->entries) && $this->entries ? 0 : -1;
	}


	/**
	* Required Iterator interface method.
	*/
	public function valid() {
		return is_array($this->entries) && ($this->key >= 0) && ($this->key < count($this->entries));
	}

}
