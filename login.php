<?php
	require_once 'core.php';
	require_once 'db.php';

	$helper = $fb->getRedirectLoginHelper();
	$permissions = ['user_posts'];
	$loginUrl = $config['site_root'] . '/login-callback.php';
	$loginUrl = $helper->getLoginUrl($loginUrl, $permissions);
	
	$online = false;
	
	
	function upsertUser($user_id) {
		//$global $db;
	}
	
	/**
	 * Insert or update a single post.
	 *
	 * The following fields are not the same as the origin:
	 * `from` is modified.
	 * `privacy` is modified.
	 * `comment_count` is added.
	 * `comments` is deleted.
	 */
	function upsertPost($post) {
		global $db;
		/// from
		upsertUser($post['from']['id']);
		$post['from'] = $post['from']['id'];
		
		/// privacy
		$privacy = $post['privacy'];
		if($privacy['friends'] === '') unset($privacy['friends']);
		if($privacy['allow'] === '') unset($privacy['allow']);
		if($privacy['deny'] === '') unset($privacy['deny']);
		$post['privacy'] = $privacy;
		
		/// comments
		$post['comment_count'] = upsertComments($post['comments'], $post['id']);
		unset($post['comments']);
		
		/// upsert
		$post['_id'] = $post['id'];
		unset($post['id']);
		$db->facebook->feed->update(
			array('_id' => $post['_id']),
			$post,
			array('upsert' => true)
		);
	}
	
	/**
	 * Upsert multiple comments and their comments with page parsing.
	 * .... uh, paginations are not handled yet.
	 *
	 * @return number of total comments
	 */
	function upsertComments($commentPage, $parent) {
		$amount = 0;
		$data = $commentPage['data'];
		$paging = $commentPage['paging']['cursors'];
		foreach($data as $comment)
			upsertSingleComment($comment, $parent);
		return count($data);
	}
	
	/**
	 * Insert or update a single comment.
	 *
	 * The following fields are not the same as the origin:
	 * `from`   is modified.
	 * `parent` is modified to the id of the parent, 
	 *          even the parent is not a comment.
	 * `comments` is deleted.
	 */
	function upsertSingleComment($comment, $parent) {
		global $db;
		/// from
		upsertUser($comment['from']['id']);
		$comment['from'] = $comment['from']['id'];
		
		/// parent
		$comment['parent'] = $comment['parent'] ? $comment['parent']['id'] : $parent;
		
		/// comments
		if($comment['comment_count'])
			upsertComments($comment['comments'], $comment['id']);
		
		/// upsert
		$comment['_id'] = $comment['id'];
		unset($comment['id']);
		$db->facebook->comment->update(
			array('_id' => $comment['_id']),
			$comment,
			array('upsert' => true)
		);
	}
	
	
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
			//: '/me/posts?fields=id,type,message,story,created_time,place,link,object_id,name,description'
			: '/me/posts?fields=id,type,from,message,message_tags,story,story_tags,with_tags,created_time,updated_time,application,parent_id,place,link,object_id,name,description,privacy,comments{id,from,message,message_tags,attachment,created_time,comment_count,comments{id,from,message,message_tags,attachment,created_time}}'
		;

		echo 'requesting <code>' . $requestUrl . '</code><br>';
		$response = $fb->get($requestUrl);
		$array = $response->getDecodedBody();
		$colFeed = $db->facebook->feed;
		foreach($array['data'] as $post) {
			$post['_id'] = $post['id'];
			unset($post['id']);
			if($online) $colFeed->update(
				array('_id' => $post['_id']),
				$post,
				array('upsert' => true)
			);
		}
		//$data = $array['data'];
		if($array['paging'] && $array['paging']['next']) {
			$ru = $_SERVER['PHP_SELF'] . '?' . substr($array['paging']['next'], 31);
			printf('<br><a href="%s">Request the next page</a> %s', $ru, $ru);
			if($online) echo "<script>setTimeout(function(){location.href = '$ru';}, 3000);</script>";
		}
		else '<br>No more pages.';
		echo '<div style="height: 40em; white-space: pre; margin: 1em; border: 1px solid #ccc; overflow: auto; font-family: monospace;">';
		print_r($array);
		echo '</div>';
	}
?>
</body>
</html>
