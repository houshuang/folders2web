<?php
/**
 * DokuWiki Plugin Snippets Syntax Component
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Klier <chi@chimeric.de>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_snippets extends DokuWiki_Syntax_Plugin {

    function getType(){ return 'protected';}
    function getAllowedTypes() { return array('container','substition','protected','disabled','formatting','paragraphs'); }
    function getPType(){ return 'block';}

    function getSort(){ return 195; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {       
      $this->Lexer->addEntryPattern('<snippet>(?=.*?</snippet>)',$mode,'plugin_snippets');
    }

    function postConnect() {
      $this->Lexer->addExitPattern('</snippet>', 'plugin_snippets');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){

        switch ($state) {
            case DOKU_LEXER_ENTER:
                return array('snippet_open',$data);
                break;
            case DOKU_LEXER_MATCHED:
            case DOKU_LEXER_UNMATCHED:                
                return array('data', $match);
                break;
            case DOKU_LEXER_EXIT:
                return array('snippet_close', $title);
                break;
        }       
        return false;
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $indata) {
        list($instr, $data) = $indata;
        if($mode == 'xhtml'){
            switch ($instr) {
                case 'snippet_open': 
                    $renderer->doc .= '<div class="plugin_snippets__info">' . DOKU_LF;
                    break;
                case 'snippet_close':
                    $renderer->doc .= '</div>' . DOKU_LF;
                    break;
                case 'data' :      
                    $renderer->doc .= $renderer->_xmlEntities($data); 
                    break;
            }
            return true;
        }
        return false;
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
