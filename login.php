<?php
	require_once 'core.php';
	require_once 'db.php';

	$helper = $fb->getRedirectLoginHelper();
	$permissions = ['user_posts'];
	$loginUrl = $config['site_root'] . '/login-callback.php';
	$loginUrl = $helper->getLoginUrl($loginUrl, $permissions);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Facebook timeline backup</title>
</head>
<body>
<?php
	echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';

	if(isset($_SESSION['facebook_access_token'])) {
		$logoutUrl = $helper->getLogoutUrl(
			$fb->getDefaultAccessToken(),
			$config['site_root'] . '/logout-callback.php'
		);
		echo '<br><a href="' . $logoutUrl . '">Logout</a>';

		echo '<h1>Facebook PHP SDK</h1>';
		echo 'logged in<br>';

		$requestUrl = $_SERVER['QUERY_STRING']
			? $_SERVER['QUERY_STRING']
			: "/me/posts?fields=id,type,message,story,created_time,place,link,object_id,name,description"
		;
		//'me/posts?fields=id,type,message,message_tags,story,story_tags,with_tags,created_time,updated_time,application,parent_id,place,link,object_id,name,description,is_hidden,privacy'

		echo 'requesting <code>' . $requestUrl . '</code><br>';
		$response = $fb->get($requestUrl);
		$array = $response->getDecodedBody();
		$col = $db->facebook->feed;
		foreach($array['data'] as $post) {
			$post['_id'] = $post['id'];
			unset($post['id']);
			$col->update(
				array('_id' => $post['_id']),
				$post,
				array('upsert' => true)
			);
		}
		//$data = $array['data'];
		if($array['paging'] && $array['paging']['next']) {
			$ru = $_SERVER['PHP_SELF'] . '?' . substr($array['paging']['next'], 31);
			printf('<br><a href="%s">Request the next page</a> %s', $ru, $ru);
			echo "<script>setTimeout(function(){location.href = '$ru';}, 3000);</script>";
		}
		else '<br>No more pages.';
		echo '<div style="height: 40em; white-space: pre; margin: 1em; border: 1px solid #ccc; overflow: auto; font-family: monospace;">';
		print_r($array);
		echo '</div>';
	}
?>
</body>
</html>
