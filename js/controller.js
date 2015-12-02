angular.module("myApp", [])
.config(function($locationProvider) {
	$locationProvider.html5Mode({
		enabled: true,
		requireBase: false
	});
})
.controller("main", function($scope, $http, $location, $window) {
	$scope.ret = function() {
		/// Initialization
		var id = $window.id;
		var type = $window.type;
		var notFound = function(r) {
			//console.log(r.status + " " + r.statusText + " on " + r.config.url);
		}
		var tabs = [//"info",
			"albums", "likes", "events",
			"feed", "posts", "photos", "videos", "tagged",
			"members", "docs", "comments"
		];
		var ret = {
			tabs: tabs,
			tab: "info",
			months: {},
			month: {},
			search: {},
			photosInAlbum: {}
		};

		/**
		 * Get distinct month of `created_time` in an array.
		 */
		var groupByMonth = function(arr, tab) {
			arr.sort(function(a,b) {
				return new Date(b.created_time) - new Date(a.created_time);
			});
			var ms = [];
			for(var i = 0; i < arr.length; ++i)
				ms.push(arr[i].created_time.substr(0, 7));
			ret.months[tab] = $.unique(ms);
			ret.month[tab] = ret.months[tab][0];
			ret.search[tab] = "";
		}

		/**
		 * Get info and edges.
		 */
		for(var i = 0; i < tabs.length; ++i) ret[tabs[i]] = [];
		$http.get("data/json/" + type + "_" + id +"_info.json")
		.then(function(r) {
			ret.info = r.data;
			for(var i = 0; i < tabs.length; ++i) {
				(function() {
					var tab = tabs[i];
					$http.get("data/json/"
						+ type + "_" + id + "_" + tab
						+ ".json"
					).then(function(r) {
						ret[tab] = r.data;
						if(tab == "posts" || tab == "feed" || tab == "comment")
							groupByMonth(ret[tab], tab);
						console.log("Edge " + tab + " is loaded.");
					}, notFound);
				})();
			}
		}, notFound);

		/**
		 * Create a link to Facebook by ID of the node.
		 */
		ret.createLink = function(id, type) {
			if(typeof id == "number") id += "";
			else if(typeof id != "string") id = "";
			else if(typeof type != "undefined") 
				id = id.replace("_", "/" + type + "/");
			return "https://www.facebook.com/" + id;
		};
		
		/**
		 * Get photos in a album.
		 */
		ret.getPhotosInAlbum = function(id) {
			if($.isArray(ret.photosInAlbum[id])) return;
			$http.get("data/json/album_" + id + "_photos.json")
			.then(function(r) {
				ret.photosInAlbum[id] = r.data;
			}, notFound);
		}
		
		/**
		 * Just for debug
		 */
		ret.templates = [
			"header",
			"post",
			"album",
			"comment",
			"photo",
			"attachment",
			"event",
			"place"
		];

		return ret;
	}();
})
.filter("capitalize", function() {
	return function(input) {
		return (!!input) ? input.charAt(0).toUpperCase() + input.substr(1) : '';
	};
});
