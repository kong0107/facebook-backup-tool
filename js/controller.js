angular.module("myApp", [])
.config(function($locationProvider) {
	$locationProvider.html5Mode({
		enabled: true,
		requireBase: false
	});
})
.controller("main", function($scope, $window, $filter) {
	if($window.debug_startTime)
		console.log("Controller start after " + (new Date - $window.debug_startTime) + " milliseconds.");
	$scope.ret = function() {
		/// Initialization
		var node = $window.node;
		if(!node) {
			throw new Error('`window.node` must be defined.');
			return;
		}
		var id = node.info._id;
		var type = $window.type;

		var ret = {
			id: id,
			type: type,
			itemsPerPage: 10, ///< used for pagination
			tab: $window.tab || "info",
			months: {},
			month: {},
			search: {},
			showDetails: {},
			displaySet: {
				user: {
					block: [
						"bio","quotes","website"
					],
					inline: [
						/*"first_name","middle_name","last_name",*/"name_format",
						"birthday","gender","email",
						"political","religion","relationship_status",
						"updated_time","link","locale","install_type","verified","is_verified","test_group","third_party_id","timezone","shared_login_upgrade_required_by","public_key"
					],
					list: [
						"interested_in","meeting_for"
					],
					pairs: [
						"significant_other","hometown","location","cover"
					],
					table: [
						"education","languages","work","inspirational_people","sports"
					],
					unknown: [
						"about"
					]
				},
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
					pairs: [
						"parking",
						"hours", "payment_options", "restaurant_services", "restaurant_specialties"
					],
					table: [
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
				},
				event: {
					inline: [
						"category","type","start_time","end_time",
						"updated_time","attending_count","declined_count","maybe_count","noreply_count","interested_count",
						"ticket_uri"
					],
					pairs: [
						"owner","parent_group"
					],
					unknown: [
						"id","place"
					]
				},
				group: {
					unknown: [
						"id","cover","description","email","icon","link","name","member_request_count","owner","parent","privacy","updated_time","venue"
					]
				},
				doc: {
					unknown: [
						"id","can_delete","can_edit","created_time","from","icon","link","message","revision","subject","updated_time"
					]
				}
			}
		};

		/**
		 * Load info and edges.
		 */
		ret.tabs = []
		for(var tab in node) {
			ret.tabs.push(tab);
			ret[tab] = node[tab];

			/**
			 * Get distinct months of `created_time`.
			 */
			if(tab == "posts" || tab == "feed" || tab == "comments") {
				ret[tab].sort(function(a, b) {
					return new Date(b.created_time) - new Date(a.created_time);
				});
				var ms = [];
				for(var i = 0; i < ret[tab].length; ++i)
					ms.push(ret[tab][i].created_time.substr(0, 7));
				ret.months[tab] = $.unique(ms);
				ret.month[tab] = ret.months[tab][0];
				ret.search[tab] = "";
			}
		}

		/**
		 * Put comments to where each should be.
		 *
		 * This should be done before `arrangePhotos`.
		 * Don't delete those without `fbbk_parent` for event node.
		 */
		if(ret.comments && ret.comments.length) {
			for(var i = 0; i < ret.tabs.length; ++i) {
				for(var j = 0; j < ret[ret.tabs[i]].length; ++j) {
					var node = ret[ret.tabs[i]][j];
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
		 * Should be executed after comments are arranged.
		 */
		if(ret.albums && ret.albums.length 
			&& ret.photos && ret.photos.length
		) {
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
		 * Render the path to the downloaded photo.
		 */
		ret.getSourcePhoto = function(photoNode, dir) {
			if(!dir) dir = "album_" + photoNode.album.id;
			var p = photoNode.picture;
			var suffix = p.substr(p.indexOf('?') - 4, 4);
			return "data/photos/" + dir + "/" + photoNode._id + suffix;
		};

		if($window.debug_startTime)
			console.log("Model ready after " + (new Date - $window.debug_startTime) + " milliseconds.");

		return ret;
	}();
})
.filter("capitalize", function() {
	return function(input) {
		return (!!input) ? input.charAt(0).toUpperCase() + input.substr(1) : '';
	};
})
.filter("unique", function() {
	return function(input, field) {
		if(!Array.isArray(input) || !input.length) return [];
		var strings = [], ret = [];
		for(var i in input) {
			if(!input[i]) continue;
			var json = JSON.stringify(
				(typeof field == "undefined" || !input[i][field]) ? input[i] : input[i][field]
			);
			if(strings.indexOf(json) != -1) continue;
			strings.push(json);
			ret.push(input[i]);
		}
		return ret;
	};
})
.filter("dateFromArray", function(dateFilter) {
	return function(arr, format, timezone) {
		var date = new Date;
		date.setFullYear(arr.year || arr.Year || 1970);
		date.setMonth((arr.month || arr.Month || 1) - 1);
		date.setDate(arr.date || arr.Date || arr.day || arr.Day || 1);
		date.setHours(arr.hour || arr.hours || arr.Hour || 0);
		date.setMinutes(arr.minute || arr.minutes || arr.Minute || 0);
		date.setSeconds(arr.second || arr.seconds || arr.Second  || 0);
		date.setMilliseconds(arr.millisecond || arr.milliseconds || 0);
		return dateFilter(date, format, timezone);
	};
})
.filter("truncate", function() {
	/**
	 * Truncate a long string.
	 *
	 * Cut `str` if it's longer than `limit` and append `ellipsis`.
	 */
	return function(str, limit, ellipsis) {
		if(!str) return "";
		str += ""; limit *= 1;
		if(isNaN(limit)) return str;
		if(typeof ellipsis == "undefined") ellipsis = " ...";
		if(str.length < limit) return str;
		return str.substr(0, limit) + ellipsis;
	};
});
