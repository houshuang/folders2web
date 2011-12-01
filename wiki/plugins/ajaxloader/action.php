<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Adrian Lang <dokuwiki@cosmocode.de>
 */
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_ajaxloader extends DokuWiki_Action_Plugin {
    function register(&$controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this,
                                   'ajax');
    }

    function ajax(&$event, $param) {
        $call = $event->data;
        include 'common.php';
    }
}
