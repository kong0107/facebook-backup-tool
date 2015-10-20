<?php
/// Connect to server.
require_once 'db.php';
$col = $db->facebook->nodes;

//$col->drop();

$result = array(
	'status'=> 'success',
	'count'	=> $col->count(),
	'data'	=> array_values(iterator_to_array($col->find()))
);
echo json_encode($result);
?>
