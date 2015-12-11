<?php
	require_once 'fb.inc.php';
	require_once 'db.inc.php';
	if(!isset($_SESSION['stack'])) $_SESSION['stack'] = [];
	if(!is_array($_SESSION['stack'])) exit('Error: stack shall be an array');

	/**
	 * Handling the initial data.
	 */
	/*if($_GET['path'] && $_GET['type']) {
		push($_GET['path'], $_GET['type'],
			$_GET['ancestors'] ? $_GET['ancestors'] : []
		);
		//header('Location: ' . $config['server_root'] . $_SERVER['SCRIPT_NAME']);
	}*/
	if(is_array($_GET['stack'])) {
		foreach($_GET['stack'] as $ele)
			push($ele['path'], $ele['type'], $ele['ancestors']);
		exit("Finished pushing from HTTP get method.");
	}
	if(!count($_SESSION['stack'])) exit('Notice: stack is empty');

	/*$edgeInfo = array(
		'user' => array(
			['type' => 'album', 'subpath' => '/albums'],
			['type' => 'page', 'subpath' => '/likes'],
			['type' => 'post', 'subpath' => '/posts'],
			['type' => 'photo', 'subpath' => '/photos'],
			['type' => 'post', 'subpath' => '/tagged']
		),
		'page' => array(
			['type' => 'album', 'subpath' => '/albums'],
			['type' => 'event', 'subpath' => '/events'],
			['type' => 'video', 'subpath' => '/videos'],
			['type' => 'post', 'subpath' => '/posts'],
			['type' => 'post', 'subpath' => '/tagged'],
			['type' => 'photo', 'subpath' => '/photos?type=tagged']
		),
		'event' => array(
			['type' => 'post', 'subpath' => '/posts'],
			['type' => 'photo', 'subpath' => '/photos'],
			['type' => 'comment', 'subpath' => '/comments'],
			['type' => 'post', 'subpath' => '/feed']
		),
		'group' => array(
			['type' => 'album', 'subpath' => '/albums'],
			['type' => 'event', 'subpath' => '/events'],
			['type' => 'post', 'subpath' => '/feed'],
			['type' => 'user', 'subpath' => '/members'],
			['type' => 'doc', 'subpath' => '/docs']
		)
	);*/

	$maxExeTime = 4; //ini_get('max_execution_time') / 2;
	$sleepTime = 1e+5;
	printf("Time limit: %.2f seconds.\n", $maxExeTime);
	printf("Sleep time setting: %d milliseconds.\n", $sleepTime / 1000);
	printf("Already %.2f seconds passed.\n\n", microtime(true) - $config['startTime']);

	/**
	 * Main algorithm.
	 *
	 * Steps:
	 * 1. Pop an element from the stack.
	 * 2. Request the data and save it to the database.
	 * 3. If it's a node, then push its edges to the stack.
	 * 4. If it's an edge and there's next page, then push the next page.
	 * 5. If it's an edge whose nodes may have comments,
	 *    then push `comments` edges of each node to the stack.
	 * 6. If the stack is not empty, then go to step 1.
	 */
	while($req = end($_SESSION['stack'])) {
		$path = $req['path'];
		$type = $req['type'];
		$ancestors = is_array($req['ancestors']) ? $req['ancestors'] : [];

		echo "Requesting $path\n";
		$s = microtime(true);
		$res = $fb->get($path);
		printf("Got response after %d milliseconds.\n", (microtime(true) - $s) * 1000);
		$res = $res->getDecodedBody();
		if(array_key_exists('data', $res)) {
			echo "Processing edge data ...\n";
			if($next = $res['paging']['next']) {
				echo "Pushing the next page ...\n";
				push($next, $type, $ancestors);
			}
			foreach($res['data'] as $doc) {
				save($doc, $type, $ancestors);
				$newAnc = array_merge($ancestors, [
					['type' => $type, 'id' => $doc['id']]
				]);

				/// Add comments.
				if(in_array('comments', $metadata[$type]['connections'])
					&& $doc['comment_count'] !== 0
				) push("/{$doc['id']}/comments", "comment", $newAnc);

				/// Add photos.
				if($type == 'album')
					push("/{$doc['id']}/photos", "photo", $newAnc);

				/// What about attachments?
			}
		}
		else {
			echo "Processing node data ...\n";
			save($res, $type, $ancestors);
			/*$ancestors[] = array(
				'type' => $type, 'id' => $res['id']
			);
			foreach($edgeInfo[$type] as $edge)
				push("/{$res['id']}{$edge['subpath']}", $edge['type'], $ancestors);*/
		}
		array_pop($_SESSION['stack']);

		$elapsedTime = microtime(true) - $config['startTime'];
		printf("%.2f seconds have passed.\n\n", $elapsedTime);
		if($elapsedTime >= $maxExeTime) break;
		usleep($sleepTime);
	}

	/**
	 * Functions
	 */
	function push($path, $type, $ancestors = []) {
		/**
		 * Handling $path
		 *
		 * @see https://developers.facebook.com/docs/php/Facebook/5.0.0#get
		 */
		$parts = parse_url($path);

		/// Remove the version prefix
		$path = $parts['host'] ? substr($parts['path'], 5) : $parts['path'];

		parse_str($parts['query'], $query);
		unset($query['access_token']);
		if(empty($query['fields']))
			$query['fields'] = implode(',', getFields($type));

		$path = $path . '?' . http_build_query($query);

		echo "Pushing $path\n";
		$_SESSION['stack'][] = [
			'path' => $path,
			'type' => $type,
			'ancestors' => $ancestors
		];
		return count($_SESSION['stack']);
	}
	function save($doc, $type, $ancestors) {
		global $db;
		$doc['fbbk_updated_time'] = date(DATE_ISO8601);
		if(count($ancestors)) {
			$r = $ancestors[0];
			$colName = "{$r['type']}_{$r['id']}_{$type}s";
			if(count($ancestors) > 1)
				$doc['fbbk_parent'] = end($ancestors);
		}
		else $colName = $type . 's';

		if($type == 'photo') {
			/// Download the photo.
			$p = end($ancestors);
			$source = $doc['images'][0]['source'];
			$dir = __DIR__ . "/data/photos/{$p['type']}_{$p['id']}/";
			$dest = $dir . $doc['id'] . '.'
				. pathinfo(parse_url($source, PHP_URL_PATH))['extension']
			;
			if(!file_exists($dest)) {
				if(!is_dir($dir)) mkdir($dir, 0777, true);
				if(copy($source, $dest))
					echo "Successfully downloaded photo to $dest\n";
				else echo "Failed downloading $source to $dest\n";
				usleep(1000);
			}
			unset($doc['images']);
		}

		$doc['_id'] = $doc['id'];
		unset($doc['id']);
		$ret = $db->selectCollection($colName)->update(
			array('_id' => $doc['_id']),
			array_remove_empty($doc),
			array('upsert' => true)
		);
		echo "Updated $type with id {$doc['_id']}\n";
		return $ret;
	}

	printf("\n\n--------\nThere are %d elements in the stack.\n", count($_SESSION['stack']));
	foreach($_SESSION['stack'] as $ele) echo urldecode($ele['path']) . "\n";
	//print_r($_SESSION['stack']);
?>
