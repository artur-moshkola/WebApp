<?
class field_string extends field {
	protected function descr_init(descriptor $descr){
		$descr->set_defaults(array(
			'null'=>false,
			'not_blank'=>false,
			'blank_null'=>false,
			'not_numeric'=>false,
			'valid_email'=>false,
			'max_len'=>null,
			'max_chars'=>null,
			'min_chars'=>null,
			'exact_chars'=>null,
			'charset'=>null,
			'regexp'=>null
		));	
	}
	
	protected function edescr_init(descriptor $edescr) {
		$edescr->set_defaults(array(
			'blank'=>'String can not be blank',
			'max_len'=>'Byte length',
			'exact_chars'=>'String length',
			'max_chars'=>'Maximal string length',
			'min_chars'=>'Minimal string length'
		));
	}
	
	protected function validate($value,$format='plain') {
		$this->invalid=$value;
		$valid=true;
		
		if (is_null($value)) {
			if ($this->descr->null) $this->setnull();
			else $this->setval('');
			return true;
		}

		if ($this->descr->blank_null && ($value=='')) {
			$this->setnull();
			return true;
		}
		
		$chars=mb_strlen($value);
		$len=strlen($value);
	
		if ($this->descr->not_blank && ($value=='')) {
			$this->seterr(field::DT_ERR_VAL,'blank');
			$valid=false;
		}
		if (!is_null($this->descr->max_len) && ($len > $this->descr->max_len)) {
			$this->seterr(field::DT_ERR_VAL,'max_len');
			$valid=false;
		}
		if (!is_null($this->descr->exact_chars) && ($chars != $this->descr->exact_chars)) {
			$this->seterr(field::DT_ERR_VAL,'exact_chars');
			$valid=false;
		}
		if (!is_null($this->descr->max_chars) && ($chars > $this->descr->max_chars)) {
			$this->seterr(field::DT_ERR_VAL,'max_chars');
			$valid=false;
		}
		if (!is_null($this->descr->min_chars) && ($chars < $this->descr->min_chars)) {
			$this->seterr(field::DT_ERR_VAL,'min_chars');
			$valid=false;
		}
		
		if ($valid) {
			$this->setval($value);
			return true;
		} else {
			return false;
		}
	}
	
	protected function unformat_db($value) {
		return (string)$value;
	}
	
	protected function pdo_val(&$type) {
		$type=PDO::PARAM_STR;
		return $this->val;
	}
}

class field_integer extends field {
	protected function descr_init(descriptor $descr) {
		$descr->set_defaults(array(
			'null'=>false,
			'inv_null'=>false,
			'not_zero'=>false,
			'zero_null'=>false,
			'positive'=>false,
			'min'=>null,
			'max'=>null,
			'onfract'=>null,
			'format'=>null
		));
	}
	
	protected function edescr_init(descriptor $edescr) {
		$edescr->set_defaults(array(
			'inval'=>'Value is not a number',
			'fract'=>'Value is not an integer number',
			'zero'=>'Zero is not accepted',
			'negative'=>'Negative is not accepted',
			'max'=>'Maximal value',
			'min'=>'Minimal value'
		));
	}
	
