<?php
$config = [
  'app_name'		=> 'NanoWiki',
  'title'		=> 'NanoWiki',
  'app_url'		=> null, // (auto-detected, although you can manually specify it if you need to)
  'file_path' 		=> __DIR__ . '/files', // no trailing slash
  'default_doc'		=> 'index.md',
  'copyright'		=> 'nobody@nowhere',
  'read_only'		=> false,
  'nanowiki_url'	=> 'https://github.com/iliu-net/nanowiki',
  'theme'		=> null,
  'cookie_id'		=> 'NanoWiki',
  'cookie_age'		=> 86400 * 30,
  'codemirror'		=> 'https://cdn.jsdelivr.net/npm/codemirror@5.65.4/',
  'proxy-ips'		=> null,	// list of IP addresses of trusted reverse proxies
  'unix_eol'		=> true,	// Convert payload EOL to UNIX style format
  // 'umask'		=> 0022,	// optional umask
];
if (file_exists(__DIR__.'/config.yaml')) {
  $config = array_merge($config, yaml_parse_file(__DIR__.'/config.yaml'));
}
if (isset($config['umask'])) umask($config['umask']);

class NanoWiki
{
    public $config = null; // configuration variables
    public $context = null; // context for file_list and bread_crumbs
    public $file_list = []; // array of available files
    public $bread_crumbs = []; // array of breadcrumbs
    public $plugin_list = [];
    public $handlers = []; // media handlers (based on file extension)
    public $url = null;
    public $html = null;
    public $source = null;
    public $events = [];
    public $meta = [];	// current doc meta data
    public $https = false;
    public $toolbar = []; // Toolbar items
    public $env = [];		// HTTP environment

    /**
     * Converts a long string of bytes into a readable format e.g KB, MB, GB, TB, YB
     *
     * @param {Int} num The number of bytes.
     */
    static public function readableBytes($bytes) {
      $i = floor(log($bytes) / log(1024));
      $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
      return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
    }

    # These are simple optimizations
    private $caches = [ 'meta' => [], 'contents' => [], 'offsets' => [] ];
    public function fileGetContents($file_path) {
      if (!isset($this->caches['contents'][$file_path]))
	$this->caches['contents'][$file_path] = file_get_contents($file_path);
      return $this->caches['contents'][$file_path];
    }
    public function fileGetMeta($file_path) {
      if (!isset($this->caches['meta'][$file_path])) {
	$pi = pathinfo($file_path);

	if (isset($this->handlers[$pi['extension'] ?? ''])) {
	  $obj = $this->handlers[$pi['extension']];
	  list($meta,$offset) = call_user_func_array([$obj,'readMeta'],[$this,$file_path]);
	} else {
	  $meta = [];
	  $offset = 0;
	}
	if (empty($meta['title'])) {
	  if (basename($file_path) == $this->config['default_doc']) {
	    $meta['title'] = $this->url;
	  } else {
	    if (empty($this->url)) {
	      $meta['title'] = $this->config['app_name'];
	    } else {
	      $meta['title'] = $pi['filename'];
	    }
	  }
	}

	$file_date = filemtime($file_path);
	$file_size = filesize($file_path);

	$meta = array_merge($meta, [
	    'file-path' => $file_path,
	    'file-ext' => $pi['extension'] ?? '',
	    'file-name' => $pi['filename'],
	    'file-datetime' => gmdate('Y-m-d H:i:s',$file_date),
	    'file-epoch' => $file_date,
	    'file-year' => gmdate('Y'),
	    'file-date' => gmdate('Y-m-d'),
	    'file-size' => self::readableBytes($file_size),
	    'file-bytes' => $file_size,
	    'file-tags' => [ gmdate('Y',$file_date) ],
	  ]);
	if (!empty($pi['extension'])) {
	  $meta['file-tags'][] = $pi['extension'];
	}
	//~ echo '<pre>';
	//~ print_r([$meta,$offset]);
	//~ echo '</pre>';
	$all_tags = [];

        $meta = $this->event('meta_read_after', $meta);

	// Fill in standard tags as needed...
	$meta['date'] = $meta['date'] ?? $meta['file-date'];
	$meta['year'] = $meta['year'] ?? $meta['file-year'];

	foreach ($meta as $k => $v) {
	  if ($k != 'tags' && substr($k,-5) != '-tags') continue;
	  if (!is_array($v)) $v = preg_split('/\s*,\s*/',$v);
	  foreach ($v as $i) {
	    if (is_array($i)) continue;
	    $i = strtolower($i);
	    $all_tags[$i] = $i;
	  }
	}
	natsort($all_tags);
	$meta['all-tags'] = $all_tags;

	$this->caches['meta'][$file_path] = $meta;
	$this->caches['offsets'][$file_path] = $offset;
      }
      return $this->caches['meta'][$file_path];
    }
    public function fileGetOffset($file_path) {
      return $this->caches['offsets'][$file_path] ?? 0;
    }

