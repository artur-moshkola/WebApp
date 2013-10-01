<?
	class dbo {
	    private static $link = null ;
        private static $error_mode = null;
	    public static function getLink ($srv="default") {
	        if ( static:: $link ) {
	            return static:: $link ;
	        }

	        $ini = CONFIG_PATH."db.conf.ini" ;
	        $parse = parse_ini_file ( $ini , true );

			static::$error_mode=$parse["err_mode"];
			$cnnCred = $parse[$srv];
	        $user = $cnnCred ["db_user"] ;
	        $password = $cnnCred ["db_password"] ;
            $dsn = $cnnCred ["db_dsn"];

	        $options = $parse ["db_options"] ;
	        $attributes = $parse ["db_attributes"] ;
	        $_opt=array();
            foreach($options as $k=>$v){
            	$_opt[constant("PDO::{$k}")]=$v;
            }
	        static:: $link = new PDO ( $dsn, $user, $password, $_opt) ;
	        foreach ( $attributes as $k => $v ) {
	            static:: $link -> setAttribute ( constant ( "PDO::{$k}" )
	                , constant ( "PDO::{$v}" ) ) ;
	        }
	        return static:: $link ;
	    }

	    public static function __callStatic ( $name, $args ) {
	        $callback = array ( static:: getLink (), $name ) ;
	        return call_user_func_array ( $callback , $args ) ;
	    }

		public static function trExec($link,$query,$param=null,&$onread=null,$onend=null,array $onerror=null){
			if (is_null($param)) {
				$q=$link->query($query);
				if ($q===false) {
					trigger_error('Error while executing query: "'.$query.'"',E_USER_WARNING);
					return;
				}
			} else {
				$q=$link->prepare($query);
				$p;
				//$q->debugDumpParams();
				if(is_callable($param)) {
					$param($q);
				}elseif(is_array($param)){
					foreach($param as $k=>&$v){
						//echo $k.'=>'.$v.'<br/>';
						if(is_null($v)){
							$p = PDO::PARAM_NULL;
						}
						else
							$p = FALSE;
							
						$q->bindParam($k,$v,$p);
					}
				}
				//$q->debugDumpParams();
				if ($q->execute()===false) {
					trigger_error('Error while executing query: "'.$query.'"',E_USER_WARNING);
					return;
				}
				//echo($q->rowCount());
			}
			if (!is_null($onread)){
				if  (is_array($onread)){
					$onread=$q->fetchAll(PDO::FETCH_ASSOC);
					//var_dump($onread); 
				}elseif(is_callable($onread)){
					while($row=$q->fetch(PDO::FETCH_ASSOC)) {
						$onread($row);
					}	
				}else{
					$q->closeCursor();
					trigger_error('Error while executing query: "'.$query.'"',E_USER_WARNING);
					return;				
				}
			}
			if (!is_null($onend)){
				$onend($q);
			}
			$q->closeCursor();
			if (!is_null($onread)&&is_array($onread)){
				return $onread;
			}
		}	
		public static function trlExec($link,$query,$param=null,$onread=null,$onend=null,array $onerror=null){
			$arr=array();
			//var_dump($param);
			static::trExec($link,$query,$param,$arr,$onend,$onerror);
			//var_dump($arr);
			if (is_callable($onread)){
				foreach($arr as $v){
					$onread($v);
				}
			}			
		}			
		
		public static function Exec($query,$param=null,&$onread=null,$onend=null,array $onerror=null){
			$link=static::getLink();
			return static::trExec($link,$query,$param,$onread,$onend,$onerror);
		}	
		public static function lExec($query,$param=null,$onread=null,$onend=null,array $onerror=null){
			$arr=array();
			//var_dump($param);
			static::Exec($query,$param,$arr,$onend,$onerror);
			//var_dump($arr);
			if (is_callable($onread)){
				foreach($arr as $v){
					$onread($v);
				}
			}
		}
		
		
		
		public static function ReadAssoc($query,$onparam,$onread,$onend=null,$onerror=null) {
		 	try{
			 	$link=static::getLink();
			 	//var_dump(static::$error_mode);
			 	try{
			 		//var_dump($onread);
				 	$q=$link->prepare($query);
					if (!empty($onparam)){
						$onparam($q);
					}
					if ($q->execute()&&!empty($onread)){
						$row;
						while($row=$q->fetch(PDO::FETCH_ASSOC)) {
				        	$onread($row);
				        }
				    }
					if (!empty($onend)){
						$onend($q);
					}
					$q->closeCursor();
			    }
			    catch(Exception $e){
					if (!empty($onerror)){
						$onerror($q,$e);
					}
                    $q->closeCursor();
					if (static::$error_mode=="TRIGGER_ERROR"){
						trigger_error('Error while executing query: "'.$q->queryString.'" : '.$e->getMessage(),E_USER_WARNING);
					}
					if (static::$error_mode=="THROW_ERROR"){
						throw $e;
					}

			    }
		    }
		  	catch(Exception $e){
				if (!empty($onerror)){
					$onerror($q,$e);
				}

				if (static::$error_mode=="TRIGGER_ERROR"){
					trigger_error('DB error: "'.$e->getMessage().'"',E_USER_WARNING);
				}
				if (static::$error_mode=="THROW_ERROR"){
					throw $e;
				}
		  	}
	    }
		public static function arrReadAssoc($query,$paramArray,$onend=null,$onerror=null){
			$res=null;
			static::ReadAssoc($query,
				function($q) use (&$paramArray){//on param
					if(is_array($paramArray)){
						foreach($paramArray as $k=>$v){
		            		$q->bindParam($k,$v);
		            	}
	            	}
				},
				function($arr) use (&$res){
					if(is_null($res)){
						$res = Array();
					};
					$res[]=$arr;
				},
				$onend,$onerror
			);
			return $res;
		}		
	}

	class DBP {
	    const db='default';
		
		protected static $link = null;
		protected static $err_mode = null;

	    public static function getLink() {
	        if (!is_null(static::$link)) {
	            return static::$link ;
	        }
			
	        $parse = parse_ini_file (CONFIG_PATH.'db.ini', true);

			$cnnCred = $parse[static::db];
	        $user = $cnnCred ["db_user"] ;
	        $password = $cnnCred ["db_password"] ;
            $dsn = $cnnCred ["db_dsn"];
	        $options = $parse ["db_options"] ;

	        $_opt=array();
            foreach($options as $k=>$v){
            	$_opt[constant('PDO::'.$k)]=$v;
            }
			
	        static::$link = new PDO($dsn, $user, $password, $_opt) ;
			static::$link -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			
	        return static::$link ;
	    }
		
		protected static function getErrMode() {
			if (!is_null(static::$err_mode)) {
	            return static::$err_mode;
	        }
			
			static::ErrorMode('TRIGGER');
			return static::$err_mode;
		}
		
		protected static function process_error($query,$err) {
			switch (static::getErrMode()) {
				case 'IGNORE':
				break;
				case 'TRIGGER':
					trigger_error('Error while executing query: "'.$query.'": '.$err[2].'.',E_USER_WARNING);
				break;
				case 'EXCEPTION':
				break;
			}
		}
		
		protected static function bind_params(PDOStatement $q, &$params) {
			foreach ($params as $k=>&$v) {
				if (is_null($v)) {
					$pn = PDO::PARAM_NULL;
				} elseif (is_object($v)) {
					if ($v instanceof DateTime) {
						$v=$v->format('Y-m-d H:i:s');
						$pn=false;
					}
				} elseif (is_numeric($v)) {
					$pn=PDO::PARAM_INT;
				} else {
					$pn=false;
				}
					
				$q->bindParam($k,$v,$pn);
			}
		}
		
		public static function ErrorMode($mode) {
			static::$err_mode=$mode;
		}

	    public static function __callStatic ( $name, $args ) {
	        $callback = array ( static:: getLink (), $name ) ;
	        return call_user_func_array ( $callback , $args ) ;
	    }

		public static function LnExec($link,$query,$param=null,$onread=null,$onend=null,$onerror=null) {
			if (is_null($param)) {
				$q=$link->query($query);
				if ($q===false) {
					if (!is_null($onerror) && is_callable($onerror)) {
						$onerror($query,$link->errorInfo());
					} else {
						static::process_error($query,$link->errorInfo());
					}
					return null;
				}
			} else {
				$q=$link->prepare($query);
				if (is_callable($param)) {
					$param($q);
				} elseif (is_array($param)) {
					static::bind_params($q,$param);
				}

				if ($q->execute()===false) {
					if (!is_null($onerror) && is_callable($onerror)) {
						$onerror($query,$link->errorInfo());
					} else {
						static::process_error($query,$q->errorInfo());
					}
					return null;
				}
			}
			
			if (!is_null($onread)){
				if ($onread===true) {
					$onread=$q->fetchAll(PDO::FETCH_ASSOC);
				} elseif (is_callable($onread)) {
					while ($row=$q->fetch(PDO::FETCH_ASSOC)) {
						$onread($row);
					}
				}
			}
			
			if (!is_null($onend) && is_callable($onend)){
				$onend($q);
			}
			
			$q->closeCursor();
			
			if (!is_null($onread) && is_array($onread)){
				return $onread;
			} else {
				return true;
			}
		}

		public static function Exec($query,$param=null,$onread=null,$onend=null,$onerror=null) {
			$ln=static::getLink();
			return static::LnExec($ln,$query,$param,$onread,$onend,$onerror);
		}
		
		public static function LnMExec($link,$query,$param=null,&$onread=null,$onend=null,$onerror=null) {
			if (is_null($param)) {
				$q=$link->query($query);
				if ($q===false) {
					if (!is_null($onerror) && is_callable($onerror)) {
						$onerror($query,$link->errorInfo());
					} else {
						static::process_error($query,$link->errorInfo());
					}
					return null;
				}
			} else {
				$q=$link->prepare($query);
				if (is_callable($param)) {
					$param($q);
				} elseif (is_array($param)) {
					static::bind_params($q,$param);
				}

				if ($q->execute()===false) {
					if (!is_null($onerror) && is_callable($onerror)) {
						$onerror($query,$link->errorInfo());
					} else {
						static::process_error($query,$q->errorInfo());
					}
					return null;
				}
			}
			$r=0;
			$rread;
			
			do{
				if (!is_null($onread) && is_array($onread) && isset($onread[$r])){
					$rread=&$onread[$r];
					if (is_array($rread)) {
						$rread=$q->fetchAll(PDO::FETCH_ASSOC);
					} elseif (is_callable($rread)) {
						//var_dump($rread);
						while ($row=$q->fetch(PDO::FETCH_ASSOC)) {
							$rread($row);
						}
					}
				}
				$r++;
			} while ($q->nextRowset());
			if (!is_null($onend) && is_callable($onend)){
				$onend($q);
			}
			
			$q->closeCursor();
			
			if (!is_null($onread) && is_array($onread)){
				return $onread;
			} else {
				return true;
			}
		}

		public static function MExec($query,$param=null,&$onread=null,$onend=null,$onerror=null) {
			$ln=static::getLink();
			return static::LnMExec($ln,$query,$param,$onread,$onend,$onerror);
		}		
		
		public static function ExecSingleRow($query,$param=null,$onerror=null) {
			$ret=static::Exec($query,$param,true,null,$onerror);
			if (is_null($ret)) {
				return null;
			} else {
				if (count($ret)>0) {
					return $ret[0];
				} else {
					return false;
				}
			}
		}
		
		public static function ExecSingleVal($query,$param=null,$onerror=null) {
			$ret=static::Exec($query,$param,true,null,$onerror);
			if (is_null($ret)) {
				return null;
			} else {
				if (count($ret)>0) {
					$row=array_values($ret[0]);
					if (count($row)>0) return $row[0];
					else return false;
				} else {
					return false;
				}
			}
		}
	}
?>