	protected function validate($value,$format='plain') {
		$this->invalid=$value;
		
		if (is_null($value)) {
			if ($this->descr->null) {
				$this->setnull();
				return true;
			} else {
				$this->seterr(field::DT_ERR_TYP,'inval');
				return false;
			}
		}
		
		if ($this->descr->null) {
			if (($value===false) || ($value==='')) {
				$this->setnull();
				return true;
			}
			if (($this->descr->zero_null) || ($value==0)) {
				$this->setnull();
				return true;
			}
		}
		
		$val=false;
		if (is_numeric($value)) {
			if (floor($value)==$value) {
				$val=$value;
			} else {
				if ($this->descr->onfract) {
					switch ($this->descr->onfract) {
						case 'deny':
							$this->seterr(field::DT_ERR_TYP,'fract');
						break;
						case 'round':
							$val=round($value,0);
						break;
						case 'ceil':
							$val=ceil($value);
						break;
						case 'floor':
						default:
							$val=floor($value);
					}
				} else {
                    $val=floor($value);
				}
			}
			if ($val===false) {
				return false;
			} else {
				$valid=true;
				
				if ($this->descr->not_zero && ($val == 0)) {
					$this->seterr(field::DT_ERR_VAL,'zero');
					$valid=false;
				}
				if ($this->descr->positive && ($val < 0)) {
					$this->seterr(field::DT_ERR_VAL,'negative');
					$valid=false;
				}

				if (!is_null($this->descr->max) && ($val > $this->descr->max)) {
					$this->seterr(field::DT_ERR_VAL,'max');
					$valid=false;
				}
				if (!is_null($this->descr->min) && ($val < $this->descr->min)) {
					$this->seterr(field::DT_ERR_VAL,'min');
					$valid=false;
				}
				
				if ($valid) {
					$this->setval($value);
					return true;
				} else {
					return false;
				}				
			}
		} else {
			if ($this->descr->inv_null) {
				$this->setnull();
				return true;
			} else {
				$this->seterr(field::DT_ERR_TYP,'inval');
				return false;
			}
		}
	}
	
	protected function unformat_db($value) {
		return (int)$value;
	}
	
	protected function pdo_val(&$type) {
		$type=PDO::PARAM_INT;
		return $this->val;
	}
}

class field_real extends field {
	protected function descr_init(descriptor $descr) {
		$descr->set_defaults(array(
			'null'=>false,
			'inv_null'=>false,
			'not_zero'=>false,
			'zero_null'=>false,
			'positive'=>false,
			'min'=>null,
			'less'=>null,
			'max'=>null,
			'more'=>null,
			'accept_sep'=>null,
			'format'=>null
		));
	}
	
	protected function edescr_init(descriptor $edescr) {
		$edescr->set_defaults(array(
			'inval'=>'Value is not a number',
			'zero'=>'Zero is not accepted',
			'negative'=>'Negative is not accepted',
			'max'=>'Maximal value',
			'min'=>'Minimal value'
		));
	}
	
	protected function validate($value,$format='plain') {
		$this->invalid=$value;
		
		if (is_null($value)) {
			if ($this->descr->null) {
				$this->setnull();
				return true;
			} else {
				$this->seterr(field::DT_ERR_TYP,'inval');
				return false;
			}
		}
		
		if ($this->descr->null) {
			if (($value===false)) {
				$this->setnull();
				return true;
			}
			if (($this->descr->zero_null) || ($value==0)) {
				$this->setnull();
				return true;
			}
		}
		
		$val=str_replace(' ','',$value);
		$co=($this->descr->accept_sep=='comma');
		if ($this->descr->accept_sep!='point') {
			$comma=str_replace(',','.',$val,$cn);
			if (($cn>1)&&($co)) {
				//more than one comma
				$this->seterr(field::DT_ERR_TYP,'inval');
				return false;
			} else {
				str_replace('.','',$val,$pn);
				if (($pn>0) && $co) {
					//only comma is allowed, but point is used
					$this->seterr(field::DT_ERR_TYP,'inval');
					return false;
				}
				if (($cn==1)&&($pn==0)) {
					//one comma, no points
					$val=$comma;
				}
			}
		}
		
		if (is_numeric($val)) {
			$valid=true;
			
			if ($this->descr->not_zero && ($val == 0)) {
				$this->seterr(field::DT_ERR_VAL,'zero');
				$valid=false;
			}
			if ($this->descr->positive && ($val < 0)) {
				$this->seterr(field::DT_ERR_VAL,'negative');
				$valid=false;
			}

			if (!is_null($this->descr->max) && ($val > $this->descr->max)) {
				$this->seterr(field::DT_ERR_VAL,'max');
				$valid=false;
			}
			if (!is_null($this->descr->more) && ($val <= $this->descr->more)) {
				$this->seterr(field::DT_ERR_VAL,'min');
				$valid=false;
			}
			if (!is_null($this->descr->min) && ($val < $this->descr->min)) {
				$this->seterr(field::DT_ERR_VAL,'min');
				$valid=false;
			}
			if (!is_null($this->descr->less) && ($val >= $this->descr->less)) {
				$this->seterr(field::DT_ERR_VAL,'max');
				$valid=false;
			}
			
			if ($valid) {
				$this->setval($value);
				return true;
			} else {
				return false;
			}				
		} else {
			if ($this->descr->inv_null) {
				$this->setnull();
				return true;
			} else {
				$this->seterr(field::DT_ERR_TYP,'inval');
				return false;
			}
		}
	}
	
