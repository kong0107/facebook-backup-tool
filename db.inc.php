<?php
try {
	$dbCon = new MongoClient();
} catch(MongoConnectionException $e) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	$result = array(
		'status'=> 'error',
		'error'	=> array(
			'message'	=> $e->getMessage(),
			'type'		=> 'MongoConnectionException'
		)
	);
	exit(json_encode($result));
}
$db = $dbCon->facebook;

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

function upsert($collect, $doc) {
	global $db;
	$doc['_id'] = $id = $doc['id'];
	unset($doc['id']);
	$doc['fbbk_updated_time'] = date(DATE_ISO8601);
	$db->selectCollection($collect)->update(
		array('_id' => $id),
		array_remove_empty($doc),
		array('upsert' => true)
	);
	return $id;
}

?>
