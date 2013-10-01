<?
interface source {
	public static function def($key);
	public static function get($key);
	public static function get_all();
}

abstract class source_base {
	public function __get($key) {
		if (static::def($key)) return static::get($key);
		else return null;
	}

	public static function eq($key,$val) {
		if (static::def($key)) {
			return (static::get($key)==$val);
		}
		return false;
	}

	public static function neq($key,$val) {
		if (static::def($key)) {
			return (static::get($key)!=$val);
		}
		return false;
	}

	public static function num($key) {
		if (static::def($key)) {
			return (is_numeric(static::get($key)));
		}
		return false;
	}
}

class src_post extends source_base implements source {
	public static function def($key) {
		return isset($_POST[$key]);
	}

	public static function get($key) {
		return isset($_POST[$key])?$_POST[$key]:null;
	}

	public static function get_all(){
		return $_POST;
	}
}

class source_post extends src_post {}

class src_get extends source_base implements source {
	public static function def($key) {
		return isset($_GET[$key]);
	}

	public static function get($key) {
		return $_GET[$key];
	}

	public static function get_all(){
		return $_GET;
	}
}

class src_uri extends source_base implements source {
	protected static $data=null;

	protected static function parse() {
		$data=array();
		$data['full']=$_SERVER['REQUEST_URI'];
		$uris=explode('/',$_SERVER['REQUEST_URI']);
		$i=-1;
		foreach ($uris as $val) {
			$i++;
			if ($i==0) continue;
			$data['uri_'.$i]=$val;
		}
		$l=&$data['uri_'.$i];
		if (strpos($l,'?')!==false) {
			list($last,$param)=explode('?',$l,2);
			$l=$last;
			$data['uri_np']=$param;
		}
		if (preg_match('/(.*)\.html?$/',$l,$mchs)) {
			$data['uri_lb']=$mchs[1];
		}
		$data['uri_n']=$i;
		$data['uri_l']=$l;
		return $data;
	}

	protected static function make_data() {
		if (is_null(self::$data)) self::$data=self::parse();
	}

	public static function def($key,$reg=null) {
		self::make_data();
		if (!is_null($reg)) {
			return (isset(self::$data[$key]) && preg_match($reg,self::$data[$key]));
		}
		return isset(self::$data[$key]);
	}

	public static function get($key,$reg=null) {
		self::make_data();
		if (!is_null($reg)) {
			if (isset(self::$data[$key]) && preg_match($reg,self::$data[$key],$mchs)) {
				if (count($mchs)==1) {
					return $mchs[0];
				} elseif (count($mchs)==2) {
					return $mchs[1];
				} else {
					return $mchs;
				}
			}
		}
		return isset(self::$data[$key])?self::$data[$key]:null;
	}

	public static function get_all(){
		self::make_data();
		$tmp=self::$data;
		unset($tmp['full']);
		unset($tmp['uri_n']);
		unset($tmp['uri_l']);
		if (isset($tmp['uri_lb'])) unset($tmp['uri_lb']);
		unset($tmp['uri_np']);
		return $tmp;
	}
}

class src_cookie extends source_base implements source {
	public static function def($key) {
		return isset($_COOKIE[$key]);
	}

	public static function get($key) {
		return isset($_COOKIE[$key])?$_COOKIE[$key]:null;
	}

	public static function get_all(){
		return $_COOKIE;
	}
}

class sourceset {
	private $srcs=array();

	function __construct($srcs=false) {
		if ($srcs) {
			foreach ($srcs as &$src) {
				$cls='src_'.$src;
				if (class_exists($cls)) {
					$this->addsrc($src,new $cls());
				}
			}
		}
	}

	public function __get($key) {
		if (isset($this->srcs[$key])) {
			return $this->srcs[$key];
		}
		return null;
	}

	public function addsrc($key,source $src) {
		$this->srcs[$key]=$src;
	}

	public function def($key,$source=false) {
		if ($source) {
			$src=$this->srcs[$source];
			return $src->def($key);
		} else {
			$def=false;
			foreach ($this->srcs as &$src) {
				if ($src->def($key)) {
					$def=true;
					break;
				}
			}
			return $def;
		}
	}

	public function get($key,$source=false) {
    	if ($source) {
			$src=$this->srcs[$source];
			return $src->get($key);
		} else {
			$def=false;
			foreach ($this->srcs as &$src) {
				if ($src->def($key)) {
					return $src->get($key);
					break;
				}
			}
			return false;
		}
	}

	public function get_all($source=false) {
		if ($source) {
			$src=$this->srcs[$source];
			return $src->get_all();
		} else {
			$arr=array();
			foreach ($this->srcs as &$src) {
				$arr=array_merge($arr,$src->get_all());
			}
			return $arr;
		}
	}
}
?>