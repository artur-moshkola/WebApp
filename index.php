<?
$conf = parse_ini_file ( './config/main.ini', true ) ;
if($conf['define']){
	foreach ( $conf['define'] as $k => $v){
		define($k,$v);
	}
}

require_once CLASSES_PATH.'application.php';

$app=new application();

if ($app->src->uri->neq('uri_1','')) {
	$mod=str_replace('-','_',$app->src->uri->uri_1);
} else {
	$mod='main';
}

if (!$app->run($mod,null,array('hidden'=>false))) {
	if (!$app->run('default',$mod)) {
		$app->err404();
	}
}
?>