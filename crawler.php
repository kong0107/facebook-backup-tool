<?php
	require_once 'fb.inc.php';
	require_once 'db.inc.php';
	
	/**
	 * JSON output functions and variables.
	 */
	$output = ['message' => ''];
	function stop($message, $status = 'error') {
		exit(json_encode([
			'status' => $status,
			'message' => $message
		]));
	}
	function p($str = '') {
		global $output;
		$output['message'] .= $str . "\n";
	}
	
	/**
	 * Basic checkings.
	 */
	if(!isset($_SESSION['stack'])) $_SESSION['stack'] = [];
	if(!is_array($_SESSION['stack'])) stop('Error: stack shall be an array');

	/**
	 * Handling the initial data.
	 */
	if(is_array($_GET['stack'])) {
		foreach($_GET['stack'] as $ele)
			push($ele['path'], $ele['type'], $ele['ancestors']);
		exit(json_encode([
			'status' => 'success',
			'message' => 'Finished pushing from HTTP get method.',
			'stackCount' => count($_SESSION['stack'])
		]));
	}
	if($_GET['clear']) {
		$_SESSION['stack'] = [];
		stop('Stack cleared.', 'warning');
	}
	if(!count($_SESSION['stack'])) stop('Stack is empty', 'warning');

	$maxExeTime = 2; //ini_get('max_execution_time') / 2;
	$sleepTime = 1e+5;
	p(sprintf('Time limit: %.2f seconds.', $maxExeTime));
	p(sprintf('Sleep time setting: %d milliseconds.', $sleepTime / 1000));
	p(sprintf("Already %.2f seconds passed.\n", microtime(true) - $config['startTime']));

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
	while($req = array_pop($_SESSION['stack'])) {
		$path = $req['path'];
		$type = $req['type'];
		$ancestors = is_array($req['ancestors']) ? $req['ancestors'] : [];

		p("Requesting $path");
		$s = microtime(true);
		$res = $fb->get($path);
		p(sprintf('Got response after %d milliseconds.', (microtime(true) - $s) * 1000));
		$res = $res->getDecodedBody();
		if(array_key_exists('data', $res)) {
			p('Processing edge data ...');
			if($next = $res['paging']['next']) {
				p('Pushing the next page ...');
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

				/// Add photos for albums.
				if($type == 'album')
					push("/{$doc['id']}/photos", "photo", $newAnc);

				/// What about photo in comment?
			}
		}
		else {
			p('Processing node data ...');
			save($res, $type, $ancestors);
		}

		$elapsedTime = microtime(true) - $config['startTime'];
		p(sprintf("%.2f seconds have passed.\n", $elapsedTime));
		if($elapsedTime >= $maxExeTime) break;
		usleep($sleepTime);
	}
	/**
	 * Output a JSON string.
	 */
	$output['status'] = 'success';
	$output['stackCount'] = count($_SESSION['stack']);
	$output['stack'] = $_SESSION['stack'];
	echo json_encode($output);

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

		p("Pushing $path");
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
					p("Successfully downloaded photo to $dest");
				else p("Warning: Failed downloading $source to $dest");
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
		p("Updated $type with id {$doc['_id']}.");
		return $ret;
	}
?>
