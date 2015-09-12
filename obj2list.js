function obj2list(obj) {
	if(typeof obj == 'undefined') return document.createTextNode('undefined');
	if(typeof obj === null) return document.createTextNode('null');
	if(typeof obj != 'object') return document.createTextNode(obj.toString());
	if(Array.isArray(obj)) {
		if(obj.length == 0) return document.createTextNode('empty array');
		var ol = document.createElement('OL');
		for(var i = 0; i < obj.length; ++i)
			ol.appendChild(cewc('LI', obj2list(obj[i])));
		return ol;
	}
	var dl = document.createElement('DL');
	for(var i in obj) {
		dl.appendChild(cewc('DT', i));
		dl.appendChild(cewc('DD', obj2list(obj[i])));
	}
	return dl;
}