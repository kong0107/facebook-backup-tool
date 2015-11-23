<?php
	require_once 'db.php';
	$page_id = '299951923544433';
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
			//$scope.tab = "timeline";
			$scope.page = <?=json_encode($page)?>;
			$scope.posts = <?=json_encode($posts)?>;

			$scope.createLinkById = function(post_id) {
				var p = post_id.split('_');
				return 'https://www.facebook.com/' + p[0] + '/posts/' + p[1];
			}
		});
	</script>
	<style>
		nav li {
			cursor: pointer;
			display: inline-block;
			border-left: 1px solid #ccc;
			padding: 0.2em;
		}
		.focus {
			font-weight: bold;
		}
		#about dt {
			clear: both;
			float: left;
		}
		#about dd {
			clear: right;
			margin-left: 16em;
			white-space: pre-wrap;
			font-family: monospace;
			margin-bottom: 1em;
		}


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
		.message, fieldset {
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
	</style>
</head>
<body>
	<h1 ng-bind="page.name"></h1>
	<nav>
		<ul>
			<li ng-click="tab='about'" ng-class="tab=='about'?'focus':''">About</li>
			<li ng-click="tab='timeline'" ng-class="tab=='timeline'?'focus':''">Timeline</li>
			<li><a href="{{page.link}}">Facebook</a></li>
		</ul>
	</nav>
	<section id="about" ng-show="tab=='about'">
		<h2>About</h2>
		<div ng-if="page.cover">
			<h2>Cover</h2>
			<a ng-attr-href="https://www.facebook.com/photo.php?fbid={{page.cover.id}}"><img ng-src="{{page.cover.source}}" width="360"></a>
		</div>
		<div ng-if="page.about || page.description">
			<h3>Description</h3>
			<?=nl2br($page['description'] ? $page['description_html'] : $page['about'])?>
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
		</article>
	</section>
</body>
</html>
