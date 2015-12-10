<?php
	/**
	 * Export data of a node to JSON files.
	 *
	 * 目標是連同 HTML 檔也打包。
	 */
	require_once 'db.inc.php';
	function echoAndExec($cmd) {
		echo $cmd . "\n";
		exec($cmd, $output, $return_val);
		echo $output . "\n\n";
		return $return_val;
	}
	
	$nodeName = $argv[1] ? $argv[1] : $_GET['nodeName'];
	if(!$nodeName) exit('Missing argument.');
	
	list($type, $id) = split('_', $nodeName);
	
	$pre = 'mongoexport -- db facebook --collection';
	$dir = __DIR__ . '/data/json';
	
	$cmd = "$pre {$type}s --query \"{_id: '$id'}\" --out \"$dir/{$nodeName}_info.json";
	echoAndExec($cmd);
	
	foreach($db->getCollectionNames() as $colName) {
		if(strpos($colName, $nodeName) !== 0) continue;
		$cmd = "$pre $colName --jsonArray --out \"$dir/$colName.json\"";
		echoAndExec($cmd);
	}
?>
