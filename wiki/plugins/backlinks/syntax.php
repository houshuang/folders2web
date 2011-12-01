<?php
/**
 * DokuWiki Syntax Plugin Backlinks
 *
 * Shows a list of pages that link back to a given page.
 *
 * Syntax:  {{backlinks>[pagename]}}
 *
 *   [pagename] - a valid wiki pagename
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Michael Klier <chi@chimeric.de>
 */
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('DW_LF')) define('DW_LF',"\n");

require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/parserutils.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_backlinks extends DokuWiki_Syntax_Plugin {


    /**
     * General Info
     */
    function getInfo(){
        return array(
            'author' => 'Michael Klier',
            'email'  => 'chi@chimeric.de',
            'date'   => @file_get_contents(DOKU_PLUGIN.'backlinks/VERSION'),
            'name'   => 'Backlinks',
            'desc'   => 'Displays backlinks to a given page.',
            'url'    => 'http://dokuwiki.org/plugin:backlinks2'
        );
    }

    /**
     * Syntax Type
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     */
    function getType()  { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort()  { return 304; }
    
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{backlinks>.+?\}\}', $mode, 'plugin_backlinks');
        $this->Lexer->addSpecialPattern('~~BACKLINKS~~', $mode, 'plugin_backlinks');
    }

    /**
     * Handler to prepare matched data for the rendering process
     */
    function handle($match, $state, $pos, &$handler){
        global $ID;

        if($match == '~~BACKLINKS~~') { //check for deprecated syntax
            $match = $ID;
        } else {
            $match = substr($match,12,-2); //strip {{backlinks> from start and }} from end
            $match = ($match == '.') ? $ID : $match;

            if(strstr($match,".:")) {
                resolve_pageid(getNS($ID),$match,$exists);
            }
        }

        return (array($match));
    }

    /**
     * Handles the actual output creation.
     */
    function render($mode, &$renderer, $data) {
        global $lang;

        if($mode == 'xhtml'){
            $renderer->info['cache'] = false;
            
            @require_once(DOKU_INC.'inc/fulltext.php');
            $backlinks = ft_backlinks($data[0]);
            
            $renderer->doc .= '<div id="plugin__backlinks">' . DW_LF;

            if(!empty($backlinks)) {

                $renderer->doc .= '<ul class="idx">';

                foreach($backlinks as $backlink){
                    $name = p_get_metadata($backlink,'title');
                    if(empty($name)) $name = $backlink;
                    $renderer->doc .= '<li><div class="li">';
                    $renderer->doc .= html_wikilink(':'.$backlink,$name,'');
                    $renderer->doc .= '</div></li>';
                }

                $renderer->doc .= '</ul>';
            } else {
                $renderer->doc .= "<strong>Plugin Backlinks: " . $lang['nothingfound'] . "</strong>";
            }
            
            $renderer->doc .= '</div>' . DW_LF;

            return true;
        }
        return false;
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
