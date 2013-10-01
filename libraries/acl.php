<?
class ACL{

	public static function can_delete($num=0){
		if (is_null($num)||!is_numeric($num))
			$num=0;
		$num=(int)$num;
		if (($num&0x40)==0x40) //64
			return true;
		else
			return false;
	}
	public static function can_create($num=0){
		if (is_null($num)||!is_numeric($num))
			$num=0;
		$num=(int)$num;

		if (($num&0x20)==0x20) //32
			return true;
		else
			return false;
	}	
	public static function can_update($num=0){
		if (is_null($num)||!is_numeric($num))
			$num=0;
		$num=(int)$num;

		if (($num&0x10)==0x10) //16
			return true;
		else
			return false;
	}
	public static function can_view($num=0){
		if (is_null($num)||!is_numeric($num))
			$num=0;
		$num=(int)$num;
		if (($num&0x02)==0x02) //2
			return true;
		else
			return false;
	}
	public static function acl2str($num=0){
		if (is_null($num)||!is_numeric($num))
			$num=0;
		
		//if (ACL::can_view($num)){$r='V';};
		$r=(ACL::can_delete($num)?'D':'-').(ACL::can_create($num)?'C':'-').(ACL::can_update($num)?'U':'-').(ACL::can_view($num)?'V':'-');
		return $r;
	}	
	public static function check($uid,$obj_key,$obj_id){
		trigger_error('ACL::check not implemented',E_USER_WARNING);
		return 0;
	}		
	private $obj_key='';
	private $uid=0;
	private $acl=0;
	private $obj_id=0;
	public function __construct($uid,$obj_key,$obj_id,$acl=null){
		if (is_null($acl)){
			$this->acl=ACL::check($uid,$obj_key,$obj_id);
		}
		$this->uid=$uid;
		$this->obj_id=$obj_id;
		$this->obj_key=$obj_key;
		$this->acl=$acl;
	}
	public function __get($k){
		switch ($k) {
			case 'can_delete' 	: return ACL::can_delete($this->acl); break;
			case 'can_create' 	: return ACL::can_create($this->acl); break;
			case 'can_update' 	: return ACL::can_update($this->acl); break;
			case 'can_view' 	: return ACL::can_view($this->acl);
		}
	}
	
}
?>