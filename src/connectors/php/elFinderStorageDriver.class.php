<?php
/**
 * Based class for elFinder storages.
 * Realize all storage logic.
 * Create abstraction level under base file sistem operation (like mkdir etc.), 
 * which must be implemented in childs classes 
 *
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 * @author Alexey Sukhotin
 **/
abstract class elFinderStorageDriver {
	
	/**
	 * Root directory path
	 *
	 * @var string
	 **/
	protected $root = '';
	
	/**
	 * Default directory to open
	 *
	 * @var string
	 **/
	protected $start = '';
	
	/**
	 * Base URL
	 *
	 * @var string
	 **/
	protected $URL = '';
	
	/**
	 * Base thumbnails URL
	 *
	 * @var string
	 **/
	protected $tmbURL = '';
	
	
	/**
	 * Storage id - used as prefix for files hashes
	 *
	 * @var string
	 **/
	protected $id = '';
	
	/**
	 * Error message from last failed action
	 *
	 * @var string
	 **/
	protected $error = '';
	
	/**
	 * Today 24:00 timestamp
	 *
	 * @var int
	 **/
	protected $today = 0;
	
	/**
	 * Yestoday 24:00 timestamp
	 *
	 * @var int
	 **/
	protected $yesterday = 0;
	
	/**
	 * Some options sending to client
	 *
	 * @var array
	 **/
	protected $params = array();
	
	/**
	 * Flag - is storage loaded correctly
	 *
	 * @var bool
	 **/
	protected $available = false;
	
	/**
	 * Object configuration
	 *
	 * @var array
	 **/
	protected $options = array(
		'path'         => '',           // root path
		'alias'        => '',           // alias to replace root dir name
		'URL'          => '',           // root url, not set to disable sending URL to client (replacement for old "fileURL" option)
		'tmbURL'       => '',           // thumbnails dir URL
		'startPath'    => '',           // open this path on initial request instead of root path
		'disabled'     => array(),      // list of commands names to disable on this root
		'uploadAllow'  => array(),      // mimetypes which allowed to upload
		'uploadDeny'   => array(),      // mimetypes which not allowed to upload
		'uploadOrder'  => 'deny,allow', // order to proccess uploadAllow and uploadAllow options
		'treeDeep'     => 1,            // how many subdirs levels return
		'dateFormat'   => 'j M Y H:i',  // files dates format
		
		'copyFrom'     => true,
		'copyTo'       => true,
	);
	
	/**
	 * Filter directory content rule
	 *
	 * @var int
	 **/
	protected static $FILTER_DIRS_ONLY = 1;
	
	/**
	 * Filter directory content rule
	 *
	 * @var int
	 **/
	protected static $FILTER_FILES_ONLY = 2;
	
	/**
	 * Directory content sort rule
	 *
	 * @var int
	 **/
	public static $SORT_NAME_DIRS_FIRST = 1;
	/**
	 * Directory content sort rule
	 *
	 * @var int
	 **/
	public static $SORT_KIND_DIRS_FIRST = 2;
	/**
	 * Directory content sort rule
	 *
	 * @var int
	 **/
	public static $SORT_SIZE_DIRS_FIRST = 3;
	/**
	 * Directory content sort rule
	 *
	 * @var int
	 **/
	public static $SORT_NAME            = 4;
	/**
	 * Directory content sort rule
	 *
	 * @var int
	 **/
	public static $SORT_KIND            = 5;
	/**
	 * Directory content sort rule
	 *
	 * @var int
	 **/
	public static $SORT_SIZE            = 6;
	
	
	
	/**
	 * Constuctor
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function __construct($id, array $opts) {
		$this->id      = $id;
		$this->options = array_merge($this->options, $opts);
		$this->root    = @$this->options['path'];
		$this->start   = @$this->options['startPath'];
		$this->URL     = empty($this->options['URL']) ? '' : $this->options['URL'].(substr($this->options['URL'], -1, 1) != '/' ? '/' : '');
		$this->tmbURL  = empty($this->options['tmbURL']) ? '' : $this->options['tmbURL'].(substr($this->options['tmbURL'], -1, 1) != '/' ? '/' : '');
		
		// root does not exists
		if (!$this->root || !$this->_isDir($this->root)) {
			return;
		}
		
		// root not readable and writable
		if (!($readable = $this->_isReadable($this->root)) 
		&& !$this->_isWritable($this->root)) {
			return;
		}
		
		if (!$readable) {
			$this->start = $this->URL = $this->tmbURL = '';
		} elseif ($this->start) {
			// check start dir if set 
			if (!$this->_accepted($this->start) 
			|| !$this->_inpath($this->start, $this->root) 
			|| !$this->_isDir($this->start) 
			|| !$this->_isReadable($this->start)) {
				$this->start = '';
			}
		}

		$this->_configure();
		$this->available = true;
	}
	
	/**
	 * Return true if storage available to work with
	 *
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function available() {
		return $this->available;
	}
	
	/**
	 * Return error message from last failed action
	 *
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	public function error() {
		return $this->error;
	}
	
	/**
	 * Return debug info
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function debug() {
		return array(
			'root'       => $this->options['basename'],
			'driver'     => 'LocalFileSystem',
			'mimeDetect' => $this->options['mimeDetect'],
			'imgLib'     => $this->options['imgLib']
		);
	}
	
	/***************************************************************************/
	/*                              storage API                                */
	/***************************************************************************/
	
