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
	$dir = __DIR__ . '/data/json';
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
	 */
	$dir = 'data/js';
	$dest = "{$dir}/{$nodeName}_info.js";
	$zip->addFromString($dest,
		'node.info=' . json_encode($nodeInfo, JSON_UNESCAPED_UNICODE) . ";\n"
	);
	$jss = [$dest];
	foreach($db->getCollectionNames() as $colName) {
		if(strpos($colName, $nodeName) !== 0) continue;
		$col = $db->selectCollection($colName);
		$dest = "data/js/$colName.js";
		$data = iterator_to_array($col->find(), false);
		list( , , $type) = explode('_', $colName);
		$zip->addFromString($dest,
			"node.$type=" . json_encode($data, JSON_UNESCAPED_UNICODE) . ";\n"
		);
		$jss[] = $dest;
	}

	/**
	 * Render `index.html`
	 */
	$html = file_get_contents('index.html');
	$script_tags = "<script>node = {};\ndebug_startTime=new Date;</script>\n";
	for($i = 0; $i < count($jss); ++$i)
		$script_tags .= "<script src=\"{$jss[$i]}\"></script>\n";
	$html = preg_replace('/<!--FBBKTemplateStart(.*)FBBKTemplateEnd-->/s', $script_tags, $html);
	$html = str_replace('<!--EXPORT_TIME-->', date(DATE_ISO8601), $html);
	$zip->addFromString("$nodeName.html", $html);

	$zip->close();


	header('Content-Type: application/zip');
	header("Content-disposition: attachment; filename=$nodeName.zip");
	header('Content-Length: ' . filesize($zipFile));
	readfile($zipFile);
	exit;
?>
