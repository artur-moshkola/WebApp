<?
interface i_auth {
	public static function get_uid();
	public static function get_pl();
	public static function check_priv($lv);
}

abstract class auth implements i_auth {
	protected static $uid=null;
	protected static $pl=self::PL_GUEST;

	const UID_GUEST=10;
	
	const PL_ROOT=0;
	const PL_ADMIN=1;
	const PL_USER=16;
	const PL_GUEST=128;

	function __construct() {
		static::authenticate();
	}

	function __get($key) {
		switch ($key) {
			case 'uid':
				return $this->get_uid();
			case 'pl':
				return $this->get_pl();
		}

		trigger_error('There\'s no parameter "'.$key.'" in "'.get_class($this).'"',E_USER_ERROR);
	}

	public static function get_pl() {
		return static::$pl;
	}

	public static function check_priv($lv) {
		return (static::$pl<=$lv);
	}

	public static function get_uid() {
		return static::$uid;
	}

	abstract public static function authenticate();
}

abstract class auth_lp_base extends auth implements i_auth {
	abstract protected static function db_check_uid($uid);
	abstract protected static function db_check_lp($login,$phash);
	abstract protected static function db_check_cookie($uid,$phash,$chash);
	abstract protected static function db_set_cookie($uid,$ip,$chash);

	public static function authenticate() {
		if (isset($_SESSION['uid'])) {
			$user=static::authenticate_uid($_SESSION['uid']);
		} elseif (isset($_COOKIE['auth'])) {
			$user=static::authenticate_cookie($_COOKIE['auth']);
		} else {
			static::unsuccess();
		}
	}
	
	private static function success($user) {
		static::$uid=$user['uid'];
		static::$pl=$user['pl'];
			
		$_SESSION['uid']=static::$uid;
	}
	
	private static function unsuccess() {
		static::$uid=auth::UID_GUEST;
		static::$pl=auth::PL_GUEST;
			
		if (isset($_SESSION['uid'])) unset($_SESSION['uid']);
	}

	private static function authenticate_uid($uid) {
        $user=static::db_check_uid($uid);
		
		if ($user) static::success($user);
		else static::unsuccess();

        return $user;
	}

	private static function authenticate_lp($login,$phash) {
		$user=static::db_check_lp($login,$phash);
		
		if ($user) static::success($user);
		else static::unsuccess();

        return $user;
	}

	public static function authenticate_cookie($cookie) {
		$ca=explode('-',$cookie);
		if ((count($ca)==2)&&is_numeric($ca[0])&&preg_match('/^[0-9a-f]{64}$/',$ca[1])) {
			$uid=(int)$ca[0];
			$sh=md5($ca[1]);
			$ph=substr($ca[1],0,32);
		} else {
			return false;
		}

		$user=static::db_check_cookie($uid,$ph,$sh);
        
		if ($user) static::success($user);
		else static::unsuccess();

        return $user;
	}

	public static function login($login,$phash) {
        $user=static::authenticate_lp($login,$phash);
		
		if ($user) {
        	static::setcook($phash);

        	return true;
        } else {
        	return false;
        }
	}

	private static function setcook($phash) {
        if (is_null(static::$uid)) return;

        $hex=array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');
        $salt=array_fill(0,32,'0');
        foreach ($salt as &$hnum) $hnum=$hex[rand(0,15)];

        $salt=implode($salt);
        $uid=static::$uid;
        $cook=$uid.'-'.$phash.$salt;
        $chash=md5($phash.$salt);

        if (setcookie('auth', $cook, time()+32435950, '/')) {
	        static::db_set_cookie($uid,$_SERVER['REMOTE_ADDR'],$chash);
		}
	}

	public function logout() {
		static::unsuccess();

		setcookie('auth', '', 0, '/');
	}

	
	
	abstract protected static function db_prec_get_token($login,&$uid);
	abstract protected static function db_prec_kill_token($tid);
	abstract protected static function db_prec_check_token($token);
	abstract protected static function db_change_password($uid,$phash);
	
	public static function prec_get_token($login,&$uid) {
		$token=static::db_prec_get_token($login,$uid);

		return $token;
	}
	
	public static function prec_kill_token($tid) {
		static::db_prec_kill_token($tid);
	}

	public static function prec_check_token($token) {
		$token=static::db_prec_check_token($token);

		return $token;
	}

	public static function change_password($phash,$uid=false) {
		if ($uid!==false) {
			$cuid=$uid;
		} elseif (!is_null($this->uid)) {
			$cuid=$this->uid;
		} else {
			return;
		}

		static::db_change_password($cuid,$phash);
	}
}
?>