var svgeditor_path = 'http://svg-edit.googlecode.com/svn/tags/stable/editor/';	//online stable
//var svgeditor_path = 'http://svg-edit.googlecode.com/svn/trunk/editor/';	//online latest (unstable)
//var svgeditor_path = DOKU_BASE+'lib/plugins/svgedit/svg-edit/';		//offline

//load embedapi.js
var head = document.getElementsByTagName("head")[0];
script = document.createElement('script');
script.type = 'text/javascript';
script.src = svgeditor_path + 'embedapi.js';
head.appendChild(script);

function svgedit_load() {
	var field = $('wiki__text');
	if (!field)
		return;
	var timeout = setTimeout('svgedit_load();', 500);	//load ASAP
	window.svgedit.setSvgString(field.value) (function(a) {
						  clearTimeout(timeout);
						  }
	);
}
function svgedit_save(page) {
	window.svgedit.getSvgString()(function(data) {
				      var field = $('wiki__text');
				      if (!field) return;
				      field.value = data; if (page) {
				      field = $('edbtn__save'); field.click();}
				      }
	) ;
}

function showhide(elem) {
	elem.style.display = (elem.style.display == 'none' ? '' : 'none');
}

function insertAfter(newNode, preNode) {
	if (preNode.nextSibling)
		preNode.parentNode.insertBefore(newNode, preNode.nextSibling);
	else
		preNode.parentNode(newNode);
}

var svgedit = null;
function svgedit_init() {
	var field = $('wiki__text');
	if (!field)
		return;

	//toggle view
	showhide(field);
	showhide($('tool__bar'));
	showhide($('edbtn__save'));

	//lock
	if ($('svg__edit'))
		return;

	//create iframe

	var el = document.createElement('iframe');
	el.setAttribute("src", svgeditor_path + 'svg-editor.html');
	el.setAttribute("id", "svg__edit");
	el.setAttribute("name", "svg__edit");
	el.setAttribute("frameborder", "0");
	el.setAttribute("width", "100%");
	el.setAttribute("height", "70%");
	el.setAttribute("style", "min-height: 400px;");
	insertAfter(el, field);

	//create save button
	field = $('edbtn__save');
	if (!field)
		return;
	el = document.createElement('input');
	el.setAttribute("type", "button");
	el.setAttribute("onclick", "svgedit_save(true)");
	el.setAttribute("value", "SVG-SAVE");
	el.setAttribute("title", "Save SVG to server");
	el.setAttribute("class", "button");
	field.parentNode.insertBefore(el, field);

	el = document.createElement('input');
	el.setAttribute("type", "button");
	el.setAttribute("onclick", "svgedit_load()");
	el.setAttribute("value", "TXT->SVG");
	el.setAttribute("title", "Copy SVG from textarea to svg-editor");
	el.setAttribute("class", "button");
	field.parentNode.insertBefore(el, field);

	el = document.createElement('input');
	el.setAttribute("type", "button");
	el.setAttribute("onclick", "svgedit_save()");
	el.setAttribute("value", "SVG->TXT");
	el.setAttribute("title", "Copy SVG from svg-editor to textarea");
	el.setAttribute("class", "button");
	field.parentNode.insertBefore(el, field);

	//create embedapi
	window.svgedit = new embedded_svg_edit($('svg__edit'));

	//load image
	svgedit_load();
}

addInitEvent(function() {
	     if (!$('wiki__text') || $('wiki__text').readOnly) return;
	     var field = $('tool__bar');
	     if (!field) return;
	     field.style.float = 'left';
	     var el = document.createElement('button');
	     el.setAttribute("id", "TZT");
	     el.setAttribute("class", "toolbutton");
	     el.setAttribute("onclick", "svgedit_init();");
	     el.setAttribute("title", "Edit this page as SVG!");
	     el.setAttribute("style", "float: left;");
	     field.parentNode.insertBefore(el, field);
	     el.appendChild(document.createTextNode("SVG"));
	     var el = document.createElement('br');
	     el.setAttribute('style', "clear: left;");
	     field.appendChild(el);}) ;
