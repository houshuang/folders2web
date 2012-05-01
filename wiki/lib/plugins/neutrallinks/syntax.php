<?php
/**
 * Plugin Now: Inserts a timestamp.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 */
 
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_neutrallinks extends DokuWiki_Syntax_Plugin {
 
 
    function getType() { return 'substition'; }
    function getSort() { return 2; }
    function getAllowedTypes() { return array();}
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\[#.+?\]',$mode,'plugin_neutrallinks');
//        $this->Lexer->addSpecialPattern('NOW',$mode,'plugin_neutrallinks');
    }
 
    function handle($match, $state, $pos, &$handler) {
        return array($match, $state, $pos);
    }
 
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
						$text = substr($data[0],1,-1);
						$split = preg_split('/\|/', $text);
						// if preg_match('/\|/',$text){
							$renderer->doc .= $renderer->internallink($split[0], $split[1]);
						// } else{
						// $renderer->doc .= $renderer->internallink($split[0]);};
            return true;
        }
        return false;
    }
}