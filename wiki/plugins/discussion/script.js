/**
 * Javascript functionality for the discussion plugin
 */

/**
 * Check if a field is blank
 */
function isBlank(s){
  if ((s === null) || (s.length === 0)){
    return true;
  }

  for (var i = 0; i < s.length; i++){
    var c = s.charAt(i);
	  if ((c != ' ') && (c != '\n') && (c != '\t')){
	    return false;
    }
  }
  return true;
}

/**
 * Validate an input field
 */
function validate(form){
    if(!form) return;

    if (isBlank(form.name.value)){
        form.name.focus();
        form.name.style.backgroundColor = '#fcc';
        return false;
    } else {
        form.name.style.backgroundColor = '#fff';
    }
    if (isBlank(form.mail.value) || form.mail.value.indexOf("@") == -1){
        form.mail.focus();
        form.mail.style.backgroundColor = '#fcc';
        return false;
    } else {
        form.mail.style.backgroundColor = '#fff';
    }
    if (isBlank(form.text.value)){
        form.text.focus();
        form.text.style.borderColor = '#fcc';
        return false;
    }
}

/**
 * AJAX preview
 *
 * @author Michael Klier <chi@chimeric.de>
 */
function discussion_ajax_preview() {
    if(!document.getElementById) return;

    var textarea = $('discussion__comment_text');
    var comment = textarea.value;
    if(!comment) return;

    var preview = $('discussion__comment_preview');
    preview.innerHTML = '<img src="'+DOKU_BASE+'/lib/images/throbber.gif" />';

    // We use SACK to do the AJAX requests
    var ajax = new sack(DOKU_BASE+'lib/exe/ajax.php');
    ajax.AjaxFailedAlert = '';
    ajax.encodeURIString = false;
    ajax.setVar('call', 'discussion_preview');
    ajax.setVar('comment', comment);

    // define callback
    ajax.onCompletion = function(){
        var data = this.response;
        if(data === ''){ return; }
        preview.style.visibility = 'hidden';
        preview.innerHTML = data;
        preview.style.visibility = 'visible';
    };

    ajax.runAJAX();
}

// init toolbar
addInitEvent(function() {
    if(typeof window.initToolbar == 'function') {
        initToolbar("discussion__comment_toolbar", "discussion__comment_text", toolbar);
    }
});

// init preview button
addInitEvent(function() {
    var btn = $('discussion__btn_preview');
    if(!btn) return;
    addEvent(btn, 'click', discussion_ajax_preview);
});

// init field check
addInitEvent(function() {
    var form = $('discussion__comment_form');
    if(!form) return;
    addEvent(form, 'submit', function() { return validate(form); });
});
