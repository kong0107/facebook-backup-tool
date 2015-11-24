/**
 * Change elements of the input object to the id of the elements' themselves.
 *
 * For example, if the input object is {id:"s", a:1, b:{id:"t", c:2, d:{id:"u", e:3}}, f:{g:4, h:5}},
 * then the output is {id: "s", a: 1, b: "t", f: {g: 4, h: 5}}.
 * Note that the elements without `id` field would not change.
 */
function obj2id(obj, notBase) {
	if(notBase && typeof obj == 'object' && obj !== null && obj.id) return obj.id;
	if(typeof obj == 'object')
		for(var i in obj) obj[i] = obj2id(obj[i], true);
	return obj;
}
