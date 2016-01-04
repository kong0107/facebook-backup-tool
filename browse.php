<?php
	require_once 'fb.inc.php';
	require_once 'db.inc.php';

	$adminnedGroups = [];
	if($_SESSION['facebook_access_token']) {
		$userInfo = $fb->get('/me?fields=id,name,link,groups{id},picture{url}')->getDecodedBody();
		if($userInfo['groups'])
			foreach($userInfo['groups']['data'] as $g)
				$adminnedGroups[] = $g['id'];
	}

	/**
	 * Get info of collections.
	 *
	 * Warning: should notice privacy issue of non-public groups and events.
	 */
	$data = [
		'user' => iterator_to_array($db->users->find(
			['_id' => $userInfo['id']],
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
			/// Get the time the oldest and newest document is updated.
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
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Browse what to export</title>
	<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script>
		angular.module("myApp", []).controller("main", function($scope, $http) {
			$scope.model = <?=json_encode($data,JSON_UNESCAPED_UNICODE)?>;
			$scope.confirmDelete = function(type, id) {
				if(!window.confirm('Sure to delete node ' + id + '?')) return;
				$http.get("delete.php?type=" + type + "&id=" + id).then(function(r) {
					console.log(r);
					delete($scope.model[type][id]);
					if(!$scope.model[type].length) delete($scope.model[type]);
				}, function(r) {
					console.log(r);
				});
			}
		});
	</script>
	<link rel="stylesheet" href="styles/std.css">
	<link rel="stylesheet" href="styles/main.css">
	<style>
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
		section a[href] {
			text-decoration: none;
		}
		section ul {
			margin: 0;
			padding: 0;
			list-style-type: none;
		}
		section span {
			font-size: small;
		}
	</style>
</head>
<body ng-controller="main">
<header ng-include="'templates/header.html'"></header>
<section>
<!-- -->
<h1>Choose what you wanna export</h1>
<details>
	<p>A zip file will be downloaded after submit the form.</p>
	<p>"albums" do not include "photos" automatically; "photos" do not include the source files themselves.</p>
	<p>"comments" (if exists) are for posts, albums, and photos. Even if you select posts and comments, comments for photos will still be in the archive.</p>
	<p>If you are a programmer, JSON files in `data/json` would help you to process the data for further usage.</p>
</details>
<p style="font-weight: bold; color: red;">Copyright belongs to the origin authors. You shall not publish things without permission.</p>
<form action="export.php" method="post">
	<fieldset ng-repeat="(type,nodes) in model track by type" ng-if="'[]'!=(nodes|json)">
		<legend><h2>{{type}}</h2></legend>
		<article ng-repeat="(id,node) in nodes track by id">
			<header class="table">
				<img class="tableCell" ng-src="{{node.picture.data.url}}">
				<div class="tableCell">
					<h3 class="tableCell" title="{{node.bio||node.about}}">
						<a target="_blank" href="http://facebook.com/{{node.username||id}}">{{node.name}}</a>
					</h3>
					<span class="button"
						ng-if="type=='user' || type=='group'"
						ng-click="confirmDelete(type, id)"
					>Delete</span>
				</div>
			</header>
			<ul>
				<li ng-repeat="(edgeName,info) in node.edges track by $index">
					<label title="crawled from {{info.first |date: 'yyyy-MM-dd HH:mm'}} to {{info.last |date: 'yyyy-MM-dd HH:mm'}}">
						<input type="checkbox" name="collections[{{type+'_'+id}}][]" value="{{edgeName}}">
						{{edgeName}}
						<span>({{info.count}})</span>
					</label>
				</li>
				<li ng-if="<?=$config['enable_photo_download']?'node.edges.photos':'false'?>">
					<label title="source files of the photos">
						<input type="checkbox" name="collections[{{type+'_'+id}}][]" value="photoFiles">
						photo files
					</label>
				</li>
			</ul>
		</article>
	</fieldset>
	<input type="submit" value="Download">
</form>
<!-- -->
</section>
<footer ng-include="'templates/footer.html'"></footer>
</body>
</html>
