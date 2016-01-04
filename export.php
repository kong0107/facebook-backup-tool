<?php
	require_once 'fb.inc.php';
	require_once 'db.inc.php';

	$adminnedGroups = [];
	if($_SESSION['facebook_access_token']) {
		$userInfo = $fb->get('/me?fields=id,name,link,groups{id},picture{url}')->getDecodedBody();
		if($userInfo['groups'])
			foreach($userInfo['groups']['data'] as $g)
				$adminnedGroups[] = $g['id'];
	}

	/**
	 * Input from $_GET['nodeName'] or $_POST['collections']
	 */
	$input = $_POST['collections'];
	if($nodeName = $_GET['nodeName']) {
		foreach($db->getCollectionNames() as $colName) {
			if(strpos($colName, $nodeName) !== 0) continue;
			list( , , $edge) = explode('_', $colName);
			$input[$nodeName][] = $edge;
		}
		if(!$input[$nodeName]) exit('Error: no collections for the node.');
	}
	if(!$input || !count($input)) exit('Error: no data.');

	/**
	 * Check permissions.
	 */
	foreach(array_keys($input) as $nodeName) {
		list($type, $id) = split('_', $nodeName);
		if($type == 'user' && $id != $userInfo['id'])
			exit('Error: you cannot download other user\'s data.');
		if($type == 'group' && !in_array($id, $adminnedGroups))
			exit('Error: you can only download groups adminned by you.');
	}

	/**
	 * Prepare template for HTML files.
	 */
	$html = file_get_contents('static.html');
	$html = str_replace('<!--EXPORT_TIME-->', date(DATE_ISO8601), $html);

	/**
	 * Open a Zip file and add static files.
	 */
	$dir = $config['data_storage'] . '/archives';
	if(!is_dir($dir)) mkdir($dir, 0777, true);
	$zipFile = "$dir/" . implode('-', array_keys($input)) . '.zip'; ///< a random generated file name would be needed.
	$zip = new ZipArchive;
	$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	$zip->addFile("js/controller.js");
	$zip->addFile("styles/style.css");

	/**
	 * Add files to the zip archive.
	 */
	foreach($input as $nodeName => $edges) {
		list($type, $id) = split('_', $nodeName);
		$nodeInfo = $db->selectCollection($type . 's')->findOne(['_id' => $id]);

		if(!$nodeInfo) continue; ///< Maybe a warning message, but how to show it?

		$dest = "data/js/{$nodeName}_info.js";
		$nodeInfo['fbbk_type'] = $type;
		$json = json_encode($nodeInfo, JSON_UNESCAPED_UNICODE);
		$zip->addFromString("data/json/{$nodeName}_info.json", $json);
		$zip->addFromString($dest, "node.info=$json;\n");
		$jss = [$dest];	///< for inserting `SCRIPT` tags in the HTML file
		$albums = [$nodeName]; ///< for adding image files.

		foreach($edges as $edge) {
			if($edge == 'photoFiles') continue;
			$colName = $nodeName . '_' . $edge;
			$col = $db->selectCollection($colName);
			$dest = "data/js/$colName.js";
			$data = iterator_to_array($col->find(), false);
			$json = json_encode($data, JSON_UNESCAPED_UNICODE);
			$zip->addFromString("data/json/$colName.json", $json);
			$zip->addFromString($dest, "node.$edge=$json;\n");
			$jss[] = $dest;

			if($edge == 'albums') {
				foreach($data as $doc)
					$albums[] = 'album_' . $doc['_id'];
			}
		}

		/**
		 * Render an HTML file to display the node.
		 */
		$script_tags = "<script>
			node = {};
			type = \"$type\";
			debug_startTime = new Date;
		</script>\n";
		foreach($jss as $jsFile)
			$script_tags .= "<script src=\"$jsFile\"></script>\n";
		$zip->addFromString("$nodeName.html", preg_replace(
			'/<!--FBBKTemplateStart(.*)FBBKTemplateEnd-->/s', $script_tags, $html
		));

		/**
		 * Add image files belonging to the node if "photo files" are requested.
		 */
		if($config['enable_photo_download']
			&& in_array('photoFiles', $edges)
		) {
			foreach($albums as $album) {
				$dir = "{$config['data_storage']}/photos/$album";
				if(!is_dir($dir)) continue;
				foreach(scandir($dir) as $filename) {
					if(in_array($filename, array('.', '..'))) continue;
					$zip->addFile("$dir/$filename", "data/photos/$album/$filename");
				}
			}
		}
	}

	$zip->close();

	header('Content-Type: application/zip');
	header('Content-disposition: attachment; filename=' . basename($zipFile));
	header('Content-Length: ' . filesize($zipFile));
	readfile($zipFile);
	if(!$config['reserve_archive_after_download']) unlink($zipFile);
?>
