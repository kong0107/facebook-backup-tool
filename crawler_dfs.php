<?php
	require_once 'fb.inc.php';
	require_once 'db.inc.php';

	/**
	 * Constants, functions and checkings.
	 */
	$fieldLists = [];
	foreach($metadata as $type => $noUse)
		$fieldLists[$type] = implode(',', getFields($type));

	if(!isset($_SESSION['stack'])) $_SESSION['stack'] = [];
	if(!is_array($_SESSION['stack'])) exit('Error: stack shall be an array');
	function push($ele) {
		echo "Pushing {$ele['path']}\n";
		$_SESSION['stack'][] = $ele;
		return count($_SESSION['stack']);
	}

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
			global $parent;
			$image = $photo['images'][0];
			$info = pathinfo(parse_url($image['source'], PHP_URL_PATH));
			$dir = __DIR__ . "/data/photos/{$parent}_photos/";
			$dest = "$dir{$photo['id']}.{$info['extension']}";
			if(!file_exists($dest)) {
				echo "Downloading photo to $dest\n";
				if(!is_dir($dir)) mkdir($dir, 0777, true);
				copy($image['source'], $dest);
				usleep(250);
			}
			else echo "File exists on $dest\n";
			return $photo;
		}/*,
		'video' => function($video) {
			// well.. `format` shall be shrunk.
			// and.. what about download the file by `source` field?
			return $video;
		}*/
	);

	/**
	 * Handle the starting node.
	 *
	 * This is the first element of the stack.
	 */
	if($_GET['node'] && array_key_exists($_GET['type'], $fieldLists)) {
		push([
			'col' => $_GET['type'] . 's',
			'type' => $_GET['type'],
			'path' => "/{$_GET['node']}?fields=" . $fieldLists[$_GET['type']]
		]);
	}
	
	if(!count($_SESSION['stack'])) exit('Notice: stack is empty');

	/**
	 * Main part.
	 */
//while(count($_SESSION['stack'])) {
	$req = array_pop($_SESSION['stack']);
	print_r($req);
	$parent = substr($req['path'], 1, strpos($req['path'], '/', 1) - 1);

	$res = $fb->get($req['path'])->getDecodedBody();
	if(array_key_exists('data', $res)) {
		echo "Processing edge data ...\n";
		if($next = $res['paging']['next']) {
			echo "Pushing the next page ...\n";
			push([
				'col' => $req['col'],
				'type' => $req['type'],
				'path' => substr($next, 31)
			]);
		}

		$t = $req['type'];
		printf("Pushing %d nodes.\n", count($res['data']));
		foreach($res['data'] as $doc) {
			if(isset($funcMap[$t])) $doc = $funcMap[$t]($doc);
			if($t == 'comment') $doc['fbbk_parent'] = $parent;

			upsert($req['col'], $doc);

			if(($t != 'page') && ($t != 'event')) {
				if($t != 'comment') {
					push([
						'col' => "{$req['col']}_comments",
						'type' => 'comment',
						'path' => "/{$doc['id']}/comments?fields=" . $fieldLists['comment']
					]);
				}
				else if($doc['comment_count'] > 0) {
					push([
						'col' => $req['col'],
						'type' => 'comment',
						'path' => "/{$doc['id']}/comments?fields=" . $fieldLists['comment']
					]);
				}
				if($t == 'album') {
					push([
						'col' => "{$req['col']}_photos",
						'type' => 'photos',
						'path' => "/{$doc['id']}/photos?fields=" . $fieldLists['photo']
					]);
				}
			}

		}
	}
	else {
		echo "Processing node data ...\n";
		upsert($req['col'], $res);
		$id = $res['id'];

		$edges = array(
			'user' => ['albums', 'likes', 'posts', 'photos', 'tagged'],
			'page' => ['albums', 'events', 'posts', 'videos'],
			'event' => ['posts', 'photos', 'comments', 'feed'],
			'group' => ['albums', 'events', 'feed', 'members', 'docs']
		);
		$containedNode = array(
			'feed' => 'post',
			'posts' => 'post',
			'tagged' => 'post',
			'members' => 'user',
			'likes' => 'page',
			'albums' => 'album',
			'photos' => 'photo',
			'videos' => 'video',
			'events' => 'event',
			'comments' => 'comment',
			'docs' => 'doc'
		);
		foreach($edges[$req['type']] as $e) {
			push([
				'col' => "{$req['col']}_{$id}_{$e}",
				'type' => $containedNode[$e],
				'path' => "/$id/$e?fields=" . $fieldLists[$containedNode[$e]]
			]);
		}

		/**
		 * Push tagged photos for page.
		 *
		 * Note that `/{page-id}/photos` is default to profile picture
		 * while `/{user-id}/photos` is default to tagged photos.
		 * @see https://developers.facebook.com/docs/graph-api/reference/page/photos
		 */
		if($req['col'] == 'page') {
			push([
				'col' => "{$req['col']}_{$id}_photos",
				'type' => 'photo',
				'path' => "/$id/photos?type=tagged&fields=" . $fieldLists['photo']
			]);
		}
	}

//} ///< while(count($_SESSION['stack']))

	printf("There are %d elements in the stack.\n", count($_SESSION['stack']));
	print_r($_SESSION['stack']);

?>
