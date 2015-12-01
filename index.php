<?php
	require_once 'db.inc.php';
	$page_id = '481065478629613';
	$page_id = isset($_GET['id']) ? $_GET['id'] : '299951923544433';
	$page = $db->page->findOne(array('_id'=> $page_id));
	$posts = iterator_to_array($db->selectCollection("page_{$page_id}_post")->find(), false);
?>
<!DOCTYPE HTML>
<html ng-app="myApp" ng-controller="main">
<head>
	<meta charset="utf-8">
	<title ng-bind="page.name"></title>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script>
		angular.module("myApp", []).controller("main", function($scope) {
			$scope.tab = "about";
			$scope.page = <?=json_encode($page)?>;
			$scope.posts = <?=json_encode($posts)?>;
			
			for(var i = 0; i < $scope.posts.length; ++i) {
				$scope.posts[i].url = $scope.page.link
					+ 'posts/'
					+ $scope.posts[i]._id.split("_")[1]
				;
			}
		});
	</script>
	<link rel="stylesheet" href="styles/style.css">
</head>
<body>
	<?=file_get_contents('templates/body_header.html')?>
	<section id="about" ng-show="tab=='about'">
		<h2>About</h2>
		<div ng-if="page.cover">
			<h3>Cover</h3>
			<a ng-attr-href="https://www.facebook.com/photo.php?fbid={{page.cover.id}}">
				<img ng-src="{{page.cover.source}}" width="360">
			</a>
		</div>
		<div ng-if="page.description">
			<h3>Description</h3>
			<?=nl2br($page['description'])?>
		</div>
		<h3>JSON</h3>
		<dl>
			<dt ng-repeat-start="(field, value) in page">{{field}}</dt>
			<dd ng-repeat-end>{{value|json}}</dd>
		</dl>
	</section>
	<section id="timeline" ng-show="tab=='timeline'">
		<h2>Timeline</h2>
		<label>Search: <input ng-model="searchText" ng-model-options="{debounce: 250}" placeholder="search text"></label>
		<article ng-repeat="post in posts | filter: searchText | orderBy: '-created_time'">
			<?=file_get_contents('templates/post_body.html')?>
		</article>
	</section>
</body>
</html>
