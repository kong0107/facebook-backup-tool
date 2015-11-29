<?php
	$time_start = microtime(true);
	require_once 'fb.php';
	require_once 'db.php';
	$metadata = json_decode(file_get_contents('metadata/v2.5.json'), true);
	$excludedFields = json_decode(file_get_contents('excludedFields.json'), true);
	
	/**
	 * Get field names of a node type.
	 */
	function getFields($nodeType) {
		global $metadata;
		global $excludedFields;
		$ef = $excludedFields[$nodeType] or $ef = [];
		$fields = [];
		foreach($metadata[$nodeType]['fields'] as $field) {
			$fn = $field['name'];
			if(!in_array($fn, $ef)) $fields[] = $fn;
		}
		return $fields;
	}
	
	$fieldsOfComment = implode(', ', getFields('comment'));
	
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
		//echo "Getting comments of $nodeId\n";
		do {
			$res = $fb->get($reqUrl)->getDecodedBody();
			foreach($res['data'] as $c) {
				//print_r($c);
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

	$edge = $_GET['edge'];
	switch($_GET['nodeType']) {
		case 'user':
			$nodeType = 'user';
			switch($edge) {
				case 'feed':
				case 'posts':
					$perms = ['user_posts'];
					break;
				case 'albums':
				case 'photos':
					$perms = ['user_photos'];
					break;
				case 'videos':
					$perms = ['user_videos'];
					break;
				case 'likes':
					$perms = ['user_likes'];
					break;
				case 'friends':
					$perms = ['user_friends'];
					break;
				case 'tagged':
					$perms = ['user_posts', 'user_photos', 'user_videos'];
					break;
				default: //'admined_groups', 'groups', 'tagged'
					exit('Unknown or unsupported edge');
			}
			break;
		case 'page':
			$nodeType = 'page';
			$perms = [];
			break;
		default:
			exit('Unknown or unsupported node type');
	}
	switch($edge) {
		case 'feed':
		case 'posts':
			$containedNode = 'post';
			break;
		default:
			exit('`$containedNode` not set');
	}

	/**
	 * Request the permission if not granted.
	 */
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
	$user = $fb->get('/me')->getDecodedBody();
	$nodeId = ($nodeType == 'user') ? $user['id'] : $_GET['pageId'];

	$col = $db->selectCollection("{$nodeType}_{$nodeId}_{$edge}");
	$fields = implode(',', getFields($containedNode));
	$mayHaveComments = in_array('comments', $metadata[$containedNode]['connections']);
		
	$requestUrl = empty($_GET['request'])
		? "/$nodeId/$edge?limit=5&fields=$fields"
		: urldecode($_GET['request'])
	;
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Facebook Data Crawler</title>
</head>
<body style="white-space: pre-wrap; font-family: monospace;">
<h1 style="margin-top: 0;">Facebook Data Crawler</h1>
<div id="timeDiff">&nbsp;</div>
<?php
	echo date(DATE_ATOM);
	echo "\nRequesting <code>$requestUrl</code>\n";
	$response = $fb->get($requestUrl)->getDecodedBody();
	foreach($response['data'] as $node) {
		echo "Parsing {$node['id']}\n";
		if($mayHaveComments) {	
			$comments = getComments($node['id']);
			if(count($comments)) $node['comments'] = $comments;
		}
		$node = array_remove_empty($node);
		
		$node['_id'] = $node['id'];
		unset($node['id']);
		$col->update(
			array('_id' => $node['_id']),
			$node,
			array('upsert' => true)
		);
		echo '<div style="max-height: 20em; overflow: auto;">';
		print_r($node);
		echo '</div>';
	}

	$next = $response['paging']['next'];
	if($next) {
		$params = $_GET;
		$params['request'] = urlencode(substr($next, 31));
		$requestNext = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
		echo "<a href=\"$requestNext\">Request next page</a><br>";
		echo "<script>setTimeout(function(){location.href = '$requestNext';}, 7500);</script>";
	}
	else echo 'No more data. You can <a href="crawler.html">crawl another page</a>.';
	
	$time_end = microtime(true);
	echo "<script>document.getElementById('timeDiff').textContent='" . ($time_end - $time_start) . "';</script>";
?>
</body>
</html>
