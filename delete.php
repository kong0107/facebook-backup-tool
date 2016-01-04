<?php
/**
 * Delete data of a node.
 *
 * Photo files are going to be deleted, too.
 * Zip archives are handled by `$config['reserve_archive_after_download']`.
 */
require_once 'fb.inc.php';
require_once 'db.inc.php';

function outputJSON($arr) {
	exit(json_encode($arr, JSON_UNESCAPED_UNICODE));
}

/**
 * Check permissions.
 */
$adminnedGroups = [];
if($_SESSION['facebook_access_token']) {
	$userInfo = $fb->get('/me?fields=id,groups{id}')->getDecodedBody();
	if($userInfo['groups'])
		foreach($userInfo['groups']['data'] as $g)
			$adminnedGroups[] = $g['id'];
}
else outputJSON([
	'status' => 'error',
	'message' => 'not logged in yet'
]);

switch($_GET['type']) {
	case 'user':
		if($_GET['id'] != $userInfo['id'])
			outputJSON([
				'status' => 'error',
				'message' => 'trying to delete other user\'s profile'
			]);
	break;
	case 'group':
		if(!in_array($_GET['id'], $adminnedGroups))
			outputJSON([
				'status' => 'error',
				'message' => 'trying to delete a group not adminned by the user'
			]);
	break;
	default:
		outputJSON([
			'status' => 'error',
			'message' => 'trying to delete unsupported node type'
		]);
}

/**
 * Started to delete.
 */
$output = [];

foreach($db->getCollectionNames() as $colName) {
	list($type, $id, $edge) = explode('_', $colName);
	if($id != $_GET['id'] || $type != $_GET['type']) continue;
	$col = $db->selectCollection($colName);

	/// Delete photo files in albums.
	if($edge == 'albums') {
		foreach(iterator_to_array($col->find([], ['_id' => 1])) as $album) {
			$album_id = $album['_id'];
			$dir = "{$config['data_storage']}/photos/album_{$album_id}";
			if(!is_dir($dir)) continue;
			foreach(scandir($dir) as $filename) {
				if(in_array($filename, ['.', '..'])) continue;
				if(!unlink("$dir/$filename"))
					$output[] = "failed to delete $dir/$filename .";
			}
			if(!rmdir($dir))
				$output[] = "failed to delete $dir .";
		}
	}

	/// Drop the collection.
	$res = $col->drop();
	if(!$res['ok']) $output[] = "failed to drop $colName.";
}

/// Delete photo files in which the user/page is tagged.
$dir = "{$config['data_storage']}/photos/{$_GET['type']}_{$_GET['id']}";
if(is_dir($dir)) {
	foreach(scandir($dir) as $filename) {
		if(in_array($filename, ['.', '..'])) continue;
		if(!unlink("$dir/$filename"))
			$output[] = "failed to delete $dir/$filename .";
	}
	if(!rmdir($dir))
		$output[] = "failed to delete $dir .";
}

/// Delete the document in the node collection.
$db->selectCollection($_GET['type'] . 's')->remove(
	['_id' => $_GET['id']]
);
///< How to know whether it succeeds?

outputJSON([
	'status' => 'success',
	'messages' => $output
]);

?>
