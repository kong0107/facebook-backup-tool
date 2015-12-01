/**
 * Define some debug function to use as callback
 *
 * For example: `FB.api("/me", f0);`
 * The code above would print out the argument list
 * of the callback function in console.
 */
for(var i = 0; i < 10; ++i)
	window['f' + i] = function(){
		var c = i;
		return function() {
			console.log('#' + c);
			if(arguments.length) console.log(arguments);
			return;
		};
	}();
delete i;

/**
 * Create a DOM containing text with links to the tagged targets.
 *
 * You can combine `story` and `story_tags`, or `message` and
 * `message_tags` with this function.
 */
function combine_tags(text, tags) {
	var c = document.createElement('SPAN');
	for(var i = 0; i < tags.length; ++i) {
		var tag = tags[i];
		var a = document.createElement('A');
		a.href = 'https://www.facebook.com/' + tag.id;
		if(tag.name) a.title = tag.name;
		a.appendChild.textContent = text.substr(tag.offset, tag.length);
		c.appendChild(a);
		var next = (i == tags.length - 1)
			? text.length
			: tags[i+1].offset
		;
		c.appendChild(document.createTextNode(
			text.substring(tag.offset+tag.length, next)
		));
	}
	return c;
}
