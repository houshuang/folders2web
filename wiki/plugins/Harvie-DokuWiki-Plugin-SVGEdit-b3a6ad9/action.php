<?php
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
 
class action_plugin_svgedit extends DokuWiki_Action_Plugin {
 
    function getInfo(){
            return array('author' => 'Thomas Mudrunka',
                         'email'  => 'harvie--email-cz',
                         'date'   => '2010-02-21',
                         'name'   => 'SVG-Edit Plugin (do=export_svg handler)',
                         'desc'   => 'Adds handler to have clean way for exporting SVGs',
                         'url'    => 'http://www.dokuwiki.org/plugin:svgedit'
                 );
		}
 
    function register(&$controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this,
                                   '_hookdo');
    }
 
    function _hookdo(&$event, $param) {
			global $ID;
      if($event->data === 'export_svg' && auth_quickaclcheck($ID) >= AUTH_READ) {
				header('Content-type: image/svg+xml');
				die(rawWiki($ID));
			}
    }
}
