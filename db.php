<?php
try {
	$db = new MongoClient();
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
?>
