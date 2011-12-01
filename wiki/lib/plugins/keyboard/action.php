<?php

/**
 * Keyboard Action Plugin: Inserts button for keyboard plugin into toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Gina Haeussge <osd@foosel.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC'))
	die();

if (!defined('DOKU_PLUGIN'))
	define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');

if (!defined('NL'))
	define('NL', "\n");

class action_plugin_keyboard extends DokuWiki_Action_Plugin {

	/**
	 * Return some info
	 */
	function getInfo() {
		return array (
			'author' => 'Gina Haeussge',
			'email' => 'osd@foosel.net',
			'date' => '2007-05-04',
			'name' => 'Keyboard Action Plugin',
			'desc' => 'Inserts button for keyboard plugin into toolbar',
			'url' => 'http://wiki.foosel.net/snippets/dokuwiki/keyboard',
			
		);
	}

	/**
	 * Register the eventhandlers
	 */
	function register(& $contr) {
		$contr->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array ());
	}
	
	/**
	 * Inserts the toolbar button
	 */
	function insert_button(&$event, $param) {
		$event->data[] = array(	
			'type'   => 'format',
			'title'  => $this->getLang('qb_keyboard'),
			'icon'   => '../../plugins/keyboard/keyboard.png',
			'open'   => '<key>',
			'close'  => '</key>',
		);
	
		return $event->_default;
	}

}
