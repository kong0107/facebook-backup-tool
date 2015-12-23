<?php
	session_start();
	require_once 'db.inc.php';

	$data = $_REQUEST['data'];

	$target = &$_SESSION['stack'];
	if(!$target || !is_array($target)) $target = [];

	switch($_REQUEST['op']) {
	case 'push':
		$target = array_merge($target, $data);
		break;
	case 'enqueue':
		$target = array_merge(array_reverse($data), $target);
		break;
	case 'clear':
		$target = [];
		break;
	default:
		exit(json_encode([
			'status' => 'error',
			'message' => 'unknown operation'
		]));
	}

	echo json_encode([
		'status' => 'success',
		'stackCount' => count($target),
		'stack' => $target
	]);
?>
