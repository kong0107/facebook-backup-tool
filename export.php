<?php
	require_once 'db.inc.php';

	$nodeName = $argv[1] ? $argv[1] : $_GET['nodeName'];
	if(!$nodeName) exit('Missing argument.');
	list($type, $id) = split('_', $nodeName);

	$nodeInfo = $db->selectCollection($type . 's')->findOne(array('_id' => $id));
	if(!$nodeInfo) exit('no such node');

	/**
	 * Open a Zip file.
	 */
	$dir = __DIR__ . '/data/archives';
	if(!is_dir($dir)) mkdir($dir, 0777, true);
	$zipFile = "$dir/$nodeName.zip";
	$zip = new ZipArchive;
	$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

	/**
	 * Add static files.
	 */
	$zip->addFile("js/controller.js");
	$zip->addFile("styles/style.css");

	/**
	 * Add dynamic files from database.
	 *
	 * Load every collection about the requested node.
	 * `$jss` is used later for inserting `SCRIPT` tags in `index.html`.
	 * `$albums` is used later for adding photos.
	 */
	$dest = "data/js/{$nodeName}_info.js";
	$json = json_encode($nodeInfo, JSON_UNESCAPED_UNICODE);
	$zip->addFromString("data/json/{$nodeName}_info.json", $json);
	$zip->addFromString($dest, "node.info=$json;\n");
	$jss = [$dest];
	$albums = [$nodeName];
	foreach($db->getCollectionNames() as $colName) {
		if(strpos($colName, $nodeName) !== 0) continue;
		$col = $db->selectCollection($colName);
		$dest = "data/js/$colName.js";
		$data = iterator_to_array($col->find(), false);
		list( , , $edge) = explode('_', $colName);
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
	 * Render `index.html`
	 */
	$html = file_get_contents('index.html');
	$script_tags = "<script>
		node = {};
		type = \"$type\";
		debug_startTime = new Date;
	</script>\n";
	for($i = 0; $i < count($jss); ++$i)
		$script_tags .= "<script src=\"{$jss[$i]}\"></script>\n";
	$html = preg_replace('/<!--FBBKTemplateStart(.*)FBBKTemplateEnd-->/s', $script_tags, $html);
	$html = str_replace('<!--EXPORT_TIME-->', date(DATE_ISO8601), $html);
	$zip->addFromString("$nodeName.html", $html);

	/**
	 * Add photos.
	 */
	foreach($albums as $album) {
		$dir = "data/photos/$album";
		if(!is_dir($dir)) continue;
		foreach(scandir($dir) as $filename) {
			if(in_array($filename, array('.', '..'))) continue;
			$zip->addFile("$dir/$filename");
		}
	}

	$zip->close();

	header('Content-Type: application/zip');
	header("Content-disposition: attachment; filename=$nodeName.zip");
	header('Content-Length: ' . filesize($zipFile));
	readfile($zipFile);
	exit;
?>
