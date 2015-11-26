<?php
	require_once 'fb.php';
	require_once 'db.php';

	switch($_GET['nodeType']) {
		case 'user':
			$nodeType = 'user';
			break;
		case 'page':
			$nodeType = 'page';
			$pageId = $_GET['pageId'];
			break;
		default:
			exit('Unknown or unsupported node type');
	}
	$fieldName = $_GET['fieldName'];

	/**
	 * Request the permission if not authenticated.
	 *
	 * Not finished yet.
	 */
	if(!isset($_SESSION['facebook_access_token'])) {
		header('Location: ' . getFBLoginUrl(array('user_posts')));
		exit;
	}
?>