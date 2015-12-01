<?php
	require_once 'db.inc.php';
	$r = iterator_to_array(
		$db->selectCollection($_GET['col'])->find()
		->sort(array('created_time'=>-1))
	, false);
	
	header('Content-Type: application/json');
	header('Content-Disposition: attachment; filename="' . $_GET['col'] . '.json"');
	echo json_encode($r, JSON_UNESCAPED_UNICODE);
?>
