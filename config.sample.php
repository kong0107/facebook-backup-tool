<?php
	$config['start_time'] = microtime(true);
	date_default_timezone_set('Asia/Taipei');

	$config['facebook_app'] = array(
		'app_id' => '****************',
		'app_secret' => '********************************',
		'default_graph_version' => 'v2.5'
	);
	$config['facebook_autoload'] = __DIR__ . '/vendor/autoload.php';
	
	$config['mongodb_url'] = '';
	$config['mongodb_dbname'] = 'facebook';

	$config['server_root'] = $_SERVER['REQUEST_SCHEME']
		. '://'
		. $_SERVER['HTTP_HOST']
	;
	$config['site_root'] = $config['server_root']
		. pathinfo($_SERVER['PHP_SELF'])['dirname']
	;
	
	$config['enable_photo_backup'] = true;
?>
