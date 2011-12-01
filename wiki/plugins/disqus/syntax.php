<?php
/**
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_disqus extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 160;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('~~DISQUS~~',$mode,'plugin_disqus');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        return array();
    }

    /**
     * Create output
     */
    function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;
        $R->doc .= $this->_disqus();
        return true;
    }

    function _disqus(){
        global $ID;
        global $INFO;

        $doc = '';
        $doc .= '<script charset="utf-8" type="text/javascript">
                    <!--//--><![CDATA[//><!--'."\n";
        if($this->getConf('devel'))
            $doc .= 'var disqus_developer = '.$this->getConf('devel').";\n";
        $doc .= "var disqus_url     = '".wl($ID,'',true)."';\n";
        $doc .= "var disqus_title   = '".addslashes($INFO['meta']['title'])."';\n";
        $doc .= "var disqus_message = '".addslashes($INFO['meta']['abstract'])."';\n";
        $doc .= 'var disqus_container_id = \'disqus__thread\';
                    //--><!]]>
                    </script>';
        $doc .= '<div id="disqus__thread"></div>';
        $doc .= '<script type="text/javascript" src="http://disqus.com/forums/'.$this->getConf('shortname').'/embed.js"></script>';
        $doc .= '<noscript><a href="http://'.$this->getConf('shortname').'.disqus.com/?url=ref">View the discussion thread.</a></noscript>';

        return $doc;
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
