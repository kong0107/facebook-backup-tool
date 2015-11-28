<?php
	require_once 'fb.php';
	require_once 'db.php';
	$metadata = json_decode(file_get_contents('metadata/v2.5.json'), true);
	$excludedFields = json_decode(file_get_contents('excludedFields.json'), true);

	function shrinkArr($arr) {
		foreach($arr as $k => $v) {
			if(is_array($v)) {
				if($k == 'attachment') unset($v['url']);
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

	$edge = $_GET['edge'];
	switch($_GET['nodeType']) {
		case 'user':
			$nodeType = 'user';
			switch($edge) {
				case 'feed':
				case 'posts':
					$containedNode = 'post';
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

	$fields = [];
	foreach($metadata[$containedNode]['fields'] as $field) {
		$fn = $field['name'];
		if(!in_array($fn, $excludedFields[$containedNode]))
			$fields[] = $fn;
	}
	$requestUrl = empty($_GET['request'])
		? ("/$nodeId/$edge?fields=" . implode(',', $fields))
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
<h1>Facebook Data Crawler</h1>
<?php
	echo "Requesting <code>$requestUrl</code>\n";
	$response = $fb->get($requestUrl)->getDecodedBody();
	foreach($response['data'] as $node) {
		$node['_id'] = $node['id'];
		unset($node['id']);
		$col->update(
			array('_id' => $node['_id']),
			$node,
			array('upsert' => true)
		);
		print_r($node);
	}

	$next = $response['paging']['next'];
	if($next) {
		$params = $_GET;
		$params['request'] = urlencode(substr($next, 31));
		$requestNext = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
		echo "<a href=\"$requestNext\">Request next page</a><br>";
		echo "<script>setTimeout(function(){location.href = '$requestNext';}, 5000);</script>";
	}
?>
</body>
</html>
