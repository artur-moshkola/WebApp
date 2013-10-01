<?
class control_text extends control {
	public function html_input(i_field $fld=null) {
		$att=array('type'=>'text');
		$att['name']=$this->descr->name;
		$att['id']=$this->descr->name;
		$att=array_merge($att,$this->css_a());
		
		if (!is_null($this->descr->maxlen)) {
			$att['maxlength']=$this->descr->maxlen;
		}

		if(is_null($fld)) {
			$val='';
		} else {
			if ($fld->is_def()) {
				$val=$fld->value('plain');
			} else {
				$val=$fld->get_invalid();
			}
		}
		$att['value']=$val;
		
		return $this->html_tag('input',$att);
	}
}

class control_combo extends control {
	private function get_list() {
		switch ($this->descr->source) {
			case 'dbproc':
				return $this->get_list_dbproc();
			default:
				trigger_error('Combo Control Descriptor error. Invalid Source.',E_USER_ERROR);
				return array();
		}
	}
	
	
	
	private function get_list_dbproc() {
		$proc_name=$this->descr->proc_name;
		$proc_param=is_null($this->descr->proc_param)?array():$this->descr->proc_param->getArray();
		
		if (is_null($proc_name)) {
			trigger_error('Combo Control Descriptor error. Source Procedure name is required.',E_USER_ERROR);
			return array();
		}
		$objclass='fldset_comb';
		if(!is_null($this->descr->objclass))
			$objclass=$this->descr->objclass;

		$arr = array();
		$c= new fldset_collection($objclass);
		$c->loadByProc($proc_name,$proc_param);
		//dbo::Exec("call ".$proc_name,$proc_param,$arr);
		return $c;
	}
	
	public function set_proc_param($name,$val){
		if(!is_null($name)&&is_string($name)&&$name!==''){
			//Нужна проверка на наличие ключа в словаре.
			$this->descr->proc_param->$name=$val;
			//var_dump($this->descr->proc_param->$name,$val);
		}
	}
	public function html_input(i_field $fld=null) {
		$ex=$this->css();
		$name=$this->descr->name;
		
		$keyfld="key";
		$valfld="val";
		if(!is_null($this->descr->keyfld)){
			$keyfld=$this->descr->keyfld;
		}
		if(!is_null($this->descr->keyfld)){
			$valfld=$this->descr->valfld;
		}
		//var_dump($keyfld,$valfld);
		$html="<select name=\"$name\" id=\"$name\"$ex>\n";
        foreach ($this->get_list() as $itm) {
			$sel = '';
			//var_dump($itm);
			if(!is_null($fld)&&$fld->is_def()&&$fld->value('plain')==$itm->$keyfld)
				$sel='selected';
			$html.="<option $sel value=\"".$itm->$keyfld."\">".$itm->$valfld."</option>";
		}
        $html.="</select>";

		return $html;
	}
}

class control_check extends control {
	public function html_input(i_field $fld=null) {
		$att=array('type'=>'checkbox');
		$att['name']=$this->descr->name;
		$att['id']=$this->descr->name;
		$att=array_merge($att,$this->css_a());
		
		if (!is_null($this->descr->maxlen)) {
			$att['maxlength']=$this->descr->maxlen;
		}

		if(is_null($fld)) {
			$val=false;
		} else {
			if ($fld->is_def()) {
				$val=$fld->value('plain');
			} else {
				$val=$fld->get_invalid();
			}
		}
		
		if ($val) {
			$att['checked']=null;
		}
		
		return $this->html_tag('input',$att);	
	}
}
?>