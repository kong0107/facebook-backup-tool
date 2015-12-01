<?php
	require_once 'db.inc.php';
	$post_id = isset($_GET['id']) ? $_GET['id'] : '299951923544433_401095500096741';
	$page_id = explode('_', $post_id)[0];
	
	$page = $db->page->findOne(array('_id'=> $page_id));
	$post = $db->selectCollection("page_{$page_id}_feed")->findOne(array('_id'=>$post_id));
	
	//$comments = iterator_to_array($db->selectCollection("page_{$page_id}_comment")->find(array('parent'=>$post_id)), false);
?>
<!DOCTYPE HTML>
<html ng-app="myApp" ng-controller="main">
<head>
	<meta charset="utf-8">
	<title ng-bind="page.name"></title>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script>
		angular.module("myApp", []).controller("main", function($scope) {
			$scope.page = <?=json_encode($page)?>;
			$scope.post = <?=json_encode($post)?>;
			$scope.comments = <?=json_encode($comments)?>;
			
			$scope.post.url = $scope.page.link + 'posts/' + $scope.post._id.split('_')[1];
			$scope.tab = 'timeline';
			
			var ct = '&comment_tracking=%7B%22tn%22%3A%22R9%22%7D';
			$scope.getCommentUrl = function(comment_id) {
				return $scope.post.url + '?comment_id=' + comment_id.split('_')[1] + ct;
			}
			$scope.getCCUrl = function(cc_id) {
				var p = cc_id.split('_');
				return $scope.post.url + '?comment_id=' + p[0] + '&reply_comment_id=' + p[1] + ct;
			}
		});
	</script>
	<link rel="stylesheet" href="styles/style.css">
</head>
<body>
	<?=file_get_contents('templates/body_header.html')?>
	<h2>Post</h2>
	<article ng-include="'templates/post_body.html'"></article>
</body>
</html>
