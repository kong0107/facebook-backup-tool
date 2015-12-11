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

/**
 * Initialization.
 *
 * Load user info and adminned groups.
 */
var userInfo = {};
FB.apiwt(userID + "?fields=id,name,groups{id,name,description}", function(r) {
	r.groups = r.groups.data;
	userInfo = r;
});


/**
 * Define binding data and functions.
 *
 * Return `model` to be `$scope.model`. Thus programmers can
 * use same names here and in HTML.
 */
var model = {};

/**
 * Edges available for each type of node.
 *
 * @see https://developers.facebook.com/docs/graph-api/reference
 */
model.edgeLists = {
	user: [
		{path: "/albums", type: "album", desc: "Albums and photos inside"},
		{path: "/posts", type: "post", desc: "Posts published by the user"},
		{path: "/likes", type: "page", desc: "Liked pages"},
		//{path: "/events", type: "event", desc: "There are different types of events."},
		{path: "/photos?type=tagged", type: "photo", desc: "Photos the user has been tagged in"},
		{path: "/tagged", type: "post", desc: "Posts the user was tagged in"}
	],
	page: [
		{path: "/albums", type: "album", desc: "Albums and photos inside"},
		{path: "/events", type: "event", desc: "Events the page created"},
		{path: "/posts", type: "post", desc: "Posts published by the page"},
		{path: "/photos?type=tagged", type: "photo", desc: "Photos the page is tagged in"},
		{path: "/videos?type=uploaded", type: "video", desc: "Videos the page uploaded"}
	],
	group: [
		{path: "/albums", type: "album", desc: "Albums"},
		{path: "/events", type: "event", desc: "Events within the last two weeks"},
		{path: "/feed", type: "post", desc: "Posts including status updates and links"},
		{path: "/members", type: "user", desc: "Members"},
		{path: "/docs", type: "doc", desc: "Documents"}
	]/*,
	event: [
		"posts", "photos", "comments", "feed"//, "videos"
	]*/
};

model.typeSelected = function(nodeType) {
	model.nodeList = [];
	model.q = "";
	model.nodeId = "";
	model.nodeInfo = null;
	model.edgeChecked = [];
	switch(nodeType) {
	case "user":
		model.nodeList.push(userInfo);
		model.nodeId = userID;
		break;
	case "group":
		model.nodeList = userInfo.groups;
		break;
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
	switch(model.nodeType) {
	case "page":
		FB.apiwt(model.nodeId + "?fields=id,name,category,about,description,likes,link,picture", function(r) {
			model.nodeInfo = r;
			$scope.$apply();
		});
		break;
	case "group":
		for(var i = 0; i < userInfo.groups.length; ++i) {
			if(userInfo.groups[i].id == model.nodeId) {
				model.nodeInfo = userInfo.groups[i];
				break;
			}
		}
		break;
	}
};

model.isButtonDisabled = function() {
	if(!model.nodeId) return true;
	var hasCheckedEdge = false;
	for(var i = 0; i < model.edgeChecked.length; ++i) {
		if(model.edgeChecked[i]) {
			hasCheckedEdge = true;
			break;
		}
	}
	return !hasCheckedEdge;
};

model.start = function() {
	var nid = model.nodeId;
	var nType = model.nodeType;
	var edgeList = model.edgeLists[nType];
	/*var ancestors = [{type: nType, id: nid}];
	var ret = [{path: "/" + nid, type: nType}];
	for(var i = 0; i < edgeList.length; ++i) {
		if(model.edgeChecked[i]) {
			ret.push({
				path: "/" + nid + edgeList[i].path,
				type: edgeList[i].type,
				ancestors: ancestors
			});
		}
	}
	console.log(ret);*/
	//window.open("http://kong-guting.zapto.org/test.php?json=" + JSON.stringify(ret));

	var qs = "?stack[0][path]=/" + nid + "&stack[0][type]=" + nType;
	var counter = 1;
	for(var i = 0; i < edgeList.length; ++i) {
		if(model.edgeChecked[i]) {
			var prefix = "&stack[" + counter + "]";
			qs += prefix + "[path]=/" + nid + edgeList[i].path
				+ prefix + "[type]=" + edgeList[i].type
				+ prefix + "[ancestors][0][type]=" + nType
				+ prefix + "[ancestors][0][id]=" + nid
			;
			++counter;
		}
	}
	console.log(qs);
	window.open("http://kong-guting.zapto.org/facebook-backup/crawler_dfs.php" + qs);
	window.alert("This page is not finished yet.");
	//
	// OK let's do what's in `crawler_dfs.html`
	//
};

return model;
//--------
			};
		});
	</script>
	<!--link rel="stylesheet" href="styles/style.css"-->
	<style>
		h1, h2, h3, p { margin: 0.2em; 0; }
		ul { list-style-type: none; }
		label { transition: all 1s; }
		label:hover { background-color: yellow; }
		.inlineBlock { display: inline-block; }
		li.inlineBlock { margin; 0.2em; padding: 0.2em; }
	</style>
</head>
<body ng-controller="main">
	<h1>Backup from Facebook</h1>
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
			<h2>Search for a page</h2>
			<input ng-model="model.q"
				ng-change="model.search()"
				ng-model-options="{debounce: 500}"
				placeholder="page ID or search text"
			>
		</section>
		<section ng-show="model.nodeList.length">
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
				<img ng-if="model.nodeInfo.picture"
					ng-src="{{model.nodeInfo.picture.data.url}}"
					style="display: table-cell; padding: 0.1em; margin: 0.1em;"
				>
				<div style="display: table-cell; vertical-align: top;">
					<h3><a target="_blank" href="{{model.nodeInfo.link}}" style="text-decoration: none;">{{model.nodeInfo.name}}</a></h3>
					<span>{{model.nodeInfo.category}}</span>
				</div>
			</header>
			<p ng-if="model.nodeInfo.likes">{{model.nodeInfo.likes|number}} likes</p>
			<div style="white-space: pre-wrap; max-height: 8em; overflow: auto; border-top: 1px dashed #ccc;">{{(
				model.nodeInfo.description
				? model.nodeInfo.description
				: model.nodeInfo.about
			)}}</div>
		</section>
		<section ng-show="model.nodeId">
			<h2>Choose which edges to crawl</h2>
			<ul>
				<li ng-repeat="edgeInfo in model.edgeLists[model.nodeType]">
					<label>
						<input type="checkbox" ng-model="model.edgeChecked[$index]"
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
