<?php
	$nodeId = $_GET['nodeId'] or exit('`nodeId` required');
	require_once 'fb.php';
	$excludedFields = json_decode(file_get_contents('excludedFields.json'), true);
	
	/**
	 * Remove null, '' and [] in an array recursively.
	 *
	 * Unlike `empty`, this does NOT remove zero and false.
	 */
	function array_remove_empty($arr) {
		foreach($arr as $k => &$v) {
			if(is_array($v)) {
				$v = array_remove_empty($v);
				if(!count($v)) unset($arr[$k]);
				continue;
			}
			if(is_null($v) || $v === '') unset($arr[$k]);
		}
		return $arr;
	}
	
	try {
		$res = $fb->get("/$nodeId?metadata=1")->getDecodedBody();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		exit('Unknown node');
	}
	$nodeId = $res['id'];
	$metadata = $res['metadata'];
	$nodeType = $metadata['type'];
	
	$fields = [];
	$ef = $excludedFields[$nodeType] or $ef = [];
	foreach($metadata['fields'] as $field) {
		$fn = $field['name'];
		if(!in_array($fn, $ef)) $fields[] = $fn;
	}
	
	$reqUrl = "/$nodeId?fields=" . implode(',', $fields);
	$data = array_remove_empty($fb->get($reqUrl)->getDecodedBody());
	
	$ret = file_put_contents(
		"./data/{$nodeType}_{$nodeId}_info.json",
		json_encode($data . "\n", JSON_UNESCAPED_UNICODE)
	);
	echo "$ret bytes were written to the file.";
?>
