if(location.origin == 'http://kong0107.github.io') location.href = 'https://kong0107.github.io/facebook-backup/';

FB.init(FBConfig);

myApp = angular.module("myApp", ["ngRoute"])
.config(function($compileProvider) {
	$compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|blob):/);
})
.controller("header", function($scope) {
	FB.getLoginStatus(function(r) {
		if(!r.authResponse) return;
		FB.apiwt("/me?fields=id,name,link,picture{url}", function(res) {
			$scope.model = {userInfo: res};
			$scope.$apply();
		})
	});
})
.controller("index", function($scope, $routeParams) {
	$scope.disableUserCrawl = true; 	///< remove this line to enable user crawling
	FB.getLoginStatus(function(res) {
		$scope.FBAuth = res.authResponse;
		if($scope.disableUserCrawl) $scope.setType("page");
		$scope.$apply();
	});

	$scope.running = false;
	$scope.message = "";
	$scope.crawlables = window.crawlables;
	$scope.downloadable = false;
	$scope.setCrawl = function(index, withComments) {
		var target = window.crawlables[index];
		var edges = target.edges || [];
		withComments = withComments ? true : false;
		$scope.message = "Crawling ";
		if(withComments) {
			var c = {
				name: "comments", type: "comment", edges: [{
					name: "comments", type: "comment"
				}]
			};
			for(var i = 0; i < edges.length; ++i) {
				if(!edges[i].edges) edges[i].edges = [];
				edges[i].edges.push(c);
			}
			edges.push(c);
			$scope.message += "(comments included) ";
		}
		$scope.running = true;
		var callback = function() {
			$scope.running = false;
			target.status = withComments ? "withComments" : "noComments";
			target.href = URL.createObjectURL(
				new Blob(
					[JSON.stringify(removeEmpty(window.dataset[index]), null, "\t")],
					{type: "application/json"}
				)
			);
			$scope.message = "Finished crawling " + target.name;
			if(withComments) $scope.message += " with comments";
			$scope.downloadable = true;
			console.log(target.href);
			$scope.$apply();
		};

		if(window.dataset[index] && window.dataset[index].length)
			crawlEdges(window.dataset[index], edges, 0, callback);
		else {
			window.dataset[index] = [];
			FB.requestPermissionIfNotPermitted(target.permission, function() {
				window.crawl(window.dataset[index], target.path, target.type, edges, callback);
			}, function() {
				console.error("Permission denied:", target.permission);
				alert("Permission denied.");
				$scope.running = false;
				$scope.$apply();
			});
		}
	};
	$scope.FBLogin = function() {
		FB.login(function(res) {
			$scope.FBAuth = res.authResponse;
			$scope.$apply();
		}, {scope: "user_posts,user_photos"});
	};
	$scope.setType = function(type) {
		window.nodeType = $scope.type = type;
		if(type == "user") {
			FB.apiwt("/me?fields=" + neededFields.user.join(",") + ",picture{url}", function(res) {
				$scope.nodeInfo = res;
				$scope.decideID(res.id);
				$scope.$apply();
			})
		}
	};
	$scope.search = function() {
		$scope.searchResult = [];
		delete $scope.nodeId;
		delete $scope.nodeInfo;
		delete $scope.idDecided;
		var q = $scope.searchText.trim();
		if(!q) return;
		if($.isNumeric(q)) {
			FB.api(q + "?metadata=1", function(r){
				if(r.error) { console.log(r.error); return; }
				if(r.metadata.type != "page") {
					console.log("page is expected but here's a " + r.metadata.type);
					return;
				}
				$scope.searchResult.push(r);
				$scope.$apply();
			});
		}
		else {
			FB.apiwt("search?type=page&q=" + q, function(r) {
				$scope.searchResult = r.data;
				$scope.$apply();
			});
		}
	};
	$scope.preview = function(id) {
		FB.apiwt(id + "?fields=" + neededFields.page.join(",") + ",picture{url}", function(r) {
			$scope.nodeInfo = r;
			$scope.$apply();
		});
	};
	$scope.decideID = function(id) {
		$scope.idDecided = id;
		window.nodeInfo = $scope.nodeInfo;
		var isUser = ($scope.type == "user");
		var subject = isUser ? "I" : "the page";
		var cs = [];

		cs.push({
			name: "已發表的文章\nPosts " + subject + " published",
			alias: "posts",
			path: "/" + id + "/posts",
			type: "post",
			permission: isUser ? "user_posts" : ""
		});

		cs.push({
			name: "相簿與照片資訊（不含照片檔）\nAlbums and photos " + subject + " published",
			alias: "albums_and_photos",
			path: "/" + id + "/albums",
			type: "album",
			permission: isUser ? "user_photos" : "",
			edges: [{name: "photos", type: "photo"}]
		});

		if(isUser) cs.push({
			name: "被標記在內的文章\nPosts " + subject + " was tagged in",
			alias: "tagged_posts",
			path: "/" + id + "/tagged",
			type: "post",
			permission: isUser ? "user_posts" : ""
		});

		cs.push({
			name: "被標記在內的照片資訊（不含圖片檔）\nPhotos " + subject + " was tagged in",
			alias: "tagged_photos",
			path: "/" + id + "/photos?type=tagged",
			type: "photo",
			permission: isUser ? "user_photos" : ""
		});

		$scope.crawlables = window.crawlables = cs;
	};

	window.setStatus = function(status) {
		$scope.status = status;
		$scope.$apply();
	}
	window.addMessage = function(str) {
		$scope.message += str;
		$scope.$apply();
	}

	$scope.downloadHTML = window.downloadHTML;
})
.config(function($routeProvider) {
	$routeProvider
		.when("/index", {
			templateUrl: "templates/index.html",
			controller: "index"
		})
		.when("/:path", {
			templateUrl: function(params){
				var path = params.path;
				if(["help", "about", "terms", "privacy"].indexOf(path) == -1)
					path = "about";
				return "templates/" + path + ".html";
			}
		})
		.otherwise({
			redirectTo: "/index"
		})
	;
});

