<?php
/**
 * Blockquote Plugin
 *
 * Allows correctly formatted blockquotes. Action component provides toolbar
 * button.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Tobias Deutsch <tobias@strix.at>
 * @author     Gina Haeussge <osd@foosel.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC'))
	die();

if (!defined('DOKU_PLUGIN'))
	define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
	
require_once (DOKU_PLUGIN . 'action.php');

class action_plugin_blockquote extends DokuWiki_Action_Plugin {
	
	/**
	 * return some info
	 */
	function getInfo() {
		return array (
			'author' => 'Gina Haeussge',
			'email' => 'osd@foosel.net',
			'date' => '2008-05-04',
			'name' => 'Blockquote Plugin (action component)',
			'desc' => 'Provides an environment for quotes in a semantically correct way using the blockquote XHTML tag. Action component provides toolbar button.',
			'url' => 'http://wiki.foosel.net/snippets/dokuwiki/blockquote',
			
		);
	}

	/**
	 * register the eventhandlers
	 */
	function register(& $controller) {
		$controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'blockquote_button', array ());
	}

	/**
	 * Inserts a toolbar button
	 */
	function blockquote_button(& $event, $param) {
		$event->data[] = array (
			'type' => 'format',
			'title' => $this->getLang('qb_blockquote'),
			'icon' => '../../plugins/blockquote/images/blockquote-icon.png',
			'open' => '<blockquote>',
			'close' => '</blockquote>',
			
		);

		return true;
	}
}