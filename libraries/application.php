<?
require_once LIBRARIES_PATH.'sources.php';
require_once LIBRARIES_PATH.'templator.php';

abstract class module {
	protected $app;
	protected $data;

	function __construct(application $app,$data=null) {
		$this->app=$app;
		$this->data=$data;
	}
	
	public function get_param($param) {
		$const='static::mod_'.$param;
		if (defined($const)) {
			return constant($const);
		} else {
			return null;
		}
	}

	public function __get($key) {
		return $this->app->$key;
	}

	abstract function body();

	public function execute() {
		$ts=microtime(true);
		$this->body();
		$t=microtime(true)-$ts;
		//echo 'Module "'.get_class($this).'" executed in '.$t.' s.';
	}

	public function output() {
		ob_start();
		$ts=microtime(true);
		$this->body();
		$t=microtime(true)-$ts;
		$ob=ob_get_contents();
		ob_end_clean();
		//echo 'Module "'.get_class($this).'" executed in '.$t.' s.';
		return $ob;
	}
}

abstract class module_tpl extends module {
	protected $tpl;
	
	protected $abort=false;

	function __construct(application $app,$data=null) {
		parent::__construct($app,$data);

		$templ=$this->get_param('tpl');
		if (is_null($templ)) {
			$cls=get_class($this);
			if (substr($cls,0,4)=='mod_') $templ=substr($cls,4);
			else $templ='main';
		}
		$this->tpl=new template($templ);
		$this->init();
	}
	
	protected function abort() {
		$this->abort=true;
	}

	abstract protected function init();
	abstract protected function finite();

	abstract protected function priv();
	
	protected function add_sub_pc($module,$data=null) {
		$this->tpl->set('sub_'.$module,$this->app->drun($module,$data));
	}

	protected function add_sub($module,$data=null) {
		$this->tpl->set('sub_'.$module,new tplclsr(function() use ($module,$data) {return $this->app->drun($module,$data);}));
	}

	public function execute() {
        if ($this->priv()) {
			$this->init();
			$this->ob_process($this->output());
			if ($this->abort) return;
			$this->finite();
			$this->tpl->output();
        } else {
        	$this->auth_failed();
        }
	}
	
	protected function ob_process($ob) {
		//$this->tpl->cont=$ob;
		echo $ob;
	}

	protected function auth_failed() {
		$this->app->err403();
	}
}

class http {
	static function relocate($uri) {
		header('Location: '.$uri);
	}

	static function err404() {
		header("HTTP/1.0 404 Not Found");
?><!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html><?
	}

	static function err403() {
		header("HTTP/1.0 403 Forbidden");
?><!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>403 Forbidden</title>
</head><body>
<h1>Forbidden</h1>
</body></html><?
	}
}

class configs {
	protected $conf=array();
}

class application_base {
	protected $objs=array();
	protected $tokens;

	function __construct() {
		$this->phpsetup();
        $this->objinit();
	}

	protected function phpsetup() {
		setlocale(LC_ALL,'ru_UA.utf8');
		date_default_timezone_set('Europe/Kiev');
		mb_internal_encoding('UTF-8');
	}

	protected function objinit() {
		$this->objs['dbp']=new DBP;
		$this->objs['src']=new sourceset(array('post','get','uri'));
		$this->objs['http']=new http;
	}

	public function __get($key) {
		if (isset($this->objs[$key])) return $this->objs[$key];
		trigger_error('Application knows nothing about object "'.$key.'".',E_USER_WARNING);
	}

	protected function mod_create($module,$data=null) {
		$cmod='mod_'.$module;
		if (preg_match('/^[-_A-Za-z0-9]*$/',$module)) {
			if (file_exists(MODULES_PATH.$module.'.php')) {
				require_once(MODULES_PATH.$module.'.php');
				if (class_exists($cmod)) {
					if ($data) $mod=new $cmod($this,$data);
					$mod=new $cmod($this,$data);
					if (($mod instanceof module)) {
						return $mod;
					}
				}
			}
		}
		return null;
	}

	protected function check_type(module $module,$type) {
		if (is_null($type)) return true;
		foreach ($type as $param=>$cond) {
            $pval=$module->get_param($param);
            if (!is_null($pval)) {
            	if ($pval!=$cond) return false;
            }
		}
		return true;
	}

	public function run($module,$data=null,array $type=null) {
		$mod=$this->mod_create($module,$data);
		if (is_null($mod))  return false;
		if (!$this->check_type($mod,$type)) return false;
		if (!is_null($mod)) {
			$mod->execute();
			return true;
		}
		return false;
	}

	public function drun($module,$data=null,array $type=null) {
		$mod=$this->mod_create($module,$data);
		if (is_null($mod))  return null;
		if (!$this->check_type($mod,$type)) return null;
		if (!is_null($mod)) {
			return $mod->output();
		}
		return null;
	}

	public function err404() {
		$this->http->err404();
	}

	public function err403() {
		$this->http->err403();
	}
}
?>