//---------------------

var neededFields = {};
var dataset = [];
var crawlables = [
	{	name: "Posts I published",
		alias: "posts",
		path: "/me/posts",
		type: "post",
		permission: "user_posts"
	},
	{	name: "Albums and photos I published",
		alias: "albums_and_photos",
		path: "/me/albums",
		type: "album",
		permission: "user_photos",
		edges: [{name: "photos", type: "photo"}]
	},
	/*{	name: "Pages I liked",
		path: "/me/likes",
		type: "page",
		permission: "user_likes"
	},*/
	{	name: "Posts I was tagged in",
		alias: "tagged_posts",
		path: "/me/tagged",
		type: "post",
		permission: "user_posts"
	},
	{	name: "Photos I was tagged in",
		alias: "tagged_photos",
		path: "/me/photos?type=tagged",
		type: "photo",
		permission: "user_photos"
	}
];

$.getJSON("metadata/v2.5.json", function(metadata) {
	$.getJSON("metadata/excludedFields.json", function(excludedFields) {
		for(var nodeType in metadata) {
			var typeInfo = metadata[nodeType];
			typeInfo.neededFields = [];
			if(!Array.isArray(typeInfo.fields)) continue;
			typeInfo.fields.forEach(function(field) {
				if(!Array.isArray(excludedFields[nodeType])
					|| excludedFields[nodeType].indexOf(field.name) == -1
				) typeInfo.neededFields.push(field.name);
			});
			window.neededFields[nodeType] = typeInfo.neededFields;
		}
	});
});

