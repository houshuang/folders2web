/**
 * Javascript for searchindex manager plugin
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */

var plugin_searchindex = {

    // hold some values
    pages: null,
    page:  null,
    sack:  null,
    done:  1,
    count: 0,
    output: null,
    lang: null,

    /**
     * initialize everything
     */
    init: function(){
        plugin_searchindex.output = $('plugin__searchindex');
        if(!plugin_searchindex.output) return;

        plugin_searchindex.sack = new sack(DOKU_BASE + 'lib/plugins/searchindex/ajax.php');
        plugin_searchindex.sack.AjaxFailedAlert = '';
        plugin_searchindex.sack.encodeURIString = false;
        plugin_searchindex.lang = LANG.plugins.searchindex;

        // init interface
        plugin_searchindex.status('<button id="plugin__searchindex_btn" class="button">'+plugin_searchindex.lang.rebuild+'</button>');
        addEvent($('plugin__searchindex_btn'),'click',plugin_searchindex.go);
    },

    /**
     * Gives textual feedback
     */
    status: function(text){
        plugin_searchindex.output.innerHTML = text;
    },

    /**
     * Callback.
     * Executed when the index was cleared.
     * Starts the indexing
     */
    cb_clear: function(){
        var ok = this.response;
        if(ok == 1){
            // start indexing
            window.setTimeout(plugin_searchindex.index,1000);
        }else{
            plugin_searchindex.status(ok);
            // retry
            window.setTimeout(plugin_searchindex.clear,5000);
        }
    },

    /**
     * Callback.
     * Executed when the list of pages came back.
     * Starts the index clearing
     */
    cb_pages: function(){
        var data = this.response;
        plugin_searchindex.pages = data.split("\n");
        plugin_searchindex.count = plugin_searchindex.pages.length;
        plugin_searchindex.status(plugin_searchindex.lang.pages.replace(/%d/,plugin_searchindex.pages.length));

        // move the first page from the queue
        plugin_searchindex.page = plugin_searchindex.pages.shift();

        // start index cleaning
        window.setTimeout(plugin_searchindex.clear,1000);
    },

    /**
     * Callback.
     * Returned after indexing one page
     * Calls the next index run.
     */
    cb_index: function(){
        var ok = this.response;
        var wait = 500;
        if(ok == 1){
            // next page from queue
            plugin_searchindex.page = plugin_searchindex.pages.shift();
            plugin_searchindex.done++;
        }else{
            // something went wrong, show message
            plugin_searchindex.status(ok);
            wait = 5000;
        }
        // next index run
        window.setTimeout(plugin_searchindex.index,500);
    },

    /**
     * Starts the indexing of a page.
     */
    index: function(){
        if(plugin_searchindex.page){
            plugin_searchindex.status(plugin_searchindex.lang.indexing+' <b>'+plugin_searchindex.page+'</b> ('+plugin_searchindex.done+'/'+plugin_searchindex.count+')');
            plugin_searchindex.sack.onCompletion = plugin_searchindex.cb_index;
            plugin_searchindex.sack.URLString = '';
            plugin_searchindex.sack.runAJAX('call=indexpage&page='+encodeURI(plugin_searchindex.page));
        }else{
            // we're done
            plugin_searchindex.throbber_off();
            plugin_searchindex.status(plugin_searchindex.lang.done);
        }
    },

    /**
     * Cleans the index
     */
    clear: function(){
        plugin_searchindex.status(plugin_searchindex.lang.clearing);
        plugin_searchindex.sack.onCompletion = plugin_searchindex.cb_clear;
        plugin_searchindex.sack.URLString = '';
        plugin_searchindex.sack.runAJAX('call=clearindex');
    },

    /**
     * Starts the whole index rebuild process
     */
    go: function(){
        plugin_searchindex.throbber_on();
        plugin_searchindex.status(plugin_searchindex.lang.finding);
        plugin_searchindex.sack.onCompletion = plugin_searchindex.cb_pages;
        plugin_searchindex.sack.URLString = '';
        plugin_searchindex.sack.runAJAX('call=pagelist');
    },

    /**
     * add a throbber image
     */
    throbber_on: function(){
        plugin_searchindex.output.style['background-image'] = "url('"+DOKU_BASE+'lib/images/throbber.gif'+"')";
        plugin_searchindex.output.style['background-repeat'] = 'no-repeat';
    },

    /**
     * Stop the throbber
     */
    throbber_off: function(){
        plugin_searchindex.output.style['background-image'] = 'none';
    }
};

addInitEvent(function(){
    plugin_searchindex.init();
});


//Setup VIM: ex: et ts=4 enc=utf-8 :
