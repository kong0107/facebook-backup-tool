<?php
	require 'core.php';
	$helper = $fb->getRedirectLoginHelper();
	try {
		$accessToken = $helper->getAccessToken();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
	  // When Graph returns an error
	  exit('Graph returned an error: ' . $e->getMessage());
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
	  // When validation fails or other local issues
	  exit('Facebook SDK returned an error: ' . $e->getMessage());
	}

	if (isset($accessToken)) {
	  // Logged in!
	  $_SESSION['facebook_access_token'] = (string) $accessToken;
	  //$fb->setDefaultAccessToken($accessToken);
	  header('Location: ' . $config['site_root'] . '/login.php');

	  // Now you can redirect to another page and use the
	  // access token from $_SESSION['facebook_access_token']
	}
?>
