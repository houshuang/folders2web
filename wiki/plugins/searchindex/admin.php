<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_searchindex extends DokuWiki_Admin_Plugin {
    var $cmd;

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 40;
    }

    /**
     * handle user request
     */
    function handle() {
    }

    /**
     * output appropriate html
     */
    function html() {
        echo $this->plugin_locale_xhtml('intro');


        echo '<div id="plugin__searchindex">';
        echo $this->getLang('nojs');
        echo '</div>';

    }


}
//Setup VIM: ex: et ts=4 enc=utf-8 :