	/**
	 * Return root directory hash
	 *
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	public function root() {
		return $this->encode($this->root);
	}
	
	/**
	 * Return start directory hash if set
	 *
	 * @return string|false
	 * @author Dmitry (dio) Levashov
	 **/
	public function start() {
		return $this->start ? $this->encode($this->start) : false;
	}
	
	/**
	 * Return true if file exists
	 *
	 * @param  string  file hash
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function fileExists($hash) {
		$path = $this->decode($hash);
		return $path && $this->_accepted($path) && $this->_fileExists($path) ;
	}
	
	/**
	 * Return true if file is ordinary file
	 *
	 * @param  string  file hash
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function isFile($hash) {
		$path = $this->decode($hash);
		return $path && $this->_accepted($path) && $this->_isFile($path);
	}
	
	/**
	 * Return true if file is directory
	 *
	 * @param  string  file hash
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function isDir($hash) {
		$path = $this->decode($hash);
		return $path && $this->_accepted($path) && $this->_isDir($path);
	}
	
	/**
	 * Return true if file is symlink
	 *
	 * @param  string  file hash
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function isLink($hash) {
		$path = $this->decode($hash);
		return $path && $this->_accepted($path) && $this->_isLink($path);
	}
	
	/**
	 * Return true if file is readable
	 *
	 * @param  string  file hash 
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function isReadable($hash) {
		$path = $this->decode($hash);
		return $path && $this->_accepted($path) && $this->_isReadable($path);
	}
	
	/**
	 * Return true if file is readable
	 *
	 * @param  string  file hash 
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function isWritable($hash) {
		$path = $this->decode($hash);
		return $path && $this->_accepted($path) && $this->_isWritable($path);
	}
	
	/**
	 * Return true if file can be removed
	 *
	 * @param  string  file hash 
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function isRemovable($hash) {
		$path = $this->decode($hash);
		return $path && $this->_accepted($path) && $this->_isRemovable($path);
	}
	
	/**
	 * Return file/dir info
	 *
	 * @param  string  file hash
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function info($hash) {
		$path = $this->decode($hash);
		
		if (!$path || $this->_accepted($path) || !$this->_fileExists($path)) {
			$this->setError('File not found');
		}

		$mime  = $this->_mimetype($path);
		$mtime = $this->_mtime($path);

		if ($mtime > $this->today) {
			$date = 'Today '.date('H:i', $mtime);
		} elseif ($mtime > $this->yesterday) {
			$date = 'Yesterday '.date('H:i', $mtime);
		} else {
			$date = date($this->options['dateFormat'], $mtime);
		}
		
		$info = array(
			'name'  => htmlspecialchars($path == $this->options['path'] ? $this->options['basename'] : basename($path)),
			'hash'  => $this->encode($path),
			'mime'  => $mime,
			'date'  => $date, 
			'size'  => $mime == 'directory' ? 0 : $this->_filesize($path),
			'read'  => $this->_isReadable($path),
			'write' => $this->_isWritable($path),
			'rm'    => $this->_isRemovable($path),
			);
			
		if ($this->_isLink) {
			if (false === ($link = $this->_readlink($path))) {
				$info['mime']  = 'symlink-broken';
				$info['read']  = false;
				$info['write'] = false;
			} else {
				$info['mime']   = $this->_mimetype($link);
				$info['link']   = $this->encode($link);
				$info['linkTo'] = DIRECTORY_SEPARATOR.$this->options['basename'].substr($link, strlen($this->options['path']));
			}
		}
		
		if ($info['mime'] != 'directory' && $info['read']) {
			
			// if (strpos($info['mime'], 'image') === 0 && false != ($s = getimagesize($path))) {
			// 	$info['dim'] = $s[0].'x'.$s[1];
			// 	if ($this->resizable($info['mime'])) {
			// 		$info['resize'] = true;
			// 		if (($tmb = $this->tmbPath($path)) != '') {
			// 			$info['tmb'] = file_exists($tmb) ? $this->path2url($tmb) : true;
			// 		}
			// 	}
			// }
			
		}
			
		return $info;
	}
	
	/**
	 * Return directory info (same as info() but with additional fields)
	 * Used to get current working directory info
	 *
	 * @param  string  directory hash
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function dir($hash) {
		$path = $this->decode($hash);
		
		if (!$path || $this->_accepted($path) || !$this->_fileExists($path)) {
			$this->setError('File not found');
		}
		
		if (!$this->_isReadable($path)) {
			return $this->setError('Access denied');
		}
		
		if ($this->_isLink($path)) {
			// try to get link target
			if (false === ($path = $this->_readlink($path))) {
				return $this->setError('Broken link');
			}
			if (!$this->_isReadable($path)) {
				return $this->setError('Access denied');
			}
		}
		
		if (!$this->_isDir($path)) {
			return $this->setError('Invalid parameters');
		}
		
		$info = $this->info($path);
		
		return is_array($info)
			? array_merge($this->info($path), array(
					'phash'  => $path == $this->options['path'] ? false : $this->encode(dirname($path)),
					'url'    => $this->options['URL'] ? $this->path2url($path, true) : '',
					'rel'    => DIRECTORY_SEPARATOR.$this->options['basename'].substr($path, strlen($this->options['path'])),
					'params' => $this->_params()
				))
			: false;
		
	}
	
	/***************************************************************************/
	/*                                utilites                                 */
	/***************************************************************************/
	
