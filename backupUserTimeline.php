<?php
	require_once 'core.php';
	require_once 'db.php';

	$online = true;

	function upsert($collect, $arr) {
		global $db;
		$arr['_id'] = $id = $arr['id'];
		/*print_r($arr);
		exit($arr['id']);*/
		unset($arr['id']);
		$db->selectCollection($collect)->update(
			array('_id' => $id),
			$arr,
			array('upsert' => true)
		);
		return $id;
	}

	/**
	 */
	function shrinkArr($arr) {
		foreach($arr as $k => $v) {
			if(is_array($v)) {
				if($k == 'attachment') {
					unset($v['url']);
					//continue;
				}
				$v = shrinkArr($v);
				if(!count($v)) unset($arr[$k]);
				else $arr[$k] = $v;
				continue;
			}
			if(is_null($v) || $v === '') unset($arr[$k]);
			else if(is_object($v) && get_class($v))
				$arr[$k] = $v->format(DateTime::ISO8601);
		}
		return $arr;
	}

	/**
	 * Remove elements in an array if they are null or empty string or array.
	 *
	 * Unlike `empty()`, 0, 0.0, "0" and false are NOT considered empty.
	 * @see http://php.net/manual/en/function.empty.php
	 */
	/*function array_remove_empty($arr) {
		foreach($arr as $k => $v) {
			if(is_array($v)) {
				$v = array_remove_empty($v);
				if(!count($v)) unset($arr[$k]);
				else $arr[$k] = $v;
				continue;
			}
			if(is_null($v) || $v === '') unset($arr[$k]);
		}
		return $arr;
	}*/
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Facebook timeline backup</title>
</head>
<body style="white-space: pre; font-family: monospace;">
<h1>Facebook timeline backup</h1>
<?php
	$helper = $fb->getRedirectLoginHelper();
	if(!isset($_SESSION['facebook_access_token'])) {
		$loginUrl = $helper->getLoginUrl(
			$config['site_root'] . '/login-callback.php',
			['user_posts']
		);
		printf('<a href="%s">Log in with Facebook</a> to continue.', $loginUrl);
		exit ('</body></html');
	}

	$logoutUrl = $helper->getLogoutUrl(
		$fb->getDefaultAccessToken(),
		$config['site_root'] . '/logout-callback.php'
	);
	printf('Logged in. <a href="%s">Logout</a><br>', $logoutUrl);

	$requestUrl = $_SERVER['QUERY_STRING']
		? $_SERVER['QUERY_STRING']
		//: '/me/posts?fields=id,type,from,message,message_tags,story,story_tags,with_tags,created_time,updated_time,application,parent_id,place,link,object_id,name,description,privacy,comments{id,from,message,message_tags,attachment,created_time,comment_count,comments{id,from,message,message_tags,attachment,created_time}}'
		: '/me/posts?fields=id,type,from,message,message_tags,story,story_tags,with_tags,created_time,updated_time,application,parent_id,place,link,object_id,name,description,privacy'
	;
	echo 'requesting <code>' . $requestUrl . '</code><br>';

	$response = $fb->get($requestUrl);
	$posts = $response->getGraphEdge();
	//var_dump(substr($response->getDecodedBody()['paging']['next'], 31));
	foreach($posts as $post) {
		$post = $post->asArray();

		//$post['from'] = upsert('user', $post['from']);
		/*$post['created_time'] = $post['created_time']->format(DateTime::ISO8601);
		$post['updated_time'] = $post['updated_time']->format(DateTime::ISO8601);*/

		//print_r(array_remove_empty($post->asArray())); echo '<hr>';
		for(
			//$comments = $post['comments'];
			$comments = $fb->get("/{$post['id']}/comments?fields=id,message,message_tags,attachment,created_time,comment_count,comments{id,from,message,message_tags,attachment,created_time}")->getGraphEdge();
			$comments;
			$comments = $fb->next($comments)
		) {
			if(!isset($post['comments'])) $post['comments'] = array();
			//print_r($comments); echo '<hr>';
			foreach($comments as $comment) {
				$c1 = $comment->asArray();
				if($c1['comment_count']) {
					$c1['comments'] = array();
					//print_r($comment); echo '<hr>';
					for($ccs = $comment['comments']; $ccs; $ccs = $fb->next($ccs)) {
						//print_r($ccs); echo '<hr>';
						foreach($ccs as $cc) {
							$c1['comments'][] = $cc->asArray();
							//print_r($cc); echo '<hr>';
						}
					}
					unset($c1['comment_count']);
				}
				$post['comments'][] = $c1;
			}
		}
		$post = shrinkArr($post);
		//$post['comment_count'] = isset($post['comments']) ? count($post['comments']) : 0;
		echo $post['created_time'] . "\n";
		print_r($post['message']); echo '<hr>';
		if($online) upsert('post', $post);
	}

	$next = $response->getDecodedBody()['paging']['next'];
	if($next) {
		$ru = $_SERVER['PHP_SELF'] . '?' . substr($next, 31);
		printf('<a href="%s">Request the next page</a>', $ru);
		if($online) echo "<script>setTimeout(function(){location.href = '$ru';}, 5000);</script>";
	}
?>
</body>
</html>
