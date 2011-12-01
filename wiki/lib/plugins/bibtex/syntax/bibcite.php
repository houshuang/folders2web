<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_bibtex_bibcite extends DokuWiki_Syntax_Plugin {

    function getInfo(){
        return array(
            'author' => 'Yann Hodique',
            'email'  => 'yann.hodique@gmail.com',
            'date'   => '2006-12-09',
            'name'   => 'BibTeX Plugin (bibcite component)',
            'desc'   => 'parses bibtex blocks',
            'url'    => 'http://hodique.info'
                     );
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){ return 'substition'; }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 359;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{\[[^]]*\]}',$mode,'plugin_bibtex_bibcite');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos) {
        $match = substr($match,2,-2);
        return array($match);
    }
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        // global $conf;

        if($mode == 'xhtml') {
            $renderer->doc .= "[";
            $several = False;
            foreach (explode(",", $data[0]) as $cite) {
                if ($several)
                    $renderer->doc .= ",";
                list($file,$ref) = explode(":",$cite);
                $renderer->doc .= '<a href=/_bib/' . $file . '/' . $ref .'>' . $ref . '</a>';
                $several = True;
            }
            $renderer->doc .= "]";
        }
        return true;

    }
}

?>