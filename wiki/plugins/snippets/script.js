/**
 * Javascript for DokuWiki Plugin snippets
 * @author Michael Klier <chi@chimeric.de>
 */

snippets = {
    keepopen: false,

    // Attach all events to elements
    attach: function(obj) {
        if(!obj) return;
        if(!opener) return;

        // add keepopen checkbox
        var opts = $('plugin_snippets__opts');
        if(opts) {
            var kobox  = document.createElement('input');
            kobox.type = 'checkbox';
            kobox.id   = 'snippets__keepopen';
            if(DokuCookie.getValue('snippets_keepopen')){
                kobox.checked  = true;
                kobox.defaultChecked = true; //IE wants this
                media.keepopen = true;
            }
            addEvent(kobox, 'click', function(event){
                snippets.togglekeepopen(this); });

            var kolbl       = document.createElement('label');
            kolbl.htmlFor   = 'snippets__keepopen';
            kolbl.innerHTML = LANG['keepopen'];

            var kobr = document.createElement('br');

            opts.appendChild(kobox);
            opts.appendChild(kolbl);
            opts.appendChild(kobr);
        }

        // attach events
        links = getElementsByClass('wikilink1', obj, 'a');
        if(links) {
            for(var i = 0; i < links.length; i ++) {
                link = links[i];
                page = link.title;
                div  = link.parentNode;

                span = document.createElement('span');
                span.innerHTML = link.innerHTML;
                div.removeChild(link);

                preview = document.createElement('a');
                preview.className = 'plugin_snippets_preview';
                preview.title = LANG['plugins']['snippets']['preview'];
                preview.href = page;
                addEvent(preview, 'click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    snippets.preview(this.href); 
                    return false; });
                div.appendChild(preview);

                insert = document.createElement('a');
                insert.className = 'plugin_snippets_insert';
                insert.title = LANG['plugins']['snippets']['insert'];
                insert.href = page;
                addEvent(insert, 'click', function(event) { 
                    event.preventDefault();
                    event.stopPropagation();
                    snippets.insert(this.href); 
                    return false; });
                div.appendChild(insert);
                div.appendChild(span);
            }
        }

        // strip out links to non-existing pages
        links = getElementsByClass('wikilink2', obj, 'a');
        if(links) {
            for(var i = 0; i < links.length; i ++) {
                link = links[i];
                span = document.createElement('span');
                span.innerHTML = link.innerHTML;
                div = link.parentNode;
                div.removeChild(link);
                div.appendChild(span);
            }
        }

        // add toggle to sub lists
        lists = obj.getElementsByTagName('ul');
        if(lists) {
            for(var i = 1; i < lists.length; i++) {
            list = lists[i];
                list.style.display = 'none';
                div = list.previousSibling;
                if(div.nodeType != 1) {
                    // IE7 and FF treat whitespace different
                    div = div.previousSibling;
                }
                div.className = 'li closed';
                addEvent(div, 'click', function(event) { snippets.toggle(this); });
            }
        }
    },
    
    // toggle open/close state in template list
    toggle: function(obj) {
        if(!obj) return;
        list = obj.nextSibling;
        if(list.nodeType != 1) {
            list = list.nextSibling;
        }
        if(list.style.display == 'none') {
            list.style.display = 'block';
            obj.className = 'li open';
        } else {
            list.style.display = 'none';
            obj.className = 'li closed';
        }
        return false;
    },

    /**
     * Toggles the keep open state
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    togglekeepopen: function(cb){
        if(cb.checked){
            DokuCookie.setValue('snippets_keepopen',1);
            snippets.keepopen = true;
        }else{
            DokuCookie.setValue('snippets_keepopen','');
            snippets.keepopen = false;
        }
    },

    // perform AJAX preview
    preview: function(page) {
        preview = $('plugin_snippets__preview');
        if(!preview) return;

        preview.innerHTML = '<img src="'+DOKU_BASE+'/lib/images/throbber.gif" />';

        var ajax = new sack(DOKU_BASE+'lib/exe/ajax.php');
        ajax.AjaxFailedAlert = '';
        ajax.encodeURIString = false;
        
        ajax.setVar('call', 'snippet_preview');
        ajax.setVar('id', page);

        ajax.onCompletion = function(){
            var data = this.response;
            if(data === '') return;
            preview.innerHTML = data;
        };
    
        ajax.runAJAX();
        return false;
    },

    // perform AJAX insert
    insert: function(page) {
        if(!opener) return;

        var ajax = new sack(DOKU_BASE+'lib/exe/ajax.php');
        ajax.AjaxFailedAlert = '';
        ajax.encodeURIString = false;
        
        ajax.setVar('call', 'snippet_insert');
        ajax.setVar('id', page);

        ajax.onCompletion = function(){
            var data = this.response;
            opener.insertAtCarret('wiki__text', data, '');
            if(!snippets.keepopen) { 
                window.close();
            }
            opener.focus();
        };
    
        ajax.runAJAX();
        return false;
    }
};

addInitEvent(function(){
    var idx = $('plugin_snippets__idx');
    if(!idx) return;
    snippets.attach(idx);
});

// vim:ts=4:sw=4:et:enc=utf-8:
