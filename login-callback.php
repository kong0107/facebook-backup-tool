<?php
	require 'fb.php';
	try {
		$accessToken = $fb->getRedirectLoginHelper()->getAccessToken();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		// When Graph returns an error
		exit('Graph returned an error: ' . $e->getMessage());
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		// When validation fails or other local issues
		exit('Facebook SDK returned an error: ' . $e->getMessage());
	}

	if (isset($accessToken)) {
		$_SESSION['facebook_access_token'] = (string) $accessToken;
		$redirectUrl = $_GET['rr']
			? ($config['server_root'] . urldecode($_GET['rr']))
			: ($config['site_root'] . '/login.php')
		;		
		header('Location: ' . $redirectUrl);
	}
?>