	protected function unformat_db($value) {
		return (float)$value;
	}
	
	protected function pdo_val(&$type) {
		$type=PDO::PARAM_INT;
		return $this->val;
	}
}

class field_datetime extends field {
	protected function descr_init(descriptor $descr) {
		$descr->set_defaults(array(
			'null'=>false,
			'inv_null'=>false,
			'future'=>false,
			'past'=>false,
			'makeval'=>true,
			'min'=>null,
			'max'=>null,
			'format'=>null
		));
	}
	
	protected function edescr_init(descriptor $edescr) {
		$edescr->set_defaults(array(
			'inval'=>'Value is not a datetime',
			'max'=>'Maximal value',
			'min'=>'Minimal value'
		));
	}
	
	protected function validate($value,$format='plain') {
		$this->invalid=$value;
		
		if (is_null($value) || ($value===false)) {
			if ($this->descr->null) {
				$this->setnull();
				return true;
			} else {
				$this->seterr(field::DT_ERR_TYP,'inval');
				return false;
			}
		}
		
		if (is_object($value) && ($value instanceof DateTime)) {
			$obj=$value;
		} else {
			if (is_numeric($value)) {
				$obj=new DateTime;
				$obj->setTimestamp($value);
			} else {
				if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$value)) $format='Y-m-d';
				elseif (preg_match('/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/',$value)) $format='d.m.Y';
				elseif (preg_match('/^[0-9]{1}\.[0-9]{2}\.[0-9]{4}$/',$value)) $format='j.m.Y';
				elseif (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$/',$value)) $format='Y-m-d H:i';
				elseif (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/',$value)) $format='Y-m-d H:i:s';
				else $format=null;
				
				if (is_null($format)) {
					$this->seterr(field::DT_ERR_TYP,'inval');
					return false;
				}
				
				$obj=DateTime::createFromFormat($format,$value);
				
				if ($obj===false) {
					$this->seterr(field::DT_ERR_TYP,'inval');
					return false;
				}
				
				if (!$this->descr->makeval) {
					$ng=$obj->format($format);
					
					if ($ng!=$value) {
						$this->seterr(field::DT_ERR_TYP,'inval');
						return false;
					}
				}
			}
		}
		
		$this->setval($obj);
		return true;
	}
	
	protected function unformat_db($value) {
		$d=DateTime::createFromFormat('Y-m-d H:i:s',$value);
		if (is_null($d)) $d=DateTime::createFromFormat('Y-m-d',$value);
		return $d;
	}
	
	protected function pdo_val(&$type) {
		$type=false;
		return $this->val->format('Y-m-d H:i:s');
	}
	
	protected function get_txt() {
		$format=$this->descr->format;
		if (is_null($format)) $format='Y-m-d H:i:s';
		return (string)$this->val->format($format);
	}
	
	protected function get_html() {
		return str_replace(array(' ','-'),array('&nbsp;','&#x2011;'),$this->get_txt());
	}
}

class field_boolean extends field {
	protected function descr_init(descriptor $descr) {
		$descr->set_defaults(array(
			
		));
	}
	
	protected function edescr_init(descriptor $edescr) {
		$edescr->set_defaults(array(

		));
	}
	
	protected function validate($value,$format='plain') {
		$this->invalid=$value;
		
		if (is_null($value)) {
			if ($this->descr->null) {
				$this->setnull();
				return true;
			} else {
				$this->setval(false);
				return true;
			}			
		}
		
		if ($value) $this->setval(true);
		else $this->setval(false);
	}
	
	protected function unformat_db($value) {
		return (bool)$value;
	}
	
	protected function pdo_val(&$type) {
		$type=PDO::PARAM_BOOL;
		return $this->val?1:0;
	}
	
	protected function get_txt() {
		return $this->val?'TRUE':'FALSE';
	}
	
	protected function get_html() {
		return $this->get_txt();
	}
}
?>