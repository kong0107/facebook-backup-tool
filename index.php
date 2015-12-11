<?php
	require_once 'fb.inc.php';
?>
<!DOCTYPE HTML>
<html ng-app="myApp">
<head>
	<meta charset="utf-8">
	<title>Login Facebook</title>
	<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script src="//connect.facebook.net/zh_TW/sdk.js" id="facebook-jssdk"></script>
	<script src="js/fbsdk-config.js"></script>
	<script>
		angular.module("myApp", []).controller("main", function($scope) {
			window.fbAsyncInit = function() {
				FB.init(FBConfig);
				FB.getLoginStatus(function(r) {
					$scope.model = main(r.authResponse ? r.authResponse.userID : "");
					$scope.$apply();
				});
			};
			$.getScript("js/fbsdk-extend.js");
			var main = function(userID) {
//--------
if(!userID) return {};
var model = {};

var userInfo = {};
FB.apiwt(userID + "?fields=id,name,groups", function(r) {
	r.groups = r.groups.data;
	userInfo = r;
});

model.nodeList = [];

model.edgeLists = {
	user: [
		{path: "/albums", desc: "Albums and photos inside"},
		{path: "/posts", desc: "Posts published by the user"},
		{path: "/likes", desc: "Liked pages"},
		//{path: "/events", desc: "See https://developers.facebook.com/docs/graph-api/reference/user/events/"},
		{path: "/photos?type=tagged", desc: "Photos the user has been tagged in"},
		{path: "/tagged", desc: "Posts the user was tagged in"}
	],
	page: [
		{path: "/albums", desc: "Albums and photos inside"},
		{path: "/events", desc: "Events the page created"},
		{path: "/posts", desc: "Posts published by the page"},
		{path: "/photos?type=tagged", desc: "Photos the page is tagged in"},
		{path: "/videos?type=uploaded", desc: "Videos the page uploaded"}
	]/*,
	group: [
		"albums", "events", "feed",
		"members", "docs"//, "videos", "files"
	],
	event: [
		"posts", "photos", "comments", "feed"//, "videos"
	]*/
};

model.typeSelected = function(nodeType) {
	model.nodeList = [];
	model.q = "";
	model.nodeId = "";
	model.nodeInfo = null;
	model.edgeChecked = {};
	if(nodeType == "user") {
		model.nodeList.push(userInfo);
		model.nodeId = userID;
	}
};

model.search = function() {
	model.nodeList = [];
	model.nodeId = "";
	model.nodeInfo = null;
	var q = model.q.trim();
	if(!q.length) return;
	if($.isNumeric(q)) {
		FB.api(q + "?metadata=1", function(r){
			if(r.error) { console.log(r.error); return; }
			if(r.metadata.type != model.nodeType) {
				console.log(model.nodeType + " is expected but here's a " + r.metadata.type);
				return;
			}
			model.nodeList.push(r);
			$scope.$apply();
		});
	}
	else {
		FB.apiwt("search?type=" + model.nodeType + "&q=" + q, function(r) {
			model.nodeList = r.data;
			$scope.$apply();
		});
	}
};

model.nodeSelected = function() {
	if(model.nodeType == "page") {
		FB.apiwt(model.nodeId + "?fields=id,name,category,about,description,likes,link,picture", function(r) {
			model.nodeInfo = r;
			$scope.$apply();
		});
	}
};

model.isButtonDisabled = function() {
	if(!model.nodeId) return true;
	var hasCheckedEdge = false;
	for(var i in model.edgeChecked) {
		if(!model.edgeChecked[i]) continue;
		hasCheckedEdge = true;
	}
	return !hasCheckedEdge;
};

model.start = function() {
	window.alert("This page is not finished yet.");
};

return model;
//--------
			};
		});
	</script>
	<link rel="stylesheet" href="styles/style.css">
</head>
<body ng-controller="main">
	<h1>Download data from Facebook</h1>
	<?php
		if(!$_SESSION['facebook_access_token']) {
			printf('<a href="%s">Login with Facebook</a>', getFBLoginUrl());
			echo '</body></html>';
			exit;
		}
	?>
	<div ng-if="!model">Loading ...</div>
	<form ng-show="model">
		<section>
			<h2>Choose what kind of node to crawl</h2>
			<ul>
				<li ng-repeat="(node, edges) in model.edgeLists" class="inlineBlock">
					<label>
						<input type="radio" 
							ng-model="model.nodeType" ng-value="node"
							ng-click="model.typeSelected(node)"
						>{{node}}
					</label>
				</li>
			</ul>
		</section>
		<section ng-show="model.nodeType=='page'">
			<h2>Search for a page to crawl</h2>
			<input ng-model="model.q" 
				ng-change="model.search()" 
				ng-model-options="{debounce: 500}" 
				placeholder="page ID or search text"
			>
		</section>
		<section ng-show="model.nodeType=='page' && model.nodeList.length">
			<h2>Choose which {{model.nodeType}} to crawl</h2>
			<ul>
				<li ng-repeat="node in model.nodeList" class="inlineBlock">
					<label>
						<input type="radio" ng-model="model.nodeId" ng-value="node.id"
							ng-click="model.nodeSelected(node.id)"
						>{{node.name}}
					</label>
				</li>
			</ul>
		</section>
		<section ng-show="model.nodeInfo" style="border: 1px solid #ccc; padding: 0.2em; margin: 0.2em;">
			<header style="display: table;">
				<img ng-src="{{model.nodeInfo.picture.data.url}}" style="display: table-cell; padding: 0.1em; margin: 0.1em;">
				<div style="display: table-cell; vertical-align: top;">
					<h3><a target="_blank" href="{{model.nodeInfo.link}}">{{model.nodeInfo.name}}</a></h3>
					<span>{{model.nodeInfo.category}}</span>
				</div>
			</header>
			<p>{{model.nodeInfo.likes|number}} likes</p>
			<div style="white-space: pre-wrap;">{{(
				model.nodeInfo.description
				? model.nodeInfo.description
				: model.nodeInfo.about
			)|limitTo:100}}</div>
		</section>
		<section ng-show="model.nodeId">
			<h2>Choose which edges to crawl</h2>
			<ul>
				<li ng-repeat="edgeInfo in model.edgeLists[model.nodeType]">
					<label>
						<input type="checkbox" ng-model="model.edgeChecked[edgeInfo.path]"
						>{{edgeInfo.desc}}
						<code>({{edgeInfo.path}})</code>
					</label>
				</li>
			</ul>
		</section>
		<button ng-disabled="model.isButtonDisabled()" ng-click="model.start()">Start crawl</button>
	</form>
	<details style="white-space: pre-wrap; font-family: monospace;"
	><summary>Debug</summary>{{model|json}}</details>
</body>
</html>
