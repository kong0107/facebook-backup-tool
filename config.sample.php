<?php
	$config['startTime'] = microtime(true);
	date_default_timezone_set('Asia/Taipei');

	$config['app'] = array(
		'app_id' => '****************',
		'app_secret' => '********************************',
		'default_graph_version' => 'v2.5'
	);

	$config['server_root'] = $_SERVER['REQUEST_SCHEME']
		. '://'
		. $_SERVER['HTTP_HOST']
	;
	$config['site_root'] = $config['server_root']
		. pathinfo($_SERVER['PHP_SELF'])['dirname']
	;
?>
