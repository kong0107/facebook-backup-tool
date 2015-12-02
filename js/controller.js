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
			unknownFields: {
				"page": [
					"affiliation","app_id","artists_we_like","attire","awards",
					"band_interests","band_members","best_page","bio","birthday","booking_agent","built","business",
					"category_list","company_overview","contact_address","country_page_likes",
					//"cover",
					"culinary_team","current_location",
					"directed_by","display_subtext","emails",
					"features","food_styles","founded",
					"general_info","general_manager","genre",
					"global_brand_root_id","has_added_app",
					"hometown","hours","impressum","influences",
					"location","mission","mpg","name_with_location_descriptor","network",
					"offer_eligible","parent_page","parking","payment_options","personal_info","personal_interests","pharma_safety_info","phone","plot_outline","press_contact","price_range","produced_by","products","promotion_eligible","promotion_ineligible_reason","public_transit","record_label","release_date","restaurant_services","restaurant_specialties","schedule","screenplay_by","season","starring","start_info","store_location_descriptor","store_number","studio","talking_about_count","engagement","single_line_address","place_type","unread_message_count","unread_notif_count","unseen_message_count","username","voip_info","website","were_here_count","written_by","featured_video","owner_business","last_used_time","asset_score","checkins","likes","members"
					
		/*"ad_campaign",
		"promotion_eligible",
		"owner_business",
		
		"access_token",
		"business",
		
		"context",
		"can_checkin",
		"can_post",
		"leadgen_tos_accepted",
		"last_used_time",
		"new_like_count",
		"is_published",
		"voip_info"*/
				]
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
