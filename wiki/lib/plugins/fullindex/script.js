/*
Based on Stuart Langridge's aqtree3clickable.js (http://www.kryogenix.org/code/browser/aqlists/)
*/


function makeTreesC() {
    // We don't actually need createElement, but we do
    // need good DOM support, so this is a good check.
    if (!document.createElement) return;
    
    uls = document.getElementsByTagName("ul");
    for (uli=0;uli<uls.length;uli++) {
        ul = uls[uli];
        if (ul.nodeName == "UL" && ul.className == "aqtree3clickable") {
            processULELC(ul);
        }
    }
}

function processULELC(ul) {
    if (!ul.childNodes || ul.childNodes.length == 0) return;
    // Iterate LIs
    for (var itemi=0;itemi<ul.childNodes.length;itemi++) {
        var item = ul.childNodes[itemi];
        if (item.nodeName == "LI") {
            // Iterate things in this LI
            var a;
            var subul;
	    	subul = "";
            for (var sitemi=0;sitemi<item.childNodes.length;sitemi++) {
                var sitem = item.childNodes[sitemi];
                switch (sitem.nodeName) {
                    case "A": a = sitem; break;
                    case "SPAN": a = sitem; break;
                    case "UL": subul = sitem; 
                               processULELC(subul);
                               break;
                }
            }
            if (subul) {
                associateELC(a,subul);
            } else {
                a.parentNode.className = "bullet";
            }
        }
    }
}

function associateELC(a,ul) {
	var el = document.createElement("a");
	var img = document.createElement("img");
	img.src="lib/plugins/fullindex/images/open.gif";
	el.appendChild(img);
	el.className = "cat";
	a.parentNode.insertBefore(el, a.parentNode.firstChild);
    if (a.parentNode.className.indexOf('open') == -1)
		a.parentNode.className = 'closed';
	el.onclick = function () {
        if (this.parentNode.className=='open') {
        	this.parentNode.className = "closed";
			this.firstChild.src="lib/plugins/fullindex/images/closed.gif";
        } else {
        	this.parentNode.className = "open";
			this.firstChild.src="lib/plugins/fullindex/images/open.gif";
        }
        return false;
    }
}

function aq_collapseAll() {
	//walk through entire tree and change "open" to "closed" class
    uls = document.getElementsByTagName("ul");
    for (uli=0;uli<uls.length;uli++) {
        ul = uls[uli];
        if (ul.nodeName == "UL" && ul.className == "aqtree3clickable") {
			if (!ul.childNodes || ul.childNodes.length == 0) return;
			for (var itemi=0;itemi<ul.childNodes.length;itemi++) {
				var item = ul.childNodes[itemi];
				item.className = 'closed';
			}
        }
    }
}

function aq_showLevel(ul, level, currLevel) {
	currLevel = currLevel + 1;
    if (!ul.childNodes || ul.childNodes.length == 0) return;
    // Iterate LIs
    for (var itemi=0;itemi<ul.childNodes.length;itemi++) {
        var item = ul.childNodes[itemi];
        if (item.nodeName == "LI") {
            // Iterate things in this LI
            var subul;
	    	subul = "";
	    	var hasChild;
	    	//if not reached level look for ul and process recursively
	    	if (currLevel < level){
				for (var sitemi=0;sitemi<item.childNodes.length;sitemi++) {
					var sitem = item.childNodes[sitemi];
					switch (sitem.nodeName) {
					  case "UL": subul = sitem; 
					    aq_showLevel(subul, level, currLevel);
					    hasChild = true;
						break;
					}
				}
				if (hasChild) {
					item.className = 'open';
					hasChild = false;
				} else {
					item.className = 'bullet';
				}
			} else {
				//check to see if there's a end node
				for (var sitemi=0;sitemi<item.childNodes.length;sitemi++) {
					var sitem = item.childNodes[sitemi];
					switch (sitem.nodeName) {
					  case "UL": subul = sitem; 
					    hasChild = true;
						break;
					}
				}
				if (hasChild) {
					item.className = 'closed';
					item.firstChild.firstChild.src="lib/plugins/fullindex/images/closed.gif";
					hasChild = false;
				} else {
					item.className = 'bullet';
				}
            }
        }
    }
}


function aq_show(level, obj) {
	//walk through entire tree and change "open" to "closed" class based on the level passed
    uls = document.getElementsByTagName("ul");
    for (uli=0;uli<uls.length;uli++) {
        ul = uls[uli];
        if (ul.nodeName == "UL" && ul.className == "aqtree3clickable") {
        	if(level > 0) {
        		aq_set(obj);
            	aq_showLevel(ul, level, 0);
            }
        }
    }
}

//find the clicked item and set classes accordingly
function aq_set(obj) {
    var ul = document.getElementById("aqNav");
    for (i=0;i<ul.childNodes.length;i++) {
    	var li = ul.childNodes[i];
    	//find clicked item
    	if(li.id == obj.parentNode.id) {
    		li.className = "on";
    	} else {
    		li.className = "";
    	}
    }
}

//initialize
addInitEvent(makeTreesC);
