<?php
	require_once 'db.php';
	$oldDir = __DIR__ . '/data/photos/';
	foreach($db->listCollections() as $colObj) {
		$colName = $colObj->getName();
		if(!preg_match('/_photos$/', $colName)) continue;
		$col = $db->selectCollection($colName);
		$dir = $oldDir . $colName . '/';
		foreach(iterator_to_array($col->find()) as $photo) {
			$basename = $photo['_id'] . '.jpg';
			if(!file_exists($oldDir . $basename)) continue;
			if(!is_dir($dir)) mkdir($dir, 0777, true);
			echo "Moving file $basename\n";
			rename($oldDir . $basename, $dir . $basename);
		}
	}
?>
