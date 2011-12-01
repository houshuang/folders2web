<?php
/**
 *  Indexmenu2 plugin, Navigation component
 *
 *  $Id: navigation.php 93 2007-05-07 11:56:33Z wingedfox $
 *  $HeadURL: https://svn.debugger.ru/repos/common/DokuWiki/Indexmenu2/tags/Indexmenu2.v2.1.2/syntax/navigation.php $
 *
 *  Syntax:     <navigation indexmenu2_options>
 *               * list
 *               * [[of links]]
 *               * {{to be shown}}
 *               * {{indexmenu>:in:the:navigation}}
 *              </navigation>
 * 
 *  @lastmodified $Date: 2007-05-07 15:56:33 +0400 (Пнд, 07 Май 2007) $
 *  @license      LGPL 2 (http://www.gnu.org/licenses/lgpl.html)
 *  @author       Ilya Lebedev <ilya@lebedev.net>
 *  @version      $Rev: 93 $
 *  @copyright    (c) 2005-2007, Ilya Lebedev
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'indexmenu/syntax/indexmenu.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_indexmenu_navigation extends DokuWiki_Syntax_Plugin {

    var $syntax = "";

    /**
     * return some info
     */
    function getInfo(){
        preg_match("#^.+Indexmenu2[/.]([^\\/]+)#"," $HeadURL: https://svn.debugger.ru/repos/common/DokuWiki/Indexmenu2/tags/Indexmenu2.v2.1.2/syntax/navigation.php $ ", $v);
        $v = preg_replace("#.*?((trunk|\.v)[\d.]+)#","\\1",$v[1]);
        $b = preg_replace("/\\D/","", " $Rev: 93 $ ");

        return array( 'author' => 'Ilya Lebedev'
                     ,'email'  => 'ilya@lebedev.net'
                     ,'date'   => preg_replace("#.*?(\d{4}-\d{2}-\d{2}).*#","\\1",'$Date: 2007-05-07 15:56:33 +0400 (Пнд, 07 Май 2007) $')
                     ,'name'   => "Indexmenu2 Navigation module {$v}.$b"
                     ,'desc'   => 'Module builds navigation menu from any unordered list, with optionally nested indexmenu sections'
                     ,'url'    => 'http://wiki.splitbrain.org/plugin:indexmenu2'
                    );
    }

    function getType(){ return 'protected';}
    function getPType(){ return 'block'; }

    // must return a number lower than returned by native 'file' mode (210)
    function getSort(){ return 138; }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {       
        $this->Lexer->addEntryPattern("<navigation(?=[^\r\n]*?\x3E.*?\x3C/navigation\x3E)",$mode,'plugin_indexmenu_navigation');
    }
    
    function postConnect() {
        $this->Lexer->addExitPattern('\x3C/navigation\x3E', 'plugin_indexmenu_navigation');
    }
    
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
            
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $this->syntax = substr($match, 1);
                return false;
                                
            case DOKU_LEXER_UNMATCHED:
                // will include everything from <code ... to ... </code >
                // e.g. ... [lang] [|title] > [content]
                list($opts, $content) = preg_split('/>/u',trim($match),2);

                return array(true, $content, $opts);
        }      
        return false;
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        switch ($mode) {
            case 'xhtml' :
                if (true === $data[0]) {
                    list(,$content, $opts) = $data;
                    $opts = syntax_plugin_indexmenu_indexmenu::parseOptions($opts);

                    $instr = p_get_instructions($content);
                    $this->__cleanInstructions ($instr,$opts);
                    /*
                    *  remove empty menu includes and wrap text with anchors, if menu item has a submenu
                    */
                    /*
                    $instr = preg_replace( "#(<li[^>]*>)([^<]*)(<(ul))#"
                                          ,"\\1<a href=\"#\" class=\"wikilink1\" title=\"\\2\">\\2</a>\\3"
                                          ,preg_replace( "#</li>\\s*<li[^>]*>\\s*<ul#"
                                                        ,"<ul"
                                                        ,p_render($mode, $instr, $info)));
                    /**/
                    $info = null;
                    $instr = preg_replace( "#<li[^>]*>\s*<li#"
                                          ,"<li"
                                          ,preg_replace( "#</li>\\s*</li#"
                                                        ,"</li"
                                                        ,p_render($mode, $instr, $info)));
                    $renderer->doc .= syntax_plugin_indexmenu_indexmenu::getHTML ($opts[1], $instr);

                    return true;
                }
                break;
            default:
                break;
        }
        return false;
    }
    /**
     *  Clean instructions from unallowed stuff
     *
     *  @author Ilya Lebedev <ilya@lebedev.net>
     *  @param $instr mixed list of instructions
     *  @param $opts mixed list of menu options
     *  @return mixed filtered list
     *  @access private
     */
    function __cleanInstructions (&$instr, $opts) {
        /*
        *  use this flag to skip allowed data in disallowed places
        */
        $li_open = false;
        foreach ($instr as $k=>$v) {
            switch ($v[0]) {
                case "document_start":
                case "document_end":
                case "listu_close":
                case "listu_open":
                case "listitem_close":
                    $li_open = false;
                    break;
                case "listitem_open":
                    $li_open = true;
                    break;
                case "plugin":
                    if (!$li_open) {
                        unset($instr[$k]);
                        break;
                    }
                    if ('indexmenu_indexmenu' === $v[1][0]) {
                        $instr[$k][1][1][1]['js'] = false;
                        $instr[$k][1][1][1]['ajax'] = $opts[1]['ajax'];
                        $instr[$k][1][1][1]['navigation'] = true;
                    }
                    break;
                default:
                    if (!$li_open) unset($instr[$k]);
                    break;
                case "listcontent_open" :
                case "listcontent_close" :
                    unset($instr[$k]);
                    break;
            }
        }
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
