<?php
	/**
	 * Export data of a node to JSON files.
	 *
	 * 目標是連同 HTML 檔也打包。
	 */
	require_once 'db.inc.php';
	function echoAndExec($cmd) {
		//echo $cmd . "\n";
		exec($cmd, $output, $return_val);
		//echo $output . "\n\n";
		return $return_val;
	}

	$nodeName = $argv[1] ? $argv[1] : $_GET['nodeName'];
	if(!$nodeName) exit('Missing argument.');

	list($type, $id) = split('_', $nodeName);

	$pre = 'mongoexport -- db facebook --collection';
	$dir = __DIR__ . '/data/json';
	if(!is_dir($dir)) mkdir($dir, 0777, true);
	$fileList = [];

	$dest = "$dir/{$nodeName}_info.json";
	$fileList[] = $dest;
	$cmd = "$pre {$type}s --query \"{_id: '$id'}\" --out \"$dest\"";
	echoAndExec($cmd);

	foreach($db->getCollectionNames() as $colName) {
		if(strpos($colName, $nodeName) !== 0) continue;
		$dest = "$dir/$colName.json";
		$fileList[] = $dest;
		$cmd = "$pre $colName --jsonArray --out \"$dest\"";
		echoAndExec($cmd);
	}

	$dir = __DIR__ . '/data/archives';
	if(!is_dir($dir)) mkdir($dir, 0777, true);
	$dest = "$dir/$nodeName.zip";
	$zip = new ZipArchive();
	$zip->open($dest, ZipArchive::OVERWRITE);
	foreach($fileList as $file)
		$zip->addFile($file, basename($file));
	$zip->close();

	header('Content-Type: application/zip');
	header("Content-disposition: attachment; filename=$nodeName.zip");
	header('Content-Length: ' . filesize($dest));
	readfile($dest);
?>