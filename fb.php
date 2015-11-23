<?php
	switch(session_status()) {
		case PHP_SESSION_DISABLED: // sessions are disabled.
			exit('Error: sessions disabled');
		case PHP_SESSION_NONE: // sessions are enabled, but none exists.
			session_start();
		case PHP_SESSION_ACTIVE: // sessions are enabled, and one exists.
	}
	require_once __DIR__ . '/config.php';
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

	function getFBLoginUrl($scope = array(), $seperator = '&') {
		global $config, $fb;
		$redirectUrl = $config['site_root']
			. '/login-callback.php?rr='
			. urlencode($_SERVER['REQUEST_URI'])
		;
		return $fb->getRedirectLoginHelper()->getLoginUrl(
			$redirectUrl, $scope, $seperator
		);
	}
?>
