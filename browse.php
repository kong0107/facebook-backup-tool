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
	 */
	//$data = ['users' => [], 'pages' => [], 'groups' => []];
	$data = [
		'users' => iterator_to_array($db->users->find(
			['_id' => $userId],
			['name' => 1, 'bio' => 1, 'picture.data.url' => 1]
		)),
		'pages' => iterator_to_array($db->pages->find(
			[],
			['name' => 1, 'username' => 1, 'category' => 1, 'about' => 1, 'picture.data.url' => 1]
		)),
		'groups' => iterator_to_array($db->groups->find(
			['_id' => [ '$in' => $adminnedGroups] ],
			['name' => 1, 'picture.data.url' => 1]
		))
		/// what about non-public events?
	];
	foreach($db->getCollectionNames() as $colName) {
		list($type, $id, $edge) = explode('_', $colName);
		if(!$id) continue;
		$count = $db->selectCollection($colName)->count();
		$data[$type . 's'][$id]['counts'][$edge] = $count;
		/// We should show `fbbk_updated_time` also, to let users make decision whether to crawl again.
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
		fieldset {
			clear: both;
		}
		article {
			float: left;
			width: 360px;
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
		p {
			margin: 0;
			font-size: smaller;
		}
		ul {
			margin: 0;
			padding: 0;
			list-style-type: none;
		}
	</style>
</head>
<body ng-controller="main">
	<h1>Choose what to Download</h1>
	<form action="browse.php">
		<fieldset ng-repeat="(type,nodes) in model track by type">
			<legend><h2>{{type}}</h2></legend>
			<article ng-repeat="(id,node) in nodes track by id">
				<header>
					<img ng-src="{{node.picture.data.url}}">
					<h3 title="{{node.bio||node.about}}">
						<a href="http://facebook.com/{{node.username||id}}">{{node.name}}</a>
					</h3>
				</header>
				<ul>
					<li ng-repeat="(edge,count) in node.counts track by $index">
						<label>
							<input type="checkbox" name="options[]" value="{{type+'_'+id+'_'+edge}}">
							{{edge}} ({{count}})
						</label>
					</li>
				</ul>
			</article>
		</fieldset>
		<input type="submit">
	</form>
	<pre><?=print_r($_GET,true)?></pre>
	<!--pre>{{model|json}}</pre-->
</body>
</html>
