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
		angular.module("myApp", []).controller("main", function($scope, $http) {
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
 * Seconds to wait after each success return.
 */
model.interval = 3;

<?php
	/**
	 * Different settings depending on whether stack is empty.
	 *
	 * Show "Do you wanna continue crawling or clear the stack" message
	 * and the corresponding button.
	 */
	if(count($_SESSION['stack'])) {
		echo 'model.showClearButton = true;';
		printf('model.stackCount = %d;', count($_SESSION['stack']));
		echo 'model.status = "crawling";';
	}
	else echo 'model.status = "setting";';
?>

/**
 * ID returned by `window.setTimeout`.
 *
 * Used for recursive call of `model.request`. Assigned to -1
 * if there's no timer but we want one later.
 */
model.timerId = null;

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

/**
 * Step 1: Choose the type of node you wanna crawl.
 *
 * Some initialization is here.
 */
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

/**
 * Step 2: Search for what you want.
 *
 * May be omitted if `nodeType` is "user".
 */
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

/**
 * Step 3: Choose the node you want to crawl.
 *
 * Once selected, show edges for user to check.
 */
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

/**
 * Check if "Start crawl" should be available.
 *
 * Available only if there are more than one edge checked.
 */
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

/**
 * Start crawling.
 */
model.start = function() {
	var nid = model.nodeId;
	var nType = model.nodeType;
	var edgeList = model.edgeLists[nType];
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
	model.timerId = -1;
	model.request(qs);
};

/**
 * Actual function for requesting.
 *
 * Recursive by `setTimeout`.
 */
model.request = function(qs) {
	if(typeof qs != "string") qs = "";
	model.showClearButton = false;
	model.status = "crawling";
	model.lastExecute = (new Date).toISOString();
	$http.get("crawler_dfs.php" + qs).then(function(r) {
		model.message = r.data.message;
		model.stackCount = r.data.stackCount;
		if(r.data.status == "success") {
			console.log(r.data.stack);
			if(model.timerId)
				model.timerId = setTimeout(model.request, model.interval * 1000);
		}
		else {
			model.stop();
			model.status = "finished";
		}
	}, function(r) {
		model.message = JSON.stringify(r, undefined, 4);
	});
};

/**
 * Set re-request automatically.
 */
model.continue = function() {
	model.timerId = -1;
	model.request();
};

/**
 * Stop re-request.
 *
 * This does NOT stop what was sent already.
 */
model.stop = function() {
	if(model.timerId > 0) window.clearTimeout(model.timerId);
	model.timerId = 0;
};

/**
 * Send "clear" message to the server to clear the stack.
 */
model.clearStack = function() {
	$http.get("crawler_dfs.php?clear=1").then(function(r) {
		model.status = "setting";
	}, function(r) {
		model.message = JSON.stringify(r, undefined, 4);
	});
};

/**
 * Download JSON files.
 */
model.download = function() {
	window.open("dbExport.php?nodeName=" + model.nodeType + "_" + model.nodeId);
}


return model;
//--------
			};
		});
	</script>
	<style>
		h1, h2, h3, p { margin: 0.2em; 0; }
		ul { list-style-type: none; }
		label { transition: all 1s; }
		label:hover { background-color: yellow; }
		.inlineBlock { display: inline-block; }
		li.inlineBlock { margin; 0.2em; padding: 0.2em; }
		.pre {
			white-space: pre-wrap;
			font-family: monospace;
		}
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
	<form ng-show="model && model.status=='setting'">
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
	<div ng-show="model && model.status!='setting'">
		<div ng-if="model.showClearButton">
			There are still {{model.stackCount}} elements in the stack.
			You may continue or clear the stack for a new crawling.
			<br>
			<button ng-click="model.clearStack()">Clear Stack</button>
		</div>
		<button ng-click="model.continue()"
			ng-disabled="model.timerId || model.status=='finished'"
		>Continue</button>
		<br>
		<button ng-click="model.stop()" ng-disabled="!model.timerId">Stop</button>
		<p>Last execute: <time ng-bind="model.lastExecute"></time></p>
		<div ng-show="model.status=='finished'">
			<button ng-click="model.download()">Download JSON files</button>
			<button ng-click="model.status='setting'">Crawl something else</button>
		</div>
		<div class="pre" ng-bind="model.message"></div>
		<!--details class="pre"><summary>Debug</summary>{{model|json}}</details-->
	</div>
</body>
</html>
