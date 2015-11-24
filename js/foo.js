for(var i = 0; i < 10; ++i)
	window['f' + i] = function(){
		var c = i;
		return function() {
			console.log('#' + c);
			if(arguments.length) console.log(arguments);
			return;
		};
	}();