	/**
	 * Encode path into hash
	 *
	 * @param  string  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function encode($path) {
		return $this->id.$this->_encode($path);
	}
	
	/**
	 * Decode path from hash
	 *
	 * @param  string  file hash
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function decode($hash) {
		return $this->_decode(substr($hash, strlen($this->id)));
	}
		
		
	
	/**
	 * Sort files 
	 *
	 * @param  object  file to compare
	 * @param  object  file to compare
	 * @return int
	 * @author Dmitry (dio) Levashov
	 **/
	protected function compare($f1, $f2) {
		$d1 = $f1['mime'] == 'directory';
		$d2 = $f2['mime'] == 'directory';
		$m1 = $f1['mime'];
		$m2 = $f2['mime'];
		$s1 = $f1['size'];
		$s2 = $f2['size'];
		
		if ($this->sort <= self::$SORT_SIZE_DIRS_FIRST && $d1 != $d2) {
			return $d1 ? -1 : 1;
		}
		
		if (($this->sort == self::$SORT_KIND_DIRS_FIRST || $this->sort == self::$SORT_KIND) && $m1 != $m2) {
			return strcmp($m1, $m2);
		}
		
		if (($this->sort == self::$SORT_SIZE_DIRS_FIRST || $this->sort == self::$SORT_SIZE) && $s1 != $s2) {
			return $s1 < $s2 ? -1 : 1;
		}
		
		return strcmp($f1['name'], $f2['name']);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	protected function setError($msg)	{
		$this->error = $msg;
		return false;
	}
	

	
	
	/***************************************************************************/
	/*                           abstract methods                              */
	/***************************************************************************/
	
	/**
	 * Any init actions here
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _configure();
	
	/**
	 * Return current root data required by client (disabled commands, archive ability, etc.)
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _params();
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _encode($path);

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _decode($hash);
	
	/**
	 * Return true if $path is subdir of $parent
	 *
	 * @param  string  $path    path to check
	 * @param  string  $parent  parent path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _inpath($path, $parent);
	
	/**
	 * Return path related to root path
	 *
	 * @param  string  $path  fuke path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _relpath($path);
	
	/**
	 * Return file URL
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _path2url($path);
	
	/**
	 * Return true if filename is accepted for current storage
	 *
	 * @param  string  file path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _accepted($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _fileExists($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _isFile($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _isDir($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _isLink($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _isReadable($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _isWritable($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _isRemovable($path);
	
	/**
	 * Return file parent directory name
	 *
	 * @param  string $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _dirname($path);

	/**
	 * Return file name
	 *
	 * @param  string $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _basename($path);

	/**
	 * Return file modification time
	 *
	 * @param  string $path  file path
	 * @return int
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _filemtime($path);
	
	/**
	 * Return file size
	 *
	 * @param  string $path  file path
	 * @return int
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _filesize($path);
	
	/**
	 * Return file mime type
	 *
	 * @param  string $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	abstract protected function _mimetype($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _readlink($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _mkdir($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _touch($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _symlink($target, $link);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _rmdir($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _unlink($path);
	
	
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _fopen($path, $mode);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _fclose($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _scandir($path);

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _tree($path);
	

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _copy($from, $to);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _fileGetContents($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	abstract protected function _filePutContents($path, $content);
	
	

	/**
	 * Return file thumnbnail path
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	// abstract protected function _tmbPath($path);
	
	/**
	 * Return file thumnbnail URL
	 *
	 * @param  string  $path  thumnbnail path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	// abstract protected function _tmbURL($path);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	// abstract protected function _tmb($path, $tmb);
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	// abstract protected function _resizeImg($path, $w, $h);
	
} // END class 

?>