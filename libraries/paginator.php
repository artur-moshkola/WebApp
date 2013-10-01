<?
class paginator {
	protected $page;
	protected $num_onpage;
	protected $num_items;

	function __construct($page=0,$onpage=10,$items=null) {
		$this->num_onpage=$onpage;
		$this->num_items=$items;

		if ($page instanceof sourceset) {
			if ($page->get->num('p')) {
				$this->set_page($page->get->p-1);
			} else {
				$all=$page->uri->get_all();
				$p=0;
				foreach ($all as $k=>$v) {
					if (preg_match('/^p([0-9]+)$/',$k,$res)) {
						$p=$res[1]-1;
					}
				}
				$this->set_page($p);
			}
		} elseif (is_numeric($page)) {
			$this->set_page($page-1);
		} else {
			$this->set_page(0);
		}
	}
	
	protected function set_page($pg) {
		if ($pg<0) $this->page=0;
		elseif (!is_null($this->num_items) && ($pg>=$this->get_pages())) $this->page=$this->get_pages()-1;
		else $this->page=$pg;
	}

	public function set_items($num) {
		$this->num_items=$num;
		$this->set_page($this->page);
	}

	public function get_mysql_limit() {
		return 'LIMIT '.$this->get_offset().', '.$this->get_onpage();
	}

	public function get_offset() {
		return $this->num_onpage*$this->page;
	}

	public function get_onpage() {
		return $this->num_onpage;
	}
	
	public function get_pages() {
		if (is_null($this->num_items)) return 1;
		return ceil($this->num_items/$this->num_onpage);
	}
	
	public function get_current() {
		return $this->page+1;
	}
	
	public function get_parray($hide=true) {
		$parr=array();

		$c=$this->get_current();
		$n=$this->get_pages();

		$pshw=false;
		for ($p=1;$p<=$n;$p++) {
			if ($hide) {
				$shw=($p<4) || (abs($p-$c)<3) || (($n-$p)<3);

				if (!$shw) {
					if ($pshw) {
						$parr[$p]='stub';
					}

					$pshw=false;
					continue;
				}
			}


			if ($p==$c) {
				$parr[$p]='current';
			} else {
				$parr[$p]='page';
			}
			$pshw=true;
		}
		
		return $parr;
	}
}
?>