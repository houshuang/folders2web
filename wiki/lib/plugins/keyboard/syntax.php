<?php
/**
 * Keyboard Syntax Plugin: Marks text as keyboard key presses.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Gina Haeussge <osd@foosel.net>
 * @author     Christopher Arndt
 */

if(!defined('DOKU_INC'))
  define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_keyboard extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo() {
        return array(
            'author' => 'Gina Haeussge',
            'email'  => 'osd@foosel.net',
            'date'   => '2008-05-04',
            'name'   => 'Keyboard Syntax Plugin',
            'desc'   => 'Marks text as keyboard key presses. Enhancements by Christopher Arndt.',
            'url'    => 'http://wiki.foosel.net/snippets/dokuwiki/keyboard',
        );
    }

    function getType() { return 'formatting'; }

    function getAllowedTypes() {
        return array('formatting', 'substition', 'disabled');
    }

    function getSort(){ return 444; }

    function connectTo($mode) {
         $this->Lexer->addEntryPattern('<key>(?=.*?\x3C/key\x3E)', $mode, 'plugin_keyboard');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</key>', 'plugin_keyboard');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        switch ($state) {
            case DOKU_LEXER_ENTER :
                return array($state, '');
            case DOKU_LEXER_UNMATCHED :
                $keys = explode('-', $match);
                $keys = array_map('trim', $keys);
                return array($state, $keys);
            case DOKU_LEXER_EXIT:
                return array($state, '');
        }
        return array();
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        global $lang;
        global $conf;
        
        include_once(dirname(__FILE__).'/lang/en/lang.php');
        @include_once(dirname(__FILE__).'/lang/'.$conf['lang'].'/lang.php');
        
        if ($mode == 'xhtml') {
            list($state, $match) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $renderer->doc .= '<kbd>';
                    break;
                case DOKU_LEXER_UNMATCHED :
                    foreach ($match as $key) {
                        if (substr($key, 0, 1) == "'" and
                          substr($key, -1, 1) == "'" and
                          strlen($key) > 1) {
                            $out[] = $renderer->_xmlEntities(substr($key,1,-1));
                        } else {
                            if (isset($lang[$key])) {
                                $out[] = $lang[$key];
                            } else {
                                $out[] = $renderer->_xmlEntities(ucfirst($key));
                            }
                        }
                    }
                    $renderer->doc .= implode('</kbd>+<kbd>', $out);
                    break;
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</kbd>';
                    break;
            }
            return true;
        }
        return false;
    }
}