function crawl(storage, path, type, edges, callback) {
	console.log(path);
	if(path.indexOf("fields=") == -1) {
		path += (path.indexOf("?") == -1) ? "?" : "&";
		path += "fields=" + neededFields[type].join(",");
	}
	setTimeout(function() {
		addMessage(".");
		FB.api(path, function(r) {
			if(r.error) {
				console.error(r.error);
				crawl(storage, path, type, edges, callback);
				return;
			}
			if(r.data) {
				for(var i = 0; i < r.data.length; ++i) storage.push(r.data[i]);
				setStatus({
					path: path,
					last: storage.length ? storage[storage.length - 1] : {}
				});
				if(r.paging && r.paging.next) {
					crawl(storage, r.paging.next, type, edges, callback);
					return;
				}
			}
			if(edges && edges.length) crawlEdges(storage, edges, 0, callback);
			else callback();
		});
	}, 500);
}

function crawlEdges(storage, edges, index, callback) {
	if(index == edges.length) return callback();
	crawlEdge(storage, 0, edges[index], function() {
		crawlEdges(storage, edges, index + 1, callback);
	});
}

function crawlEdge(storage, index, edge, callback) {
	if(index == storage.length) return callback();
	if(edge.name == "comments" && storage[index].comment_count === 0)
		return crawlEdge(storage, index + 1, edge, callback);

	storage[index][edge.name] = [];
	crawl(
		storage[index][edge.name],
		"/" + storage[index].id + "/" + edge.name,
		edge.type,
		edge.edges,
		function() {
			crawlEdge(storage, index + 1, edge, callback);
		}
	);
}

function downloadHTML() {
	var now = new Date;
	$.get("static.html?" + now.getTime(), function(html) {
		$.get("js/controller.js?" + now.getTime(), function(js) {
			$.get("styles/style.css?" + now.getTime(), function(css) {
//---------------------
var pre = html.substr(0, html.indexOf("<!--FBBKTemplateStart"));
var post = html
	.substr(html.indexOf("<!--FBBKTemplateEnd") + 22)
	.replace("<" + 'script src="js/controller.js">', "<" + "script>" + js)
	.replace('<link rel="stylesheet" href="styles/style.css">', "<style>" + css + "</style>")
	.replace("<!--EXPORT_TIME-" + "->", now.toISOString())
;

var script = "<" + "script> type = '" + window.nodeType + "';";
var node = {info: window.nodeInfo};
for(var i = 0; i < dataset.length; ++i) {
	if(!dataset[i] || !dataset[i].length) continue;
	var tab = crawlables[i].type + "s";
	if(!node[tab]) node[tab] = [];
	node[tab] = node[tab].concat(dataset[i]);
}
script += "node = " + JSON.stringify(removeEmpty(node)) + ";";
script += "</" + "script>";

html = pre + script + post;

window.html = html;
var a = document.createElement("a");
var blob = new Blob([html], {type: "text/html"});
a.href = URL.createObjectURL(blob);
a.download = "static.html";

/// @see https://developer.mozilla.org/en-US/docs/Web/Guide/Events/Creating_and_triggering_events
var evt = document.createEvent("MouseEvents");
evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
a.dispatchEvent(evt);

//---------------------
			});
		});
	});
}

function isEmptyObj(obj) {
	if(obj === null) return true;
	for(var i in obj) return false;
	return true;
}

function removeEmpty(obj) {
	if(typeof obj != "object" || obj === null) return obj;
	if(Array.isArray(obj)) {
		for(var i = 0; i < obj.length; ++i)
			obj[i] = removeEmpty(obj[i]);
	}
	else {
		for(var i in obj) {
			if(Array.isArray(obj[i])) {
				if(obj[i].length) obj[i] = removeEmpty(obj[i]);
				else delete obj[i];
			}
			else if(typeof obj[i] === "object") {
				obj[i] = removeEmpty(obj[i]);
				if(isEmptyObj(obj[i])) delete obj[i];
			}
			else if(typeof obj[i] === "undefined" || obj[i] === "")
				delete obj[i];

			obj[i] = removeEmpty(obj[i]);
			var isEmpty = true;
		}
	}
	return obj;
}
