<?php /**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Klier <chi@chimeric.de>
 */
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class action_plugin_snippets extends DokuWiki_Action_Plugin {

    /**
     * Register callbacks
     */
    function register(&$controller) {
        $controller->register_hook('TOOLBAR_DEFINE','AFTER', $this, 'handle_toolbar_define');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call');
    }

    /**
     * Adds the new toolbar item
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function handle_toolbar_define(&$event, $param) {
        if(!page_exists($this->getConf('snippets_page'))) return;

        $item = array(
                'type' => 'mediapopup',
                'title' => $this->getLang('gb_snippets'),
                'icon' => '../../plugins/snippets/images/icon.png',
                'url' => 'lib/plugins/snippets/exe/snippets.php?ns=',
                'name' => 'snippets',
                'options' => 'width=800,height=500,left=20,top=20,scrollbars=no,resizable=yes'
                );
        $event->data[] = $item;
    }

    /**
     * Handles the AJAX calls
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function handle_ajax_call(&$event, $param) {
        global $lang;
        if($event->data == 'snippet_preview' or $event->data == 'snippet_insert') {
            $event->preventDefault();  
            $event->stopPropagation();
            $id = cleanID($_REQUEST['id']);
            if(page_exists($id)) {
                if($event->data == 'snippet_preview') {
                    if(auth_quickaclcheck($id) >= AUTH_READ) {
                        print p_wiki_xhtml($id);
                    } else {
                        print p_locale_xhtml('denied');
                    }
                } elseif($event->data == 'snippet_insert') {
                    if(auth_quickaclcheck($id) >= AUTH_READ) {
                        print "\n\n"; // always start on a new line (just to be safe)
                        print trim(preg_replace('/<snippet>.*?<\/snippet>/s', '', io_readFile(wikiFN($id))));
                    }
                }
            }
        }
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
