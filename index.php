<?php
	require_once 'fb.inc.php';
	if(!isset($_SESSION['stack'])
		|| !is_array($_SESSION['stack'])
	) $_SESSION['stack'] = [];
?>
<!DOCTYPE HTML>
<html ng-app="myApp">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=$config['site_name']?></title>
	<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script src="//connect.facebook.net/zh_TW/sdk.js" id="facebook-jssdk"></script>
	<script src="js/fbsdk-config.js"></script>
	<script src="js/fbsdk-extend.js"></script>
	<script>
		FB.init(FBConfig);
		angular.module("myApp", []).controller("main", function($scope, $http) {
			<?php
				if(isset($_SESSION['facebook_access_token'])) {
					?>
					FB.getLoginStatus(function(r) {
						$scope.model = main(r.authResponse ? r.authResponse.userID : "");
						$scope.$apply();
					});
					<?php
				}
				else echo '$scope.model = {};'
			?>

			var main = function(userID) {
//--------
if(!userID) return {};

/**
 * Define binding data and functions.
 *
 * Return `model` to be `$scope.model`. Thus we can
 * use same names here and in HTML.
 */
var model = {};

/**
 * Seconds to wait after each success return.
 */
model.interval = 3;

/**
 * ID returned by `window.setTimeout`.
 *
 * Used for recursive call of `model.request`. Assigned to -1
 * if there's no timer but we want one later.
 */
model.timerId = null;

/**
 * Check if there's unfinished crawling.
 */
model.stack = <?=json_encode($_SESSION['stack'], JSON_UNESCAPED_UNICODE)?>;

/**
 * Initialization.
 *
 * Load user info and adminned groups.
 * Permission "user_managed_groups" would be requested in `model.typeSelected`
 * if it hasn't been granted.
 */
model.userInfo = {id: userID};
FB.apiwt(userID + "?fields=id,name,link,groups{id,name,description},picture{url}", function(r) {
	if(r.groups) r.groups = r.groups.data;
	model.userInfo = r;
	$scope.$apply();
});

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
		{path: "/events?type=created", type: "event", desc: "Events the user created"},
		{path: "/photos?type=tagged", type: "photo", desc: "Photos the user has been tagged in"},
		{path: "/tagged", type: "post", desc: "Posts the user was tagged in"}
	],
	group: [
		{path: "/albums", type: "album", desc: "Albums"},
		{path: "/events", type: "event", desc: "Events within the last two weeks"},
		{path: "/feed", type: "post", desc: "Posts including status updates and links"},
		{path: "/members", type: "user", desc: "Members"},
		{path: "/docs", type: "doc", desc: "Documents"}
	],
	page: [
		{path: "/albums", type: "album", desc: "Albums and photos inside"},
		{path: "/events", type: "event", desc: "Events the page created"},
		{path: "/posts", type: "post", desc: "Posts published by the page"},
		{path: "/photos?type=tagged", type: "photo", desc: "Photos the page is tagged in"},
		{path: "/videos?type=uploaded", type: "video", desc: "Videos the page uploaded"}
	],
	event: [
		/// It seems that edges "posts", "comments", "videos" are always empty,
		/// though stuff in "feed" is posts, including photo posts.
		{path: "/feed", type: "post", desc: "Posts published to the event"},
		{path: "/photos", type: "photo", desc: "Photos published to the event"}
	]
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
		model.nodeList.push(model.userInfo);
		model.nodeId = userID;
		break;
	case "group":
		if(!model.userInfo.groups) {
			FB.ifPermitted("user_managed_groups", 0, function() {
				FB.requestPermission(
					"user_managed_groups",
					function() {
						FB.apiwt("me/groups", function(r) {
							model.nodeList = model.userInfo.groups = r.data;
							$scope.$apply();
						});
					},
					function() {
						alert("This app cannot access groups you managed if permission `user_managed_groups` is not granted.");
					}
				);
			});
		}
		model.nodeList = model.userInfo.groups;
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
	case "event":
		FB.apiwt(model.nodeId + "?fields=id,category,description,end_time,name,start_time,attending_count,picture", function(r) {
			model.nodeInfo = r;
			$scope.$apply();
		});
	case "group":
		for(var i = 0; i < model.userInfo.groups.length; ++i) {
			if(model.userInfo.groups[i].id == model.nodeId) {
				model.nodeInfo = model.userInfo.groups[i];
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
model.isEnqueueButtonDisabled = function() {
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
 * Add what to crawl to the list.
 */
model.enqueue = function() {
	model.showForm = false;
	model.message = "Enqueuing ...";

	var nid = model.nodeId;
	var nType = model.nodeType;
	var edgeList = model.edgeLists[nType];
	var body = {
		op: "enqueue",
		data: [{
			path: "/" + nid,
			type: nType
		}]
	};
	var ancestors = [{type: nType, id: nid}];
	for(var i = 0; i < edgeList.length; ++i) {
		if(model.edgeChecked[i]) {
			body.data.push({
				path: "/" + nid + edgeList[i].path,
				type: edgeList[i].type,
				ancestors: ancestors
			});
		}
	}
	/// Most server-side language does not support `$http.post` of AngularJS.
	/// @see http://stackoverflow.com/questions/19254029
	model.waitingResponse = true;
	$.post("array_op.php", body, function(data) {
		model.message = "Sucessfully enqueued.";
		model.stack = data.stack;
		model.continue();
		$scope.$apply();
	}, "json").fail(function() {
		model.message = JSON.stringify(arguments, undefined, 4);
		model.waitingResponse = false;
		$scope.$apply();
	});
}

/**
 * Actual function for requesting.
 *
 * Recursive by `setTimeout`.
 */
model.request = function() {
	model.lastExecute = new Date;
	model.waitingResponse = true;
	$http.get("crawler.php").then(function(r) {
		model.message = r.data.message;
		model.waitingResponse = false;
		if(r.data.stack) {
			model.stack = r.data.stack;
			if(!model.stack.length) {
				model.stop();
				setTimeout(function(){
					model.message = "Queue clear. All crawled.";
					$scope.$apply();
				}, model.interval * 1000);
			}
		}
		if(model.timerId)
			model.timerId = setTimeout(model.request, model.interval * 1000);
	}, function(r) {
		model.waitingResponse = false;
		model.message = r.statusText;
		if(model.timerId)
			model.timerId = setTimeout(model.request, model.interval * 1000);
	});
};

/**
 * Set re-request automatically.
 */
model.continue = function() {
	if(model.timerId) return;
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
	if(!window.confirm("Sure?")) return;
	$http.get("array_op.php?op=clear").then(function(r) {
		model.stack = [];
	}, function(r) {
		model.message = JSON.stringify(r, undefined, 4);
	});
};

/**
 * Request permission for edges of user.
 */
model.checkPerm = function(index) {
	if(!model.edgeChecked[index]) return;
	if(model.nodeType != "user") return;
	var perms = [];
	switch(model.edgeLists.user[index].type) {
		case "album":
		case "photo":
			perms.push("user_photos"); break;
		case "post": perms.push("user_posts"); break;
		case "page": perms.push("user_likes"); break;
		case "event": perms.push("user_events"); break;
	}
	if(perms.length) FB.ifPermitted(perms, 0, function() {
		FB.login(function() {
			FB.getGrantedPermissions(function(gs){
				if(gs.indexOf(perms[0]) != -1) return;
				alert("This app cannot access some checked edge(s) if permission `" + perms[0] + "` is not granted.");
			});
		}, {scope: perms.toString(), auth_type: "rerequest"});
	});
};

/**
 * Show different parts depending on whether the stack is empty while loading.
 */
if(model.stack.length) model.continue();
else model.showForm = true;

return model;
//--------
			};
		});
	</script>
	<link rel="stylesheet" href="styles/std.css">
	<link rel="stylesheet" href="styles/main.css">
</head>
<body ng-controller="main">
<header ng-include="'templates/header.html'"></header>
<section>
<!-- -->
<div ng-if="!model">Loading ...</div>
<div ng-show="model && !model.userInfo">
	<a href="<?=getFBLoginUrl()?>">Login with Facebook</a>
</div>
<div ng-show="model.userInfo">
	<button ng-hide="model.showForm" ng-click="model.showForm=true">Enqueue something to crawl</button>
	<form ng-show="model.showForm">
		<section>
			<header>
				<h2 style="display: inline-block;">Choose what kind of node to crawl</h2>
				<button ng-show="model.stack.length" ng-click="model.showForm=false">Hide this form</button>
			</header>
			<ul class="inlineList">
				<li ng-repeat="(node, edges) in model.edgeLists track by node" class="inlineBlock">
					<label>
						<input type="radio"
							ng-model="model.nodeType" ng-value="node"
							ng-click="model.typeSelected(node)"
						>{{node}}
					</label>
				</li>
			</ul>
		</section>
		<section ng-show="['page', 'event'].indexOf(model.nodeType) != -1">
			<h2>Search for a {{model.nodeType}}</h2>
			<input ng-model="model.q"
				ng-change="model.search()"
				ng-model-options="{debounce: 500}"
				placeholder="{{model.nodeType}} ID or search text"
			>
			<p ng-show="['page','event'].indexOf(model.nodeType)>=0">
				Public {{model.nodeType}}s are available by searching either ID or name without any permission.
				<br>
				For non-public {{model.nodeType}}s which you are one manager, you shall grant permission
				<button ng-click="FB.requestPermission({page:'manage_pages',event:'user_events'}[model.nodeType])">{{{page:'manage_pages',event:'user_events'}[model.nodeType]}}</button>
				manually, and then search by ID.
			</p>
		</section>
		<section ng-show="model.nodeList.length">
			<h2>Choose which {{model.nodeType}} to crawl</h2>
			<p ng-show="model.nodeType=='user'">
				Only your own data is accessible.
				Note that private posts are also downloaded. Check before you publish them.
			</p>
			<p ng-show="model.nodeType=='group'">
				Only groups in which you are one manager is accessible.
				<br> (Crawling public groups are possible but not implemented yet.
				Crawling non-public groups in which you are not a manager is not possible by Facebook API.)
			</p>
			<ul class="inlineList">
				<li ng-repeat="node in model.nodeList track by node.id" class="inlineBlock">
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
					style="display: table-cell; padding: 0.2em; margin: 0.2em;"
				>
				<div style="display: table-cell; vertical-align: top;">
					<h3><a target="_blank" href="{{model.nodeInfo.link||('http://facebook.com/'+model.nodeInfo.id)}}" style="text-decoration: none;">{{model.nodeInfo.name}}</a></h3>
					<span>{{model.nodeInfo.category}}</span>
				</div>
			</header>
			ID: {{model.nodeInfo.id}}

			<!-- For Pages -->
			<p ng-if="model.nodeInfo.likes">{{model.nodeInfo.likes |number}} likes</p>

			<!-- For events -->
			<p ng-if="model.nodeInfo.attending_count">{{model.nodeInfo.attending_count |number}} attendees</p>
			<p ng-if="model.nodeInfo.start_time">From {{model.nodeInfo.start_time |date : 'yyyy-MM-dd HH:mm'}}</p>
			<p ng-if="model.nodeInfo.end_time">To {{model.nodeInfo.end_time |date : 'yyyy-MM-dd HH:mm'}}</p>

			<div style="white-space: pre-wrap; max-height: 8em; overflow: auto; border-top: 1px dashed #ccc;">{{(
				model.nodeInfo.description
				? model.nodeInfo.description
				: model.nodeInfo.about
			)}}</div>
		</section>
		<section ng-show="model.nodeId">
			<h2>Choose which edges to crawl</h2>
			<ul style="list-style-type: none;">
				<li ng-repeat="edgeInfo in model.edgeLists[model.nodeType] track by $index">
					<label>
						<input type="checkbox" ng-model="model.edgeChecked[$index]"
							ng-click="model.checkPerm($index)"
						>{{edgeInfo.desc}}
						<code>({{edgeInfo.path}})</code>
					</label>
				</li>
			</ul>
		</section>
		<button ng-disabled="model.isEnqueueButtonDisabled()" ng-click="model.enqueue()">Enqueue and crawl</button>
	</form>
	<hr>
	<div ng-show="model.lastExecute">
		<h2>Crawling message</h2>
		<button ng-click="model.continue()" ng-disabled="model.timerId">Continue</button>
		<button ng-click="model.stop()" ng-disabled="!model.timerId">Stop</button>
		<button ng-click="model.clearStack()"
			ng-disabled="model.waitingResponse || model.timerId || !model.stack.length"
		>Clear</button>
		<p>Last execute: <time ng-bind="model.lastExecute |date :'HH:mm:ss.sss'"></time></p>
	</div>
	<div ng-show="model.stack.length">
		<h2>{{model.stack.length}} in list</h2>
		<ol style="overflow: auto; height: 8em; resize: vertical;">
			<li ng-repeat="ele in model.stack track by ele.path"
			>{{ele.path}}</li>
		</ol>
	</div>
	<div class="rawdata" ng-bind="model.message"></div>
</div>

<!-- -->
</section>
<footer ng-include="'templates/footer.html'"></footer>
</body>
</html>
