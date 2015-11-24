function obj2table(obj, depth) {
	var createNode = function(tag, text) {
		var ele = document.createElement(tag);
		ele.appendChild(document.createTextNode(text));
		return ele;
	}
	var createTable = function(fieldName) {
		var table = document.createElement('TABLE');
		var thead = document.createElement('THEAD');
		var tr = document.createElement('TR');
		if(typeof fieldName == 'string')
			tr.appendChild(createNode('TH', fieldName));
		tr.appendChild(createNode('TH', 'type'));
		tr.appendChild(createNode('TH', 'value'));
		thead.appendChild(tr);
		table.appendChild(thead);
		table.appendChild(document.createElement('TBODY'));
		table.border = 1;
		return table;
	};
	var createRow = function(field, value) {
		var tr = document.createElement('TR');
		tr.appendChild(createNode('TH', field));
		tr.appendChild(createNode('TD', getType(value)));
		var td = document.createElement('TD');
		td.appendChild(obj2table(value, depth + 1));
		tr.appendChild(td);
		return tr;
	};
	var getType = function(value) {
		if(value === null) return 'null';
		if(typeof value == 'object' && value.hasOwnProperty('length')) return 'enum';
		return typeof value;
	};

	var type = getType(obj);

	if(typeof depth != 'number' || !depth) {
		var table = createTable();
		if(typeof depth == 'string')
			table.insertBefore(createNode('CAPTION', depth), table.tHead);
		var tr = document.createElement('TR');
		tr.appendChild(createNode('TD', type));
		var td = document.createElement('TD');
		td.appendChild(obj2table(obj, 1));
		tr.appendChild(td);
		table.tBodies[0].appendChild(tr);
		return table;
	}

	if(type == 'undefined') return createNode('CODE', type);
	if(obj === null) return createNode('CODE', 'null');
	if(typeof obj != 'object') return createNode('PRE', obj);
	if(type == 'enum') {
		if(!obj.length) return createNode('CODE', 'empty');
		var table = createTable('index');
		for(var i = 0; i < obj.length; ++i)
			table.tBodies[0].appendChild(createRow(i, obj[i]));
		return table;
	}
	
	if(!Object.getOwnPropertyNames(obj).length)
		return createNode('CODE', 'empty');
	var table = createTable('field');
	for(var i in obj)
		table.tBodies[0].appendChild(createRow(i, obj[i]));
	return table;
}
