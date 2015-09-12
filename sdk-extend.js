if(!window.fbAsyncInit) throw new Error('This extension for Facebook SDK should be loaded after `window.fbAsyncInit` is defined.');
window.fbAsyncInit = function(){
	var orig = window.fbAsyncInit;
	return function() {
		
		/**
		 * FB.api with throw
		 *
		 * Same as FB.api, except that an error would be thrown automatically
		 * if there's error response.
		 * Since FB.api is overloading, so we have to detect which argument
		 * is the callback function.
		 * 
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
		 * Run function depending on whether the permission is granted
		 * 
		 * If the permission `perm` is granted, execute `granted`;
		 * otherwise, execute `declined`.
		 */
		FB.ifPermitted = function(perm, granted, declined) {
			if(typeof granted != 'function') granted = function(){};
			if(typeof declined != 'function') declined = function(){};
			FB.apiwt('me/permissions/' + perm, function(r) {
				if(r.data.length && r.data[0].status == 'granted') granted();
				else declined();
			});
		};
		return orig.apply(this, arguments);
	}
}()