<?php
/**
 * Emphasis Plugin: Enables text highlighting with 
 *                  ::text::, :::text:::, ::::text::::
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stefan Hechenberger <foss@stefanix.net>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 

 
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_emphasis extends DokuWiki_Syntax_Plugin {
 
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Stefan Hechenberger',
            'email'  => 'foss@stefanix.net',
            'date'   => '2007-02-09',
            'name'   => 'Emphasis Plugin',
            'desc'   => 'Enables different levels of highlighted text',
            'url'    => 'http://wiki.splitbrain.org/plugin:emphasis',
        );
    }
 
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }
	
 
    /**
     * Where to sort in?
     */ 
    function getSort(){
        return 922;
    }
 
 
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern(':{2,4}.*?:{2,4}',$mode, substr(get_class($this), 7));
    }

 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        if((substr($match, 0, 4) == '::::') and (substr($match, -4, 4) == '::::')) {
            $deg = 3;
            $emtext = substr(substr($match, 4), 0, -4);
        } else if((substr($match, 0, 3) == ':::') and (substr($match, -3, 3) == ':::')) {
            $deg = 2;
            $emtext = substr(substr($match, 3), 0, -3);            
        } else if((substr($match, 0, 2) == '::') and (substr($match, -2, 2) == '::')) {
            $deg = 1;
            $emtext = substr(substr($match, 2), 0, -2);            
        } else {
            $emtext = $match;
            $deg =1;   
        }
        return array($emtext, $deg);
    }            
 
 
     /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        list($emtext, $deg) = $data;
        if($mode == 'xhtml'){
            $renderer->doc .= '<span class="emphasis'.$deg.'">'.$emtext.'</span>';
            return true;
        }
        return false;
    }
 
 

}
