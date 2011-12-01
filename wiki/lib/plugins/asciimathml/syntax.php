<?php
/**
 * DokuWiki Plugin asciimathml (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Mohammad Rahmani <m [dot] rahmani [at] aut [dot] ac [dot] ir>

 *  Rev. 0.20: Some bugs fixed
 *  Date: Thursday, June 16, 2011

 *  Rev. 0.21: Some bugs fixed, support for the latest version of Dokuwiki (2011-05-25a)
 *  Date: Thursday, June 23, 2011
 *  -Note use <acm> tag for inline formatting
 *        use <acmath> tag for block formatting

 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_asciimathml extends DokuWiki_Syntax_Plugin {
    public function getType(){ return 'formatting'; }
    public function getPType(){ return 'normal'; }
    public function getSort(){ return 450; }

     /**
     * Connect pattern to lexer
     */
    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('<acm>(?=.*?</acm>)',$mode,'plugin_asciimathml');
        $this->Lexer->addEntryPattern('<acmath>(?=.*?</acmath>)',$mode,'plugin_asciimathml');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</acm>','plugin_asciimathml');
        $this->Lexer->addExitPattern('</acmath>','plugin_asciimathml');
    }


/**
     * Handler to prepare matched data for the rendering process.
     */
    public function handle($match, $state, $pos, &$handler){
        switch ($state) {
        case DOKU_LEXER_ENTER :
            return array($state, preg_match($match, "/^<acm>/"));
            break;
        case DOKU_LEXER_MATCHED :
            break;
        case DOKU_LEXER_UNMATCHED :
            return array($state, $match);
            break;
        case DOKU_LEXER_EXIT :
            return array($state, preg_match($match, "/^<\/acm>/"));
            break;
        case DOKU_LEXER_SPECIAL :
            break;
        }
        return array($state, '');
    }

   /**
     * Handle the actual output creation.
     */
    public function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;
        list($state, $match) = $data;
        switch ($state) {
        case DOKU_LEXER_ENTER :
            if ($match) {
                $renderer->doc .= '<span class="acmath"> `';
            } else {
                $renderer->doc .= '<div class="acmath"> `';
            }
            break;
        case DOKU_LEXER_MATCHED :
            break;
        case DOKU_LEXER_UNMATCHED :
            $renderer->doc .= $renderer->_xmlEntities($match);
            break;
        case DOKU_LEXER_EXIT :
            if ($match) {
                $renderer->doc .= ' `</span>';
            } else {
                $renderer->doc .= ' `</div>';
            }
            break;
        }
    }
}

?>