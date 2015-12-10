angular.module("myApp", [])
.config(function($locationProvider) {
	$locationProvider.html5Mode({
		enabled: true,
		requireBase: false
	});
})
.controller("main", function($scope, $http, $location, $window, $filter) {
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
		 *
		 * Use `setTimeout` in a self-calling callback function
		 * to prevent stack overflow problem.
		 * @see https://blog.jcoglan.com/2010/08/30/the-potentially-asynchronous-loop/
		 */
		for(var i = 0; i < tabs.length; ++i) ret[tabs[i]] = [];
		$http.get("data/json/" + type + "_" + id +"_info.json")
		.then(function(r) {
			ret.info = r.data;
			var iterator = 0;
			var loadEdge = function() {
				var tab = tabs[iterator];
				$http.get("data/json/"
					+ type + "_" + id + "_" + tab
					+ ".json"
				).then(function(r) {
					ret[tab] = r.data;
					if(tab == "posts" || tab == "feed" || tab == "comment")
						groupByMonth(ret[tab], tab);
					console.log("Edge " + tab + " is loaded.");
					if(iterator < tabs.length - 1) {
						++iterator;
						setTimeout(loadEdge, 1);
					}
					else {
						console.log("All edges are loaded.");
						arrangeComments();
						arrangePhotos();
					}
				}, notFound);
			};
			loadEdge();
		}, notFound);

		/**
		 * Put comments to where each should be.
		 *
		 * This should be called before `arrangePhotos`.
		 * Maintain only those without `fbbk_parent` for event node.
		 */
		var arrangeComments = function() {
			if(!ret.comments || !ret.comments.length) return;
			for(var i = 0; i < tabs.length; ++i) {
				for(var j = 0; j < ret[tabs[i]].length; ++j) {
					var node = ret[tabs[i]][j];
					node.comments = $filter('filter')(
						ret.comments,
						{fbbk_parent: {id: node._id}},
						function(a, b) {return a==b;}
					);
				}
			}
			if(type == "event") {
				var all = ret.comments;
				ret.comments = [];
				for(var i = 0; i < all.length; ++i) {
					if(!all[i].fbbk_parent)
						ret.comments.push(all[i]);
				}
			}
			else delete ret.comments;
			console.log("Comments arranged.");
		}
		
		/**
		 * Put photos to the albums they should be in.
		 *
		 * This should be called after `arrangeComments`.
		 */
		var arrangePhotos = function() {
			if(!ret.photos || !ret.photos.length) return;
			for(var j = 0; j < ret.albums.length; ++j)
				ret.albums[j].photos = [];
			var tagged = [];
			for(var i = 0; i < ret.photos.length; ++i) {
				var photo = ret.photos[i];
				for(var j = 0; j < ret.albums.length; ++j) {
					var album = ret.albums[j];
					if(photo.album && photo.album.id == album._id) {
						album.photos.push(photo);
						break;
					}
				}
				if(j == ret.albums.length) tagged.push(photo);
			}
			ret.photos = tagged;
			console.log("Photos arranged.");
		}

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
