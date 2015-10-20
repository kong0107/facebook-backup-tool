<?php
/// Connect to server.
require_once 'db.php';

if(!count($_POST) || !$_POST['id']) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
	$result = array(
		'status'=> 'error',
		'error'	=> array(
			'message'	=> 'You must POST something with id',
			'type'		=> 'client error'
		)
	);
	exit(json_encode($result));
}

$doc = $_POST;
$doc['_id'] = $doc['id'];
unset($doc['id']);

/**
 * Use this if you wanna distinguish update from insert.
 *
$criteria = array('_id' => $doc['_id']);
$result = $criteria;
$result['status'] = 'success';

if($col->findOne($criteria)) {
	$col->update($criteria, $doc);
	$result['operation'] = 'update';
}
else {
	$col->insert($doc);
	$result['operation'] = 'insert';
}
echo json_encode($result);
*/


/**
 * Use this if you don't care whether it's update or insert.
 */
$col = $db->facebook->nodes;
$col->update(
	array('_id' => $doc['_id']), 
	$doc, 
	array('upsert' => true)
);

$result = array(
	'status'=> 'success',
	'_id'	=> $doc['_id']
);
echo json_encode($result);


?>
