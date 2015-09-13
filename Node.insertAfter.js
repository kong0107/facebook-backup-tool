if(typeof Node.prototype.insertAfter == 'undefined')
	Node.prototype.insertAfter = function(newnode, existingnode) {
		if(existingnode == this.lastChild) this.appendChild(newnode);
		this.insertBefore(newnode, existingnode.nextSibling);
	};
