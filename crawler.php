<?php
	$time_start = microtime(true);
	require_once 'fb.inc.php';
	require_once 'db.inc.php';
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
			global $nodeType, $nodeId;
			$image = $photo['images'][0];
			$info = pathinfo(parse_url($image['source'], PHP_URL_PATH));
			$dir = __DIR__ . "/data/photos/{$nodeType}_{$nodeId}_photos/";
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
			$perms = ['user_events'];
			$containedNode = 'comment';
			break;
		case 'events':
			$perms = ['user_events'];
			$containedNode = 'event';
			break;
		default: //'admined_groups', 'groups'
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

	$waitTime = 2500;
	if($containedNode == 'photo') $waitTime *= 2;
	if($mayHaveComments) $waitTime *= 2;
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Facebook Data Crawler</title>
</head>
<body style="white-space: pre-wrap; font-family: monospace; margin-top: 0;">
<h1 style="margin-top: 0;">Facebook Data Crawler</h1>
<div id="timeDiff">&nbsp;</div>
<div id="waitMsg">Waits <?=number_format($waitTime)?> milliseconds for each request bundle.</div>
<?php
	echo 'Starts at ' . date('Y-m-d H:i:s') . "\n";
	/**
	 * Save node info to a JSON file and into DB.
	 */
	if(empty($_GET['request'])) {
		echo "Requesting node info ...\n";
		$ru = "/$nodeId?fields=" . implode(',', getFields($nodeType));
		$nodeInfo = array_remove_empty($fb->get($ru)->getDecodedBody());

		$dir = __DIR__ . '/data/json/';
		if(!is_dir($dir)) mkdir($dir, 0777, true);
		$dest = __DIR__ . "/data/json/{$nodeType}_{$nodeId}_info.json";
		$bytes = file_put_contents($dest,
			json_encode($nodeInfo, JSON_UNESCAPED_UNICODE) . "\n"
		);
		echo "Save node info into <code>$dest</code>\n";

		$nodeInfo['_id'] = $nodeId;
		unset($nodeInfo['id']);
		$db->selectCollection($nodeType)->update(
			array('_id' => $nodeId),
			$nodeInfo,
			array('upsert' => true)
		);
	}

	echo "Requesting <code>$requestUrl</code>\n";
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
		echo '<div style="max-height: 20em; overflow: auto; border: 1px solid #ccc;">';
		echo htmlspecialchars(print_r($node, true));
		echo '</div>';
	}

	$next = $response['paging']['next'];
	if($next) {
		$params = $_GET;
		$params['request'] = substr($next, 31);
		$requestNext = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
		echo "<a href=\"$requestNext\">Request next page</a>\n";
		echo "<script>setTimeout(function(){location.href = '$requestNext';}, $waitTime);</script>";
	}
	else {
		echo "No more data.\n";
		$data = iterator_to_array(
			$db->selectCollection($colName)->find()
			->sort(array('created_time'=>-1))
		, false);
		$dest = __DIR__ . "/data/json/{$nodeType}_{$nodeId}_{$edge}.json";
		$bytes = file_put_contents($dest,
			json_encode($data, JSON_UNESCAPED_UNICODE) . "\n"
		);
		?>
			<br>Saved all nodes in edge (totally <?=number_format($bytes)?> bytes) into <code><?=$dest?></code>
			<script>
				document.getElementById("waitMsg").textContent="Finished crawling this edge.";
				if(window.parent != window) {
					var p = window.parent;
					var f = p.$('form').get(0);
					var edges = f.edge;
					var scope = p.angular.element(p.$("[ng-controller='main']")).scope();
					var submit = function(){ 
						f.submit(); 
						p.$("#message").text("Form submitted. Crawling the edge ...");
					};

					if(edges.value != "photosInAlbum") {
						for(var i = 0; i < edges.length - 1; ++i) {
							if(edges[i].value == edges.value) {
								edges.value = edges[i + 1].value;
								break;
							}
						}
						if(edges.value != "photosInAlbum")
							scope.requestEdge(edges.value);
						else scope.getAlbums(scope.nodeId);
						setTimeout(submit, 5000);
					}
					else {
						var albums = f.album;
						if(albums.value == albums[albums.length - 1].value) {
							alert('Finished every album.');
						}
						else {
							for(var i = 0; i < albums.length - 1; ++i) {
								if(albums[i].value == albums.value) {
									albums.value = albums[i + 1].value;
									break;
								}
							}
							scope.getAlbumInfo(albums.value);
							setTimeout(submit, 5000);
						}
					}
					p.$("#message").text("Finished crawling edge <?=$colName?>.");
				}
			</script>
		<?php
	}
	$time_end = microtime(true);
	echo '<script>document.getElementById("timeDiff").textContent="'
		. number_format($time_end - $time_start, 3)
		. ' seconds were spent on this page.";</script>'
	;
?>
<br><a href="export.php?col=<?=$colName?>">Download JSON file</a>
<br><a href="crawler.html">crawl another page</a>
</body>
</html>
