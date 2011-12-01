<?php
/**
 * Discussion Plugin
 *
 * Enables/disables discussion features based on config settings.
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Esther Brunner <wikidesign@gmail.com>
 * @author  Dave Lawson <dlawson@masterytech.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_discussion_comments extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo() {
        return array(
                'author' => 'Gina Häußge, Michael Klier, Esther Brunner',
                'email'  => 'dokuwiki@chimeric.de',
                'date'   => @file_get_contents(DOKU_PLUGIN.'discussion/VERSION'),
                'name'   => 'Discussion Plugin (comments component)',
                'desc'   => 'Enables discussion features',
                'url'    => 'http://wiki.splitbrain.org/plugin:discussion',
                );
    }

    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 230; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        if ($mode == 'base') {
            $this->Lexer->addSpecialPattern('~~DISCUSSION[^\r\n]*?~~', $mode, 'plugin_discussion_comments');
        }
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        global $ID, $ACT;

        // strip markup
        $match = substr($match, 12, -2);

        // split title (if there is one)
        list($match, $title) = explode('|', $match, 2);

        // assign discussion state
        if ($match == ':off') $status = 0;
        else if ($match == ':closed') $status = 2;
        else $status = 1;

        if ($ACT == 'preview') return;

        // get discussion meta file name
        $file = metaFN($ID, '.comments');

        $data = array();
        if (@file_exists($file)) {
            $data = unserialize(io_readFile($file, false));
        }
        $data['title']  = $title;
        $data['status'] = $status;
        io_saveFile($file, serialize($data));

        return $status;
    }

    function render($mode, &$renderer, $status) {
        return true; // do nothing -> everything is handled in action component
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
