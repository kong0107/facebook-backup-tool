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
		var tabs = $window.tabs || [//"info",
			"albums", "likes", "events",
			"feed", "posts", "photos", "videos", "tagged",
			"members", "docs", "comments"
		];
		var ret = {
			id: id,
			type: type,
			tabs: tabs,
			tab: $window.tab || "info",
			months: {},
			month: {},
			search: {},
			photosInAlbum: {},
			showDetails: {},
			displaySet: {
				page: {
					block: [
						"about", "awards", "bio", "company_overview",
						"description", "impressum", "mission",
						"personal_info", "products", "personal_interests",
						"public_transit", "plot_outline", "produced_by",
						"starring", "culinary_team", "record_label", "general_info"
					],
					inline: [
						"id", "attire", "network", "affiliation", "phone",
						"birthday", "single_line_address", "display_subtext", "checkins",
						"hometown", "directed_by", "genre", "screenplay_by", "studio",
						"global_brand_root_id", "price_range", "band_members", "asset_score"
					],
					list: [
						"emails"
					],
					complicated: [
						"parking",
						"hours", "payment_options", "restaurant_services", "restaurant_specialties"
					],
					complicatedList: [
						"category_list"
					],
					unknown: [
						"app_id","artists_we_like","attire",
						"band_interests","booking_agent","built","business",
						"contact_address","country_page_likes",
						"culinary_team","current_location",
						"features","food_styles",
						"general_manager",
						"has_added_app","hometown","influences",
						"mpg","name_with_location_descriptor","offer_eligible",
						"parent_page","pharma_safety_info",
						"press_contact","schedule","season",
						"store_location_descriptor","store_number","studio",
						"voip_info","written_by","asset_score","checkins","members"
					]
				}
			}
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
			"page",
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
