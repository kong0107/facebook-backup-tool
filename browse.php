<?php
	require_once 'fb.inc.php';
	require_once 'db.inc.php';

	/**
	 * User must login to browse/download data.
	 */
	if(!$_SESSION['facebook_access_token']) {
		printf('<a href="%s">Login with Facebook</a>', getFBLoginUrl());
		exit;
	}

	$userId = $fb->get('/me?fields=id')->getDecodedBody()['id'];

	/**
	 * Get groups where user is an admin.
	 *
	 * 未來若要開放公開社團給無權限者下載，下載前應確認原社團是否仍處於公開狀態。
	 */
	$adminnedGroups = [];
	$groups = $fb->get('/me/groups')->getDecodedBody()['data'];
	foreach($groups as $g) $adminnedGroups[] = $g['id'];

	/**
	 * Get info of collections.
	 *
	 * Warning: should notice privacy issue of non-public groups and events.
	 */
	$data = [
		'user' => iterator_to_array($db->users->find(
			['_id' => $userId],
			['name' => 1, 'bio' => 1, 'picture.data.url' => 1]
		)),
		'page' => iterator_to_array($db->pages->find(
			[],
			['name' => 1, 'username' => 1, 'category' => 1, 'about' => 1, 'picture.data.url' => 1]
		)),
		'group' => iterator_to_array($db->groups->find(
			['_id' => [ '$in' => $adminnedGroups] ],
			['name' => 1, 'picture.data.url' => 1]
		)),
		'event' => iterator_to_array($db->events->find(
			[],
			['name' => 1, 'picture.data.url' => 1]
		))
	];
	foreach($db->getCollectionNames() as $colName) {
		list($type, $id, $edge) = explode('_', $colName);
		if(!$id) continue;
		if(!array_key_exists($id, $data[$type])) continue;
		$col = $db->selectCollection($colName);
		$count = $col->count();
		if($count) {
			$last = $col->aggregate([['$group' => [
				'_id' => null,
				'last' => ['$max' => '$fbbk_updated_time']
			]]])['result'][0]['last'];
			$first = $col->aggregate([['$group' => [
				'_id' => null,
				'first' => ['$min' => '$fbbk_updated_time']
			]]])['result'][0]['first'];
		}
		else $last = $first = null;

		$data[$type][$id]['edges'][$edge] = [
			'first' => $first,
			'last' => $last,
			'count' => $count
		];
	}
?>
<!DOCTYPE HTML>
<html ng-app="myApp">
<head>
	<meta charset="utf-8">
	<title>Browse what to export</title>
	<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script>
		angular.module("myApp", []).controller("main", function($scope) {
			$scope.model = function() {
//--------
var model = <?=json_encode($data,JSON_UNESCAPED_UNICODE)?>;
return model;
//--------
			}();
		});
	</script>
	<style>
		h1, h2, h3, h4, h5, h6 {
			margin: 0 .25em;
		}
		p {
			margin: .5em 0;
		}
		fieldset {
			clear: both;
		}
		article {
			display: inline-block;
			vertical-align: top;
			width: 360px;
			margin: .5em;
			padding: .5em 0;
		}
		header {
			display: table;
		}
		header > * {
			display: table-cell;
			vertical-align: top;
			margin: .2em;
		}
		a[href] {
			text-decoration: none;
		}
		ul {
			margin: 0;
			padding: 0;
			list-style-type: none;
		}
		span {
			font-size: smaller;
		}
	</style>
</head>
<body ng-controller="main">
	<h1>Choose what to Download</h1>
	<details>
		<p>A zip file will be downloaded after submit the form.</p>
		<p>"albums" do not include "photos" automatically; "photos" do not include the source files themselves; "photo files" may cost much time.</p>
		<p>"comments" are for posts, albums, and photos. Even if you select posts and comments, comments for photos will still be in the archive.</p>
		<p>If you are a programmer, JSON files in `data/json` would help you to process the data for further usage.</p>
	</details>
	<form action="export.php" method="post" target="_blank">
		<fieldset ng-repeat="(type,nodes) in model track by type" ng-if="'[]'!=(nodes|json)">
			<legend><h2>{{type}}</h2></legend>
			<article ng-repeat="(id,node) in nodes track by id">
				<header>
					<img ng-src="{{node.picture.data.url}}">
					<h3 title="{{node.bio||node.about}}">
						<a target="_blank" href="http://facebook.com/{{node.username||id}}">{{node.name}}</a>
					</h3>
				</header>
				<ul>
					<li ng-repeat="(edgeName,info) in node.edges track by $index">
						<label title="crawled from {{info.first |date: 'yyyy-MM-dd HH:mm'}} to {{info.last |date: 'yyyy-MM-dd HH:mm'}}">
							<input type="checkbox" name="collections[{{type+'_'+id}}][]" value="{{edgeName}}">
							{{edgeName}}
							<span>({{info.count}})</span>
						</label>
					</li>
					<li ng-if="node.edges.photos">
						<label title="source files of the photos">
							<input type="checkbox" name="collections[{{type+'_'+id}}][]" value="photoFiles">
							photo files
						</label>
					</li>
				</ul>
			</article>
		</fieldset>
		<input type="submit">
	</form>
</body>
</html>
