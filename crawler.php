<?php
	$time_start = microtime(true);
	require_once 'fb.php';
	require_once 'db.php';
	$fieldsOfComment = implode(',', getFields('comment'));

	/**
	 * A function map what shall be done to a node by its type.
	 */
	$funcMap = array(
		/*'post' => function($post) {
			// Hmm.. what about delete duplicates of field `action`?
			return $post;
		},*/
		'photo' => function($photo) {
			// Field `image` seems to be superfluous, 
			// maybe delete some of its elements?
			global $nodeId;
			$image = $photo['images'][0];
			$info = pathinfo(parse_url($image['source'], PHP_URL_PATH));
			$dir = __DIR__ . "/data/photos/$nodeId/";
			$dest = "$dir{$photo['id']}.{$info['extension']}";
			if(!file_exists($dest)) {
				echo "Downloading photo to <code>$dest</code>\n";
				if(!is_dir($dir)) mkdir($dir, 0777, true);
				copy($image['source'], $dest);
				usleep(250);
			}
			return $photo;
		}/*,
		'video' => function($video) {
			// well.. `format` shall be shrunk.
			// and.. what about download the file by `source` field?
			return $video;
		}*/
	);
	
	/**
	 * Modify $_GET for photos in album.
	 *
	 * Not a good solution, but I'm lazy.
	 */
	if($_GET['album']) {
		$params = array(
			'nodeType' => 'album',
			'nodeId' => $_GET['album'],
			'edge' => 'photos'
		);
		$_GET = $params;
	}

	$nodeType = $_GET['nodeType'];
	$edge = $_GET['edge'];
	switch($edge) {
		case 'feed':
		case 'posts':
		case 'tagged':
			$perms = ['user_posts'];
			$containedNode = 'post';
			break;
		case 'albums':
			$perms = ['user_photos'];
			$containedNode = 'album';
			break;
		case 'photos':
			$perms = ['user_photos'];
			$containedNode = 'photo';
			break;
		case 'videos':
			$perms = ['user_videos'];
			$containedNode = 'video';
			break;
		case 'likes':
			$perms = ['user_likes'];
			$containedNode = 'page';
			break;
		case 'docs':
			$containedNode = 'doc';
			break;
		case 'comments':
			$containedNode = 'comment';
			break;
		default: //'admined_groups', 'groups', 'tagged'
			exit('Unknown or unsupported edge');
	}
	if($nodeType == 'page') $perms = [];
	else if($nodeType == 'group') $perms = ['user_managed_groups'];
	else if($nodeType == 'event') $perms = ['user_events'];
	
	checkLogin($perms);

	$user = $fb->get('/me')->getDecodedBody();
	$nodeId = ($nodeType == 'user') ? $user['id'] : $_GET['nodeId'];

	$colName = "{$nodeType}_{$nodeId}_{$edge}";
	$col = $db->selectCollection($colName);
	$fields = implode(',', getFields($containedNode, $nodeType != 'group'));
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
	try {
		$response = $fb->get($requestUrl)->getDecodedBody();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		exit('Graph returned an error: ' . $e->getMessage());
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		exit('Facebook SDK returned an error: ' . $e->getMessage());
	}
	foreach($response['data'] as $node) {
		echo "Parsing {$node['id']}\n";
		if($mayHaveComments) {
			$comments = getComments($node['id']);
			if(count($comments)) $node['comments'] = $comments;
		}
		if($funcMap[$containedNode])
			$node = $funcMap[$containedNode]($node);

		$node = array_remove_empty($node);

		$node['_id'] = $node['id'];
		unset($node['id']);
		$col->update(
			array('_id' => $node['_id']),
			$node,
			array('upsert' => true)
		);
		echo '<div style="max-height: 20em; overflow: auto;">';
		echo htmlspecialchars(print_r($node, true));
		echo '</div>';
	}

	$next = $response['paging']['next'];
	if($next) {
		$params = $_GET;
		$params['request'] = substr($next, 31);
		$requestNext = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
		echo "<a href=\"$requestNext\">Request next page</a><br>";
		echo "<script>setTimeout(function(){location.href = '$requestNext';}, 10000);</script>";
	}
	else echo 'No more data.';

	$time_end = microtime(true);
	echo "<script>document.getElementById('timeDiff').textContent='" . ($time_end - $time_start) . "';</script>";
?>
<br><a href="export.php?col=<?=$colName?>">Download JSON file</a>
<br><a href="crawler.html">crawl another page</a>
</body>
</html>
