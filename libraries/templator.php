<?
class tplclsr {
	protected $clsr;
	protected $data;
	protected $res=null;
	
	function __construct(Closure $clsr, $data=null) {
		$this->data=$data;
		$this->clsr=$clsr;
	}
	
	public function get() {
		$clsr=$this->clsr;
		if (is_null($this->res)) $this->res=$clsr($this->data);
		return $this->res;
	}
	
	public function __toString() {
		return $this->get();
	}
}

class template {
	protected $data=array();
    protected $template=null;
	protected $path=null;
	
	protected $tplsrc=null;
	
	protected $flds=array();

	function __construct($template=null,$path=null) {
		$this->set_path($path);
		if (!is_null($template)) $this->set_template($template);
	}

	private function out($key) {
		echo $this->data[$key];
	}

	private function sub($tpl) {
		foreach ($this->data as $k => &$v) {
			$$k=&$v;
		}
		//extract($this->data, EXTR_PREFIX_SAME | EXTR_PREFIX_INVALID | EXTR_REFS, 'var_');
		require($this->path.'subs/'.$tpl.'.php');
	}

	public function set($key,$val) {
		$this->data[$key]=$val;
	}

	public function __set($key,$val) {
		$this->data[$key]=$val;
	}

	public function set_array($arr) {
		$this->data=array_merge($this->data,$arr);
	}

    public function set_path($path) {
		if (is_null($path)) $this->path=TEMPLATES_PATH;
		else $this->path=$path;
	}
	
	public function set_template($template) {
    	if (file_exists($this->path.$template.'.php')){
    		$this->template=$this->path.$template.'.php';
    	} else {
    		$this->template=null;
    	}
		$this->tplsrc=$template;
    }

	public function output($template=null,$craw=null) {
		if (!is_null($template)) {
			$this->set_template($template);
		}
		if (is_null($this->template)) {
			if (!is_null($this->tplsrc))
				echo '<b>There\'s no template file: <u>'.$this->tplsrc.'</u>.</b><br/>'."\n";
			else
				echo '<b>Template file is not specified.</b><br/>'."\n";
			echo '<b>Raw template data:</b><br/>'."\n";
			echo str_replace(array("\r\n","\n"),"<br>\n",print_r($this->data, true));
			return;
		}
		
		if (is_null($craw)) $craw=false;
		if ($craw) $this->out_raw();
		
		foreach ($this->data as $k => &$v) {
			$$k=&$v;
		}
		//extract($this->data, EXTR_PREFIX_SAME | EXTR_PREFIX_INVALID | EXTR_REFS, 'var_');
		set_error_handler(array($this,'err_handler'),E_NOTICE | E_WARNING);
		require($this->template);
		restore_error_handler();
	}

	public function execute($template=null) {
		ob_start();
		$this->output($template,false);
		$html=ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
	public function get_fields($template=null) {
		if (!is_null($template)) {
			$this->set_template($template);
		}
		if (is_null($this->template)) {
			if (is_null($this->tplsrc)) trigger_error('There\'s no template file: '.$this->tplsrc.'.');
			else trigger_error('Template file is not specified.');
		}
		
		set_error_handler(array($this,'err_handler_flds'),E_NOTICE);
		ob_start();
		require($this->template);
		ob_end_clean();
		restore_error_handler();
		
		$flds=$this->flds;
		$this->flds=array();
		
		return $flds;
	}
	
	protected function out_raw() {
		echo "<!--\n";
		print_r($this->data);
		echo "-->\n\n";
	}
	
	protected function err_handler($errno,$errstr,$errfile,$errline,$errcontext) {
		//var_dump($errfile,$this->template);
		if ($errno==8) {
			if (preg_match('/^Undefined variable: (.*)$/',$errstr,$mchs)) {
				echo '== '.$mchs[1].' ==';
				return true;
			}
		} elseif ($errno==2) {
			echo '[[ ARRAY EXPECTED ]]';
			return true;
		}
		return false;
	}
	
	protected function err_handler_flds($errno,$errstr) {
		if ($errno==8) {
			if (preg_match('/^Undefined variable: (.*)$/',$errstr,$mchs)) {
				$this->flds[]=$mchs[1];
				return true;
			}
		}
		return false;
	}
}
?>