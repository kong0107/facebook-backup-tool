<?php
	require_once 'db.php';
	$page_id = '299951923544433';
	$post_id = '299951923544433_401095500096741';
	$page = $db->page->findOne(array('_id'=> $page_id));
	$post = $db->selectCollection("page_{$page_id}_post")->findOne(array('_id'=>$post_id));
	$comments = iterator_to_array($db->selectCollection("page_{$page_id}_comment")->find(array('parent'=>$post_id)), false);
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
			$scope.post = <?=json_encode($post)?>;
			$scope.comments = <?=json_encode($comments)?>;
		});
	</script>
	<style>
		article {
			border-top: 1px solid #888;
			margin-top: 1em;
			padding-top: 0.5em;
			line-height: 125%;
			clear: both;
		}
		.metadata {
			text-align: right;
			float: right;
			clear: both;
			background-color: #ccc;
			font-size: smaller;
			padding: 0.25em;
			margin: 0.25em;
		}
		.metadata p {
			margin: 0;
		}
		.message {
			white-space: pre-wrap;
		}
		.link_container {
			clear: both;
			border-left: 2px solid #888;
			margin: 0.5em 0 0 1em;
			padding: 0 0.5em;
		}
		.description {
			white-space: pre-wrap;
			font-size: smaller;
			margin: 0;
		}
		.tags {
			margin: 0.5em 0 0 0;
			padding: 0;
			list-style-type: none;
		}
		.tags li {
			display: inline-block;
			margin: 0.2em;
			padding: 0.2em;
			border-left: 1px solid #ccc;
		}
		.comment {
			clear: both;
		}
		.comment:nth-child(even) {
			background-color: #def;
		}
		.comment:nth-child(odd) {
			background-color: #fed;
		}
		.cc {
			margin-left: 2em;
		}
	</style>
</head>
<body>
	<h1 ng-bind="page.name"></h1>
	<p>{{page.about}}</p>
	<h2>Post</h2>
	<article>
		<div class="metadata">
			<a class="_id" ng-attr-href="{{createLinkById(post._id)}}" name="{{post._id}}">{{post._id}}</a>
			<p>
				published time:
				<time class="created_time" datetime="{{post.created_time}}">{{post.created_time | date: 'yyyy-MM-dd HH:mm:ss'}}</time>
			</p>
			<p ng-if="post.created_time!=post.updated_time">
				newest comment:
				<time class="updated_time" datetime="{{post.updated_time}}">{{post.updated_time | date: 'yyyy-MM-dd HH:mm:ss'}}</time>
			</p>
		</div>
		<div class="story" ng-if="post.story">{{post.story}}</div>
		<div class="message" ng-if="post.message">{{post.message}}</div>
		<div class="link_container" ng-if="post.link">
			<a class="link" href="{{post.link}}">{{post.name}}</a>
			<blockquote class="description">{{post.description}}</blockquote>
		</div>
		<ul class="tags">
			<li ng-repeat="tag in post.story_tags" ng-if="tag.offset && tag.name">
				<a ng-attr-href="{{'https://www.facebook.com/'+tag.id}}">{{tag.name}}</a>
			</li>
			<li ng-repeat="tag in post.message_tags" ng-if="tag.name">
				<a ng-attr-href="{{'https://www.facebook.com/'+tag.id}}">{{tag.name}}</a>
			</li>
			<li ng-repeat="tag in post.with_tags">
				<a ng-attr-href="{{'https://www.facebook.com/'+tag.id}}">{{tag.name}}</a>
			</li>
		</ul>
		<h3 ng-if="post.comment_count">Comments</h3>
		<section class="comment" ng-repeat="comment in comments | orderBy: '-created_time'">
			<div class="metadata">
				<a class="_id" name="{{comment._id}}">{{comment._id}}</a>
				<p>
					published time:
					<time class="created_time" datetime="{{comment.created_time}}">{{comment.created_time | date: 'yyyy-MM-dd HH:mm:ss'}}</time>
				</p>
			</div>
			<div class="from">
				<a ng-attr-href="https://www.facebook.com/{{comment.from.id}}">{{comment.from.name}}</a>
			</div>
			<div class="message" ng-if="comment.message">{{comment.message}}</div>
			<div class="cc" ng-repeat="cc in comment.comments | orderBy: '-created_time'">
				<div class="from">
					<a ng-attr-href="https://www.facebook.com/{{cc.from.id}}">{{cc.from.name}}</a>
				</div>
				<div class="message" ng-if="comment.message">{{cc.message}}</div>
			</div>
		</section>
	</article>
</body>
</html>