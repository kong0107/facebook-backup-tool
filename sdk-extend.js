/**
 * @author	kong0107
 */

if(!window.fbAsyncInit)
	throw new Error('This extension for Facebook SDK should be loaded after `window.fbAsyncInit` is defined.');

window.fbAsyncInit = function(){
	var orig = window.fbAsyncInit;
	return function() {

		/**
		 * Throw an error if FB.api gets an error response.
		 *
		 * Since FB.api is overloading, so we have to detect which argument
		 * is the callback function.
		 *
		 * @see https://developers.facebook.com/docs/javascript/reference/FB.api/
		 */
		FB.apiwt = function() {
			for(var i = 1; (i < arguments.length) && (typeof arguments[i] != 'function'); ++i);
			if(i == arguments.length) throw new Error('no callback function');
			var func = arguments[i];
			arguments[i] = function() {
				var orig = func;
				return function(response) {
					if(response.error) throw new Error(response.error.message);
					orig.apply(this, arguments);
				};
			}();
			FB.api.apply(this, arguments);
		};

		/**
		 * Run different function depending on login status.
		 *
		 * @param con	called and having the authResponse object as the
		 *				first argument if the user is logged in and has
		 *				authenticated
		 * @see	https://developers.facebook.com/docs/reference/javascript/FB.getLoginStatus
		 */
		FB.ifLoggedIn = function(con, na, uk) {
			if(typeof na != 'function') na = function(){};
			if(typeof uk != 'function') uk = na;
			FB.getLoginStatus(function(r){
				if(r.status == 'connected') con(r.authResponse);
				else if(r.status == 'not_authorized') na(r.status);
				else uk(r.status);
			});
		}

		/**
		 * Parse granted permissions asynchronously.
		 *
		 * If the user isn't logged in, an empty array is passed to
		 * the callback function.
		 */
		FB.getGrantedPermissions = function(callback) {
			FB.ifLoggedIn(function() {
				FB.apiwt('me/permissions', function(r) {
					var perms = [];
					for(var i = 0; i < r.data.length; ++i) {
						var p = r.data[i];
						if(p.status == 'granted')
							perms.push(p.permission);
					}
					callback(perms);
				});
			}, function(){
				callback([]);
			});
		};

		/**
		 * Run function depending on whether the permission is granted.
		 *
		 * If the permission `perm` is granted, execute `granted` and passed
		 * all granted permissions;
		 * otherwise, execute `declined` and passed declined permissions.
		 * Wrong permission names are treated as declined.
		 *
		 * @see https://developers.facebook.com/docs/facebook-login/permissions/v2.4#reference
		 */
		FB.ifPermitted = function(perms, granted, declined) {
			if(typeof granted != 'function') granted = function(){};
			if(typeof declined != 'function') declined = function(){};
			if(!Array.isArray(perms)) perms = perms.split(',');
			FB.getGrantedPermissions(function(gps) {
				var ds = [];
				perms.forEach(function(p) {
					p = p.trim();
					if(p && gps.indexOf(p) == -1) ds.push(p);
				});
				if(ds.length) declined(ds);
				else granted(gps);
			});
		};


		/**
		 * Request the permission(s) and run different functions.
		 *
		 * Since login status is checked in FB.getGrantedPermissions,
		 * here we don't have to check it again.
		 */
		FB.requestPermission = function(perms, allGranted, notAll) {
			FB.login(function(){
				FB.ifPermitted(perms, allGranted, notAll);
			}, {scope: perms.toString(), auth_type: 'rerequest'});
		};


		/**
		 * Keep asking for the designated permission(s) until all granted.
		 *
		 * This function may annoy the user.
		 */
		FB.requestPermissionUntilGranted = function(perms, callback) {
			var recur = function() {
				FB.requestPermission(perms, callback, recur);
			};
			recur();
		};

		return orig.apply(this, arguments);
	}
}()
