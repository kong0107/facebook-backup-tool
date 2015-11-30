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

	/**
	 * Get a FB login URL which redirects back to the current page.
	 *
	 * Due to the mechanism of the FB API, call this twice would 
	 * not get the same result.
	 */
	function getFBLoginUrl($permissions = [], $seperator = '&') {
		global $config, $fb;
		$redirectUrl = $config['site_root']
			. '/login-callback.php?rr='
			. urlencode($_SERVER['REQUEST_URI'])
		;
		return $fb->getRedirectLoginHelper()->getLoginUrl(
			$redirectUrl, $permissions, $seperator
		);
	}

	/**
	 * Request the permission if not granted.
	 *
	 * Untested.
	 */
	function checkLogin($perms = []) {
		global $fb;
		if(!isset($_SESSION['facebook_access_token'])) {
			header('Location: ' . getFBLoginUrl($perms));
			exit;
		}
		else if(count($perms)) {
			$granted = [];
			foreach($fb->get("/me/permissions")->getGraphEdge() as $perm) {
				if($perm['status'] == 'granted')
					$granted[] = $perm['permission'];
			}
			foreach($perms as $perm) {
				if(!in_array($perm, $granted)) {
					header('Location: ' . getFBLoginUrl($perms));
					exit;
				}
			}
		}
		return true;
	}

	$metadata = json_decode(file_get_contents('metadata/v2.5.json'), true);
	$excludedFields = json_decode(file_get_contents('excludedFields.json'), true);

	/**
	 * Get field names of a node type.
	 */
	function getFields($nodeType) {
		global $metadata, $excludedFields;
		$ef = $excludedFields[$nodeType] or $ef = [];
		$fields = [];
		//print_r($metadata);
		foreach($metadata[$nodeType]['fields'] as $field) {
			$fn = $field['name'];
			if(!in_array($fn, $ef)) $fields[] = $fn;
		}
		if(in_array('picture', $metadata[$nodeType]['connections']))
			$fields[] = 'picture';
		return $fields;
	}

	/**
	 * Get all comments of a node.
	 *
	 * All comments and their comments are going to be parsed.
	 * This may take minutes for posts of a famous page.
	 */
	function getComments($nodeId) {
		global $fb;
		global $fieldsOfComment;
		$result = [];
		$reqUrl = "/$nodeId/comments?fields=$fieldsOfComment";
		echo "Getting comments of $nodeId\n";
		do {
			$res = $fb->get($reqUrl)->getDecodedBody();
			foreach($res['data'] as $c) {
				if($c['comment_count'] !== 0)
					$c['comments'] = getComments($c['id']);
				//else echo "no sub-comments.\n";
				$result[] = $c;
			}
		} while($reqUrl = substr($res['paging']['next'], 31));
		return $result;
	}

	/**
	 * Remove null, '' and [] in an array recursively.
	 *
	 * Unlike `empty`, this does NOT remove zero and false.
	 */
	function array_remove_empty($arr) {
		foreach($arr as $k => &$v) {
			if(is_array($v)) {
				$v = array_remove_empty($v);
				if(!count($v)) unset($arr[$k]);
				continue;
			}
			if(is_null($v) || $v === '') unset($arr[$k]);
		}
		return $arr;
	}
?>
