/**
 * script.js for Plugin hidden
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Guillaume Turri <guillaume.turri@gmail.com>
 *
 */

function plugin_hidden_hideThis(){
	plugin_hidden_flip(this);
}

function plugin_hidden_flip(elem){
  var parent = elem.parentNode;
  var onHiddenText = parent.childNodes[0].innerHTML;
  var onVisibleText = parent.childNodes[1].innerHTML;
  var head = parent.childNodes[3];
  var body = parent.childNodes[4];

  
  if (body.style.display === ""){
    body.style.display = "none";
    head.innerHTML = onHiddenText;
  }
  else{
    body.style.display = "";
    head.innerHTML = onVisibleText;
  }
}

function plugin_hidden_hide(elem){
	var parent = elem.parentNode;
	var onHiddenText = parent.childNodes[0].innerHTML;
	var onVisibleText = parent.childNodes[1].innerHTML;
	var head = parent.childNodes[3];
	var body = parent.childNodes[4];

	body.style.display = "none";
	head.innerHTML = onHiddenText;
}

/**
 * The plugin will hide text only if javascript is enabled (else, it wouldn't be possible to display it)
 */
function installPluginHiddenJS(){
	var noeuds = document.getElementsByTagName("div");
	var taille = noeuds.length;
	var i;
	var regActif = new RegExp("hiddenActive","");
	var regInitial = new RegExp("hiddenSinceBeginning", "");	

	for( i=0 ; i < taille ; i++){
		//declare the onclick comportment
		if ( regActif.test(noeuds[i].className) ){
			addEvent(noeuds[i], 'click', plugin_hidden_hideThis);
		}
	}
	
	for ( i=0 ; i < taille ; i++ ){
		//Hide the nodes which should initially be hidden
		if ( regInitial.test(noeuds[i].className) ){
			plugin_hidden_hide(noeuds[i]);
		}
	}
}

addInitEvent(function(){installPluginHiddenJS();});
