<?php
	$nodeId = $_GET['nodeId'] or exit('`nodeId` required');
	require_once 'fb.inc.php';

	try {
		$res = $fb->get("/$nodeId?metadata=1")->getDecodedBody();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		exit('Unknown node');
	}
	$nodeId = $res['id'];
	$nodeType = $res['metadata']['type'];
	
	$fields = getFields($nodeType);
	$reqUrl = "/$nodeId?fields=" . implode(',', $fields);
	$data = array_remove_empty($fb->get($reqUrl)->getDecodedBody());

	$ret = file_put_contents(
		"./data/{$nodeType}_{$nodeId}_info.json",
		json_encode($data, JSON_UNESCAPED_UNICODE) . "\n"
	);
	echo "$ret bytes were written to the file.";
?>