    static public function VERSION() {
      return trim(file_get_contents(__DIR__.'/VERSION'));
    }
    static public function sanitize($url) {
      $url = preg_replace('/\s+/','_', $url);
      $url = preg_replace('/[^A-Za-z0-9-\/\._]/', '', $url);
      $url = preg_replace('/\/\.\.?\//', '/', '/'.$url.'/');
      $url = preg_replace('/\/+/', '/', $url);
      $url = preg_replace('/^\/+/', '', $url);
      $url = preg_replace('/\/+$/', '', $url);

      $d = dirname($url);
      $b = basename($url);

      if (false !== ($i = strrpos($b,'.')) && $i > 0) {
	$b = substr($b,0,$i).strtolower(substr($b,$i));
	if ($d == '.') {
	  $url = $b;
	} else {
	  $url = $d.'/'.$b;
	}
      }
      if ($d && $d != '.') {
	$d = preg_replace('/\./','', $d);
	$url = $d . '/' . $b;
      }
      return $url;
    }

    public function __construct($config) {
      $this->event('init', $this);
      $this->config = $config;

      $this->env['scheme'] = $_SERVER['REQUEST_SCHEME'] ?? '*INTERNAL*';
      $this->env['remote_addr'] = $_SERVER['REMOTE_ADDR'] ?? '*LOCAL*';
      $this->env['host'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

      if (!empty($this->config['proxy-ips'])) {
	$rp = $this->config['proxy-ips'];
	if (!is_array($rp)) $rp = preg_split('/\s*,\s*/',trim($rp));
	if (in_array($_SERVER['REMOTE_ADDR'],$rp)) {
	  // IP is a registered reverse proxy....
	  $this->env['scheme'] = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $this->env['scheme'];
	  $this->env['remote_addr'] = $_SERVER['HTTP_X_REAL_IP'] ?? $this->env['remote_addr'];
	  $this->env['host'] = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $this->env['host'];
	}
      }
      if ($this->env['scheme'] == 'https') $this->https = true;

      if (!$this->config['app_url']) {
	$this->config['app_url'] = '//'.$this->env['host'].str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
      }
      if (empty($this->config['app_dir'])) $this->config['app_dir'] = __DIR__;
      $this->event('config_loaded', $this);
      $this->loadPlugins();
      $this->event('plugins_loaded', $this);
    }


    /* These two functions are stubs for the moment... */
    public function isWritable($filepath) {
      //~ return false;
      if (empty($this->config['read_only'])) {
	$wr = true;
      } else {
	if (!is_bool($this->config['read_only']) && $this->config['read_only'] == 'not-auth') {
	  # TODO: check how auth user is received
	  # $_SERVER['PHP_AUTH_USER'] or REMOTE_USER
	  $wr = true;
	} else {
	  $wr = !filter_var($this->config['read_only'],FILTER_VALIDATE_BOOLEAN);
	}
      }
      return $this->event('check_writeable', $wr);
    }
    public function isReadable($filepath) {
      return $this->event('check_readable', true);
    }

    public function mkUrl(...$uri) {
      $uri = preg_replace('/\/+/','/',trim(implode('/',$uri),'/'));
      return rtrim($this->config['app_url'],'/').'/'.$uri;
    }
    /**
     * Finds a file with that URL and outputs it nicely
     *
     * @param string $url a URL slug that should presumably match a file
     */
    public function run($url = null) {
      $this->event('run_init', $this);
      $this->url = self::sanitize($url);
      $this->event('url_loaded', $this);

      $this->checkContext();
      $this->event('context_loaded', $this);

      if (!empty($_GET['go'])) {
	if ($_GET['go'][0] == '/') {
	  header('Location: '.$this->mkUrl($_GET['go']));
	} else {
	  if (is_dir($this->config['file_path'].'/'.$this->url)) {
	    header('Location: '.$this->mkUrl($this->url,$_GET['go']));
	  } else {
	    header('Location: '.$this->mkUrl(dirname($this->url),$_GET['go']));
	  }
	}
	exit;
      }

      $this->listFiles();
      $this->event('list_loaded', $this);
      $this->bakeCrumbs();
      $this->event('crumbs_loaded', $this);

      $this->loadTools();
      $this->event('tools_loaded', $this);

      $file_path = $this->getFilePath($this->url);

      if(isset($_GET['do'])) {
	if ($_GET['do'] == 'delete') {
	  if (!$this->isWritable($file_path)) {
	    $this->event('write_access_error',$this);
	    die("Write access: $file_path"); #TODO:
	  } else {
	    $this->delete($file_path);
	    exit;
	  }
	} elseif ($_GET['do'] == 'rename' && !empty($_GET['name'])) {
	  if (!$this->isWritable($file_path)) {
	    $this->event('write_access_error',$this);
	    die("Write access: $file_path"); #TODO:
	  } else {
	    $this->rename($file_path,$_GET['name']);
	    exit;
	  }
	}
      }

      if (count($_POST)) {
	if (!$this->isWritable($file_path)) {
	  $this->event('write_access_error',$this);
	  die("Write access: $file_path"); #TODO:
	}
	$this->post($file_path);
      } else {
	if (!file_exists($file_path)) {
	  $this->error404($file_path);
	  return;
	}
	$this->meta = $this->fileGetMeta($file_path);

	if (!$this->isReadable($file_path)) {
	  $this->event('read_access_error',$this);
	  die("Read access: $file_path");
	}
	$this->view($file_path);
      }
    }

    #
    # These function
    protected function checkContext() {
      $cookie_name = $this->config['cookie_id'].'_context';
      if (isset($_COOKIE[$cookie_name])) {
	parse_str($_COOKIE[$cookie_name],$context);
      } else {
	$context = [];
      }
      $set_cookie = false;

      if (empty($context['timestamp'])) $context['timestamp'] = 1;

      if (empty($context['lstmode'])) $context['lstmode'] = 'global';
      if (isset($_GET['sw_lstmode'])) {
	$m = strtolower($_GET['sw_lstmode']);
	if (in_array($m,['local','global'])) {
	  if ($m != $context['lstmode']) {
	    $context['lstmode'] = $m;
	    $set_cookie = true;
	  }
	}
      }

      if (isset($_GET['q'])) {
	$q = strtolower($_GET['q']);
	if (!isset($context['q']) || $q != $context['q']) {
	  $context['q'] = $q;
	  $set_cookie = true;
	  if (empty($context['q'])) unset($context['q']);
	}
      }

      if (empty($context['debug'])) $context['debug'] = false;
      if (isset($_GET['debug'])) {
	if (!$context['debug']) {
	  $context['debug'] = true;
	  $set_cookie = true;
	}
      }
      if (isset($_GET['nodebug'])) {
	if ($context['debug']) {
	  $context['debug'] = false;
	  $set_cookie = true;
	}
      }

      if ($set_cookie || time()-$context['timestamp'] > $this->config['cookie_age']/2) {
	$context['timestamp'] = time();
	setcookie($cookie_name,http_build_query($context), [
	  'expires' => time() + $this->config['cookie_age'],
	  'path' => dirname($_SERVER['SCRIPT_NAME']),
	  'secure' => $this->https,
	  'httponly' => true,
	  'samesite' => 'Lax',
	]);
      }
      $this->context = $context;
    }

    protected function delete($file_path) {
      if (is_dir($file_path)) {
	if (!$this->url) die("Can not delete home document");
	$ff = glob($file_path.'/*');
	if (count($ff)) die('Folder: "'.$this->url.'" not empty!');
	if (rmdir($file_path) === false) {
	  die("Error rmdir($file_path)");
	}
	header('Location: '.$this->mkUrl(dirname($this->url)));
      } else {
	if (unlink($file_path) === false) {
	  die("Error unlink($file_path)");
	}
	header('Location: '.$this->mkUrl(dirname($this->url)));
      }
      exit;
    }

    protected function rename($file_path,$newname) {
      //~ echo "NewName: $newname<br>";
      $q = $newname[0] == '/' ? '/' : '';
      $newname = $q.self::sanitize($newname);

      //~ echo "Orig: $file_path<br>";
      //~ echo "NewName: $newname<br>";
      //~ echo "<pre>";
      //~ echo "STRPOS: ";
      //~ $v = strpos($newname,'/');
      //~ var_dump($v);
      //~ echo "\n</pre>";

      if (strpos($newname,'/') === false) {
	$target = dirname($file_path).'/'.$newname;
      } else {
	$target = $this->config['file_path'].'/'.$newname;
      }

      //~ echo "target: $target<br>";
      if ($file_path == $this->config['file_path']) {
	die("Cannot rename root directory");
      }

      if (rename($file_path,$target) == false) {
	die("Error rename($file_path,$target)");
      }

      $location = str_replace($this->config['file_path'].'/', '', $target);
      echo('Location: '. $location.'<br>');
      Header('Location: '. $this->mkUrl($location));
      echo '<br>';
      exit;
    }

    /**
     * Handles 404 errors
     *
     * @param string $file_path to missing file
     */
    public function error404($file_path) {
      http_response_code(404);
      $this->meta['year'] = $this->meta['year'] ?? gmdate('Y');
      $this->meta['title'] = $this->meta['title'] ??
	'404: '.htmlspecialchars($this->url);

      $this->event('error404', $this);

      $ext = pathinfo($file_path)['extension'] ?? '';
      if (isset($this->handlers[$ext])) {
	$obj = $this->handlers[$ext];
	call_user_func_array([$obj,'error404'],[$this,$file_path]);
	return;
      }
      // Default 404 hanlder
      $PicoWiki = $this;
      require(__DIR__ . '/backend/templates/404.html');
    }


    /**
     * Reads all files in the path
     *
     * @param string $path a glob path pattern
     */
    protected function listFiles() {
      if ($this->context['lstmode'] == 'local') {
	$url = $this->url;
	$dpath = $this->config['file_path'].'/'.$url;
	if (!is_dir($dpath)) {
	  $url = dirname($url);
	  $dpath = dirname($dpath);
	}
	//~ echo "url: $url<br>";
	//~ echo "dpath: $dpath<br>";

	$this->file_list = [];

	$dp = opendir($dpath);
	if ($url != '') $url .= '/';
	if ($dp !== false) {
	  while (false !== ($fn = readdir($dp))) {
	    if ($fn[0] == '.' || $fn == $this->config['default_doc']) continue;
	    $this->file_list[] = $url . $fn;
	  }
	  closedir($dp);
	}
      } else {
	$path = $this->config['file_path'];
	$this->file_list = $this->readDirectory($path);
      }

      natsort($this->file_list);

      if (!empty($this->context['q'])) {
	$q = $this->context['q'];
	$res = [];
	if ($this->context['lstmode'] == 'local') {
	  $this->file_list[] = $this->url;
	}
	foreach ($this->file_list as $ff) {
	  $fp = $this->getFilePath($ff);
	  if (file_exists($fp.'/'.$this->config['default_doc'])) {
	    $fp = $fp.'/'.$this->config['default_doc'];
	  }
	  if (!file_exists($fp)) continue;

	  $ext = pathinfo($fp)['extension'] ?? '';
	  if (isset($this->handlers[$ext])) {
	    $text = $this->fileGetContents($fp);
	    if (preg_match('/'.$q.'/i',$text)) {
	      $res[] = $ff;
	    }
	  }
	  $this->file_list = $res;
	}
      }
    }
    /**
     * Generate bread-crumbs
     */
    protected function bakeCrumbs() {
      if ($this->context['lstmode'] == 'local') {
	$crumbs = [
	  [
	    'href' => $this->mkUrl(),
	    'title' => 'Home page',
	    'text' => ' &#x2302; ',
	  ],
	];
	if ($this->url) {
	  $parts = explode('/', $this->url);
	  for ($i = 0; $i < count($parts) ; ++$i) {

	    $urlpath = implode('/',array_slice($parts,0,$i+1));
	    $crumbs[] = [
	      'href' => $this->mkUrl($urlpath),
	      'title' => $urlpath,
	      'text' => $parts[$i],
	      'prefix' => ' : ',
	    ];
	  }
	}

	$crumbs[] = [
	    'href' => $this->mkUrl($this->url).'?'.http_build_query([
		  'sw_lstmode' => 'global'
	      ]),
	    'title' => 'Switch to global context',
	    'text' => ' &#x1F310; ',
	    'class' => 'dropdown-topbar-right',

	];
      } else {
        $crumbs = [
	  [
	    'href' => $this->mkUrl(),
	    'title' => 'Home page',
	    'text' => ' &#x2302; ',
	  ],
	  [
	    'href' => $this->mkUrl($this->url).'?'.http_build_query([
		  'sw_lstmode' => 'local'
	      ]),
	    'title' => 'Switch to local context',
	    'text' => ' &#x25CE; ',
	    'class' => 'dropdown-topbar-right',
	  ]
	];
      }
      $this->bread_crumbs = $crumbs;
    }
    public function serveCrumb($crumb) {
      $txt = ($crumb['prefix'] ?? '') . '<a';
      foreach ($crumb as $k=>$v) {
	if ($k == 'text' || $k == 'prefix') continue;
	$txt .= ' '.$k.'="'.$v.'"';
	if ($k == 'title' && !isset($crumb['alt'])) $txt .= ' alt="'.$v.'"';
	if ($k == 'alt' && !isset($crumb['title'])) $txt .= ' title="'.$v.'"';
      }
      $txt .= '>'.($crumb['text'] ?? ' ').'</a>';
      return $txt;
    }
    /**
     * Returns a list of all files recursive
     *
     * @param string $path a directory path
     */
    protected function readDirectory($path) {
      $dq = [ '' ];
      $files = [];
      while (count($dq)) {

	$cd = array_shift($dq);
	$dp = opendir($path . $cd);
	if ($dp === false) continue;

	$cd = $cd .'/';
	while (false !== ($fn = readdir($dp))) {
	  if ($fn[0] == '.' || $fn == $this->config['default_doc']) continue;
	  $files[] = $cd . $fn;
	  if (is_dir($path. $cd.$fn)) $dq[] = $cd.$fn;
	}
	closedir($dp);
      }
      return $files;
    }

    /**
     * Returns the full path to a file in /files/ folder based on its filename
     *
     * @param string $file_name file name to get the full path from
     */
    public function getFilePath($file_name) {
      $file_path = $this->config['file_path'].'/'.$file_name;
      if (file_exists($file_path.'/'.$this->config['default_doc']))
	$file_path = $this->config['file_path'].'/'.$file_name.'/'.$this->config['default_doc'];

      return $file_path;
    }

    protected function viewDir($file_path) {
      $PicoWiki = $this;
      $doc_view = false;

      $lst = [];
      $dp = @opendir($file_path);
      if ($dp !== false) {
	while (false !== ($fn = readdir($dp))) {
	  if ($fn == $PicoWiki->config['default_doc']) {
	    $doc_view = true;
	    continue;
	  }
	  if ($fn[0] == '.') continue;
	  $lst[] = $fn;
	}
	closedir($dp);
      }
      natsort($lst);

      require(__DIR__ . '/backend/templates/folder.html');
    }
    /**
     * Outputs the templates and files
     * You can use file_get_contents($file_path) instead of require to disable running PHP code in .md files
     *
     * @param string $file_path full path to the Markdown file
     */
    protected function view($file_path) {
      header('Accept-Ranges: bytes');

      if (basename($file_path) == $this->config['default_doc']
	  && isset($_GET['tools'])
	  && $_GET['tools']) {
	$this->viewDir(dirname($file_path));
	return;
      }

      $ext = pathinfo($file_path)['extension'] ?? '';
      if (isset($this->handlers[$ext])) {
	$obj = $this->handlers[$ext];

	$this->source = $this->fileGetContents($file_path);
	//~ echo "<pre>";print_r($PicoWiki);echo "</pre>";
	$this->html = substr($this->source,$this->fileGetOffset($file_path));
	$this->html = $this->event('view_before', $this->html);

	$this->html = call_user_func_array([$obj,'render'],[$this,$this->html]);

	$this->html = $this->event('view_after', $this->html);

	call_user_func_array([$obj,'view'],[$this]);
	return;
      }
      if (is_dir($file_path)) {
	$this->viewDir($file_path);
	return;
      }

      ### Remove headers that might unnecessarily clutter up the output
      header_remove('Cache-Control');
      header_remove('Pragma');

      $mime = mime_content_type($file_path);
      if ($mime === false) $mime = 'application/octet-stream';
      header('Content-Type: '.$mime);
      header('Content-Disposition: filename="'
		. basename($file_path) . '"');

      ### Default to send entire file
      $byteOffset = 0;
      $byteLength = $fileSize = filesize($file_path);

      ### Parse Content-Range header for byte offsets, looks like "bytes=11525-" OR "bytes=11525-12451"
      if( isset($_SERVER['HTTP_RANGE']) && preg_match('%bytes=(\d+)-(\d+)?%i', $_SERVER['HTTP_RANGE'], $match) ) {
	### Offset signifies where we should begin to read the file
	$byteOffset = (int)$match[1];

	### Length is for how long we should read the file according to the browser, and can never go beyond the file size
	if( isset($match[2]) ){
	  $finishBytes = (int)$match[2];
	  $byteLength = $finishBytes + 1;
	} else {
	  $finishBytes = $fileSize - 1;
	}
	$cr_header = sprintf('Content-Range: bytes %d-%d/%d', $byteOffset, $finishBytes, $fileSize);

	header('HTTP/1.1 206 Partial content');
	header($cr_header);  ### Decrease by 1 on byte-length since this definition is zero-based index of bytes being sent
      }

      if ($byteOffset >= $byteLength) {
	http_response_code(416);
	die('Range outside resource size: '.$_SERVER['HTTP_RANGE']);
      }

      $byteRange = $byteLength - $byteOffset;

      header('Content-Length: ' . $byteRange);
      header('Expires: '. date('D, d M Y H:i:s', time() + 60*60*24*90) . ' GMT');

      $buffer = ''; 			### Variable containing the buffer
      $bufferSize = 1024 * 32;		### Just a reasonable buffer size
      $bytePool = $byteRange;		### Contains how much is left to read of the byteRange

      if(!($handle = fopen($file_path, 'r'))) die("Error reading: $file_path");
      if(fseek($handle, $byteOffset, SEEK_SET) == -1 ) die("Error seeking file");

      while( $bytePool > 0 ) {
	$chunkSizeRequested = min($bufferSize, $bytePool); ### How many bytes we request on this iteration

	### Try readin $chunkSizeRequested bytes from $handle and put data in $buffer
	$buffer = fread($handle, $chunkSizeRequested);

	### Store how many bytes were actually read
	$chunkSizeActual = strlen($buffer);

	### If we didn't get any bytes that means something unexpected has happened since $bytePool should be zero already
	if( $chunkSizeActual == 0 ) die('Chunksize became 0');

	### Decrease byte pool with amount of bytes that were read during this iteration
	$bytePool -= $chunkSizeActual;

	### Write the buffer to output
	print $buffer;

	### Try to output the data to the client immediately
	flush();
      }
    }
    /**
     * Handle post actions
     *
     * @param string $file_path full path to the Markdown file
     */
    protected function post($file_path)
    {
      if (empty($_POST['action'])) die("No action in POST");

      switch (strtolower($_POST['action'])) {
	case 'save':
	  $this->save($file_path);
	  break;
	case 'attach':
	  $this->attach($file_path);
	  break;
	default:
	  die('Unknown action: '.$_POST['action']);
      }
    }
    /**
     * Handle attach action
     */
    protected function attach($file_path) {
      if (!isset($_FILES['fileToUpload'])) die("Invalid FORM response");
      $fd = $_FILES['fileToUpload'];
      if (isset($fd['size']) && $fd['size'] == 0) die("Zero file submitted");
      if (isset($fd['error']) && $fd['error'] != 0) die('Error: '.$fd['error']);
      if (empty($fd['name']) || empty($fd['tmp_name'])) die("No file uploaded");

      $fname = self::sanitize(basename($fd['name']));

      if (basename($file_path) == $this->config['default_doc'])
	$file_path = dirname($file_path);

      $pi = pathinfo($file_path);
      $dir_path = $pi['dirname'].'/'.$pi['filename'];

      if (file_exists($dir_path.'/'.$fname)) die("$fname: File already exists");

      echo '<pre>';
      echo "url: $this->url\n";
      echo "dir_path: $file_path\n";
      echo "fname: $fname\n";

      print_r($_POST);
      print_r($_FILES);

      if (!is_dir($dir_path)) {
	if (mkdir($dir_path,0777,true) === false)
	  die("Unable to mkdir: $dir_path");
      }
      if (!move_uploaded_file($fd['tmp_name'],$dir_path.'/'.$fname))
	die("Error saving uploaded file");

      header('Location: '.$_SERVER['REQUEST_URI']);
    }
    /**
     * Handle save action
     */
    protected function save($file_path) {
      echo '<pre>';

      if (empty($_POST['payload'])) die("No payload!");
      if ($this->config['unix_eol']) {
	$payload = str_replace("\r", '', $_POST['payload']);
      } else {
	$payload = $_POST['payload'];
      }
      $payload = $this->event('payload_pre', $payload);

      $ext = pathinfo($file_path)['extension'] ?? '';
      if (isset($this->handlers[$ext])) {
	$obj = $this->handlers[$ext];

	list($meta,$body) = call_user_func_array([$obj,'payload_before'],[$this,$file_path,$payload]);

	$fattr = [ 'file-path' => $file_path ];
	if (!file_exists($file_path)) $fattr['created'] = true;
        list($meta,) = $this->event('meta_write_before', [$meta,$fattr]);

	$payload = call_user_func_array([$obj,'payload_after'],[$this,$meta,$body]);
      }

      $payload = $this->event('payload_post', $payload);

      if (!is_dir(dirname($file_path))) {
	if (mkdir(dirname($file_path),0777,true) === false)
	  die("Unable to create: $file_path");
      }
      //~ echo "FILE_PATH: $file_path\n";
      //~ echo htmlspecialchars($payload);

      if (file_put_contents($file_path,$payload) === false)
	die("Error saving to: $file_path");

      header('Location: '.$_SERVER['REQUEST_URI']);

      echo '<a href="'.$_SERVER['REQUEST_URI'].'">OK!</a>';

      echo '<pre>';
      echo "file_path: $file_path\n";
      print_r($_POST);
      echo '</pre>';
    }

    /**
     * Finds .php files inside the /plugins/ folder, stores the list and initializes them
     */
    protected function loadPlugins()
    {
        $this->plugin_list = glob( __DIR__ . '/backend/plugins/*.php');
        foreach ($this->plugin_list as $plugin_file) {
            $class_name = pathinfo($plugin_file)['filename'];
            require_once $plugin_file;
            call_user_func_array([ $class_name, 'load'], [$this] );
        }
    }

    /**
     * Attach (or remove) multiple callbacks to an event and trigger those callbacks when that event is called.
     * https://github.com/Xeoncross/micromvc/blob/master/Common.php#L15
     *
     * @param string $event name
     * @param mixed $value the optional value to pass to each callback
     * @param mixed $callback the method or function to call - FALSE to remove all callbacks for event
     */
    public function event($event, $value = NULL, $callback = NULL) {
      // Adding or removing a callback?
      if ($callback !== NULL) {
	  if ($callback) {
	      $this->events[$event][] = $callback;
	  } else {
	      unset($this->events[$event]);
	  }
      } else {
	if (isset($this->events[$event])) { // Fire a callback
	  foreach($this->events[$event] as $function) {
	      $value = call_user_func($function, $value);
	  }
	}
	return $value;
      }
    }

    /**
     * Register a media handler for the given file extension
     *
     * @param string $ext file extension
     * @param mixed $callback object or classname used for media handling.
     */
    public function handler($ext, $callback) {
      $this->handlers[$ext] = $callback;
    }
    /* Adding theme support */
    protected function themeDir() {
      if (is_dir($this->config['theme'])) {
	$theme_dir = $this->config['theme'];
      } elseif (is_dir('static/themes/'.$this->config['theme'])) {
	$theme_dir = 'static/themes/'.$this->config['theme'];
      } elseif (is_dir('static/'.$this->config['theme'])) {
	$theme_dir = 'static/'.$this->config['theme'];
      } else {
	// Theme not found!
	return null;
      }
      return str_replace(__DIR__.'/','',$theme_dir);
    }

    protected function themeFile($path) {
      $path = str_replace(__DIR__.'/','',$path);
      if (empty($this->config['theme'])) return $path;
      $theme_dir = $this->themeDir();
      if (empty($theme_dir)) return $path;

      # "<p>theme_dir: $theme_dir</p>";
      # "<p>path: $path</p>";

      $src = '/'.$path;
      $src = str_replace('/js/','/',$src);
      $src = str_replace('/css/','/',$src);
      $src = str_replace('/static/','/',$src);
      $src = str_replace('/backend/plugins/','/',$src);
      $src= ltrim($src,'/');
      # "<p>src: $src</p>";

      $pp = pathinfo($src);
      $subs = [];
      if (isset($pp['extension'])) $subs[] = '/'.$pp['extension'].'/';
      $subs[] = '/';
      $pkg = str_replace('/','-',$pp['dirname']);

      foreach ($subs as $sd) {
	if ($pkg == '.') {
	  if (file_exists($theme_dir.$sd.$pp['basename']))
	    return $theme_dir.$sd.$pp['basename'];
	} else {
	  if (file_exists($theme_dir.'/'.$pkg.$sd.$pp['basename']))
	    return $theme_dir.'/'.$pkg.$sd.$pp['basename'];
	  if (file_exists($theme_dir.$sd.$pkg.'-'.$pp['basename']))
	    return $theme_dir.'/'.$pkg.$sd.$pp['basename'];
	}
      }
      # '<p>'.__FILE__.','.__LINE__.':</p>';
      return $path;
    }
    public function css($path,$use_link=true) {
      $path = $this->themeFile($path);
      if ($use_link && explode('/',$path,2)[0] == 'static') {
	// Can be included
	return '<link rel="stylesheet" href="'.$this->mkUrl($path).'">'.PHP_EOL;
      }
      return '<style>'.PHP_EOL.
	      file_get_contents($path).PHP_EOL.
	      '</style>'.PHP_EOL;
    }
    public function jsCode($path) {
      $path = $this->themeFile($path);
      if (explode('/',$path,2)[0] == 'static') {
	// If begins wiht static we can include them...
	return '<script src="'.$this->mkUrl($path).'"></script>'.PHP_EOL;
      }
      // Must embed the javascript inside
      return '<script>'.PHP_EOL.
	      file_get_contents($path).PHP_EOL.
	      '</script>'.PHP_EOL;
    }
    public function themeCss() {
      if (empty($this->config['theme'])) return;
      $theme_dir = $this->themeDir();
      if (empty($theme_dir)) return;
      $css = $theme_dir.'/'.$this->config['theme'].'.css';
      if (!file_exists($css)) return;
      return '<link rel="stylesheet" href="'.$this->mkUrl($css).'">'.PHP_EOL;
    }
    /*
     * Hanlde toolbar customizations
     */
    protected function loadTools() {
      $PicoWiki = $this;
      $tools = [];
      $tools[] = [
	  'tag' => 'a',
	  'text' => '<img src="'.$PicoWiki->mkUrl('static/nanowiki-favicon.png').'" height=24 width=24>',
	  'target' => '_blank',
	  'title' => 'NanoWiki github page',
	  'href' => $PicoWiki->config['nanowiki_url']
	];
      $tools[] = [ 'include' => 'backend/templates/_filelist.html' ];
      $tools[] = [ 'include' => 'backend/templates/_filetools.html' ];

      $fp = $this->getFilePath($this->url);
      if ($this->isWritable($fp)) {
	$tools[] = [
	    'tag' => 'a',
	    'href' => 'javascript:;',
	    'id' => 'tb-tools-show-source',
	    'class' => 'topbar-default-hidden topbar-right',
	    'title' => 'Show source',
	    'text' => ' &#x1F6C8; source',
	  ];
	$tools[] = [
	    'tag' => 'a',
	    'href' => 'javascript:;',
	    'id' => 'tb-tools-show-content',
	    'class' => 'topbar-default-hidden topbar-right',
	    'title' => 'Display content',
	    'text' => '&#x1F441; view',
	  ];
	$tools[] = [
	    'tag' => 'a',
	    'href' => 'javascript:;',
	    'id' => 'tb-tools-save',
	    'class' => 'topbar-default-hidden topbar-right',
	    'title' => 'Save document',
	    'text' => '&#x1F4BE; save',
	  ];
      }
      $this->toolbar = $tools;
    }
    public function renderTools($tools = null) {
      if ($tools == null) $tools = $this->toolbar;

      $PicoWiki = $this;
      $ret = '';
      foreach ($tools as $tbi) {
	//~ echo '<pre>'.print_r($tbi,true).'</pre>';
	if (isset($tbi['include'])) {
	  ob_start();
	  include($tbi['include']);
	  $ret .= ob_get_clean();
	} elseif (isset($tbi['tag'])) {
	  $ret .= '<'. $tbi['tag'];
	  foreach ($tbi as $k=>$v) {
	    if ($k == 'text') continue;
	    $ret .= ' ' . $k . '="'.$v.'"';
	    if ($k == 'title' && empty($tbi['alt'])) $ret .= ' alt="'.$v.'"';
	  }
	  $ret .= '>';
	  if (isset($tbi['text'])) {
	    if (is_array($tbi['text'])) {
	      $ret .= $this->renderTools($tbi['text']);
	    } else {
	      $ret .= $tbi['text'];
	    }
	  }
	  $ret .= '</'.$tbi['tag'].'>'.PHP_EOL;
	}
      }
      //~ echo '<pre>'.htmlspecialchars($ret).'</pre>';
      return $ret;
    }
}

$PicoWiki = new NanoWiki($config);
$PicoWiki->run(@$_GET['url']);
