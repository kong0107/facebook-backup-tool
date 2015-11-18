<?php
	session_start();
	require_once 'config.php';
	require_once __DIR__ . '/vendor/autoload.php';

	try {
		$fb = new Facebook\Facebook($config['app']);
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		exit('Graph returned an error: ' . $e->getMessage());
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		exit('Facebook SDK returned an error: ' . $e->getMessage());
	}

	if(isset($_SESSION['facebook_access_token']))
		$fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
?>
