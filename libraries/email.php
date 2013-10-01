<?
define('CRLF',"\r\n");

abstract class email {
	protected $to;
	protected $from;

	protected $headers=array();
	protected $message='';

	function __construct($to=false,$from=false) {
		if ($to!==false)
			$this->set_to($to);

		if ($from!==false)
			$this->set_from($from);
	}

	public function set_to($address,$name=false) {
		if ($name) {
			$to='=?UTF-8?B?'.base64_encode($name).'?='.' <'.$address.'>';
		} else {
			$to='<'.$address.'>';
		}
        $this->to=$to;
		//$this->header_add('To',$to);
	}

	public function set_from($address,$name=false) {
		$this->from=$address;

		if ($name) {
			$from='=?UTF-8?B?'.base64_encode($name).'?='.' <'.$address.'>';
		} else {
			$from='<'.$address.'>';
		}

		$this->header_add('From',$from);
	}

	public function subject_set($subj) {
        $subj='=?UTF-8?B?'.base64_encode($subj).'?=';
		$this->header_add('Subject',$subj);
	}

	public function message_set($msg) {
		//$msg='=?UTF-8?B?'.base64_encode($msg).'?=';
		$this->message=$msg;
	}

	public function header_add($frst,$sec=false,$replace=true) {
		if ($sec===false) {
			if (is_array($frst)) {
				foreach ($frst as $k=>$v) {
					$this->headers_add($k,$v);
				}
			} else {
				list($k,$v)=explode(':',$frst,2);
				$v=str_replace("\r",'',$v);
				$v=str_replace("\n",'',$v);
				$this->headers_add($k,$v);
			}
		} else {
			if ($replace || !isset($this->headers[$frst])) {
				$this->headers[$frst]=$sec;
			}
		}
	}

	protected function raw_headers() {
		$this->auto_headers();

		$hdrs='';
		foreach ($this->headers as $k=>$v) {
			$hdrs.=$k.': '.$v.CRLF;
		}

		return $hdrs;
	}

	protected function raw() {
		$msg=$this->raw_headers();
		$msg.=CRLF;
		$msg.=$this->message;
		$msg.=CRLF.'.';

		return $msg;
	}

	protected function auto_headers() {
		$this->header_add('Date',date('r'),false);
		$this->header_add('X-Mailer','AR2R PHP Mailer 0.1 Alpha',false);

		$this->header_add('MIME-Version','1.0',false);
		$this->header_add('Content-Type','text/plain; charset=UTF-8',false);
		$this->header_add('Content-Transfer-Encoding','8bit',false);
		$this->header_add('X-Priority','1',false);
	}

	abstract function send();
}

class email_test extends email {
	function send() {
		echo $this->raw();
	}
}

class email_sock extends email {
	protected $sc;

	protected $settings=array();

	function __construct($settings,$to=false,$from=false) {
		$this->settings=$settings;
		parent::__construct($to,$from);
	}

	protected function sock($cmd) {
		while ($ln=fgets($this->sc)) {
			//echo "< ".$ln;
			if (substr($ln,3,1)==' ') break;
		}

		//echo "> $cmd\n";
		//flush();
		fputs($this->sc,$cmd.CRLF);
	}

	function send() {
		$this->sc = fsockopen($this->settings['server'], $this->settings['port']);

	    $this->sock('HELO nigga');
	    $this->sock('MAIL FROM:<'.$this->from.'>');
	    $this->sock('RCPT TO:<'.$this->to.'>');
	    $this->sock('DATA');
	    $this->sock($this->raw());

	    fclose($this->sc);
	}
}

class email_sendmail extends email {
	function send() {
		mail($this->to,$this->headers['Subject'],$this->message,$this->raw_headers());
	}
}

class email_default extends email_test {}
?>