/**
 * Create a function which runs the input funcions sequentially.
 * Usage: var func = Function.concatenate(func1, func2, ...);
 * Usage: var func = Function.concatenate([func1, func2, ...]);
 *
 * Note that "this function" does NOT run the "input functions". It's the
 * "returned function" that runs the "input functions" of "this function".
 * Arguments for "input functions" are needed only when "returned function"
 * is executed.
 * Input functions can be assigned by an argument list, or you can put all
 * input functions in an array and treat the array as the only argument of 
 * this function.
 * When calling the returned function, the first argument would be parsed
 * to the first input function, the second argument to the second input
 * function, and so on.
 *
 * This function shall not be named as `concat`; 
 * otherwise String.prototype.concat would be overrided.
 *
 */

if(typeof Function.prototype.concatenate == 'undefined')
	Function.prototype.concatenate = function() {
		var funclist = Array.isArray(arguments[0]) ? arguments[0] : arguments;
		return function() {
			var result = [];
			for(var i  = 0; i < funclist.length; ++i)
				result[i] = funclist[i].apply(this, arguments[i]);
			return result;
		}
	};
