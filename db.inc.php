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


function upsert($collect, $arr) {
	global $db;
	$arr['_id'] = $id = $arr['id'];
	unset($arr['id']);
	$db->selectCollection($collect)->update(
		array('_id' => $id),
		$arr,
		array('upsert' => true)
	);
	return $id;
}

?>
