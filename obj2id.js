function obj2id(obj, notBase) {
	if(notBase && typeof obj == 'object' && obj !== null && obj.id) return obj.id;
	if(typeof obj == 'object')
		for(var i in obj) obj[i] = obj2id(obj[i], true);
	return obj;
}