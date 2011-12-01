<?php
/*
 * CSV Plugin: displays a cvs formatted file or inline data as a table
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Steven Danz <steven-danz@kc.rr.com>
 * @author     Gert
 * @author     Andreas Gohr <gohr@cosmocode.de>
 * @author     Jerry G. Geiger <JerryGeiger@web.de>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/*
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_csv extends DokuWiki_Syntax_Plugin {

    /*
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Andreas Gohr',
            'email'  => 'gohr@cosmocode.de',
            'date'   => '2009-11-14',
            'name'   => 'CSV Plugin',
            'desc'   => 'Displays a CSV file, or inline CSV data, as a table',
            'url'    => 'http://www.dokuwiki.org/plugin:csv',
        );
    }

    /*
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /*
     * Where to sort in?
     */
    function getSort(){
        return 155;
    }

    /*
     * Paragraph Type
     */
    function getPType(){
        return 'block';
    }

    /*
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern("<csv[^>]*>.*?(?:<\/csv>)",$mode,'plugin_csv');
    }


    /*
     * Handle the matches
     */
    function handle($match, $state, $pos, &$handler){
      global $INFO;
        $match = substr($match,4,-6);

        //default options
        $opt = array(
            'hdr_rows'    => 1,
            'hdr_cols'    => 0,
            'span_empty_cols' => 0,
            'file'    => '',
            'delim'   => ',',
            'content' => ''
        );

        list($optstr,$opt['content']) = explode('>',$match,2);
        unset($match);

        // parse options
        $optsin = explode(' ',$optstr);
        foreach($optsin as $o){
            $o = trim($o);
            if (preg_match("/(\w+)=(.*)/",$o,$matches)) {
                $opt[$matches[1]] = $matches[2];
            } elseif($o) {
                if(preg_match('/^https?:\/\//i',$o)){
                    $opt['file'] = $o;
                }else{
                    $opt['file'] = cleanID($o);
                    if(!strlen(getNS($opt['file'])))
                      $opt['file'] = $INFO['namespace'].':'.$opt['file'];
                }
            }
        }
        if($opt['delim'] == 'tab') $opt['delim'] = "\t";

        return $opt;
    }

    /*
     * Create output
     */
    function render($mode, &$renderer, $opt) {
        if($mode == 'metadata') return false;

        // load file data
        if($opt['file']){
            if(preg_match('/^https?:\/\//i',$opt['file'])){
                require_once(DOKU_INC.'inc/HTTPClient.php');
                $http = new DokuHTTPClient();
                $opt['content'] = $http->get($opt['file']);
                if(!$opt['content']){
                    $renderer->cdata('Failed to fetch remote CSV data');
                    return true;
                }
            }else{
                $opt['file'] = cleanID($opt['file']);
                $renderer->info['cache'] = false; //no caching
                if (auth_quickaclcheck(getNS($opt['file']).':*') < AUTH_READ) {
                    $renderer->cdata('Access denied to CSV data');
                    return true;
                } else {
                    $file = mediaFN($opt['file']);
                    $opt['content'] = io_readFile($file);
                    // if not valid UTF-8 is given we assume ISO-8859-1
                    if(!utf8_check($opt['content'])) $opt['content'] = utf8_encode($opt['content']);
                }
            }
        }

        $content =& $opt['content'];

        // clear any trailing or leading empty lines from the data set
        $content = preg_replace("/[\r\n]*$/","",$content);
        $content = preg_replace("/^\s*[\r\n]*/","",$content);

        if(!trim($content)){
            $renderer->cdata('No csv data found');
        }
        $rows = array();
        $maxcol=0;
        $maxrow=0;
        while($content != "") {
          $thisrow = $this->csv_explode_row($content,$opt['delim']);
          if($maxcol < count($thisrow))
              $maxcol = count($thisrow);
          array_push($rows, $thisrow);
            //$cells = $this->csv_explode_row($content,$opt['delim']);
            // some spreadsheet systems (i.e., excell) appear to
            // denote column spans with a completely empty cell
            // (to adjacent commas) and an 'empty' cell will
            // contain at least one blank space, so if the user
            // asks, use that for attempting to span columns
            // together
          $maxrow++;
        }
        // render table we need values e.g. for ODT plugin ... -jerry
        $renderer->table_open($maxcol, $maxrow);
        $row = 1;
        foreach($rows as $cells) {
            $renderer->tablerow_open();
            $spans = array();
            $span  = 0;
            $current = 0;
            foreach($cells as $cell) {
                if ($cell == '' && $opt['span_empty_cols']) {
                    $spans[$current] = 0;
                    $spans[$span]++;
                } else {
                    $spans[$current] = 1;
                    $span = $current;
                }
                $current++;
            }
            //handle empty line feature ;-) jerry
            if($current < 2) {
              $spans[0] = $maxcol;
            }
            $current = 0;
            foreach($cells as $cell) {
                $cell = preg_replace('/\\\\\\\\/',' ',$cell);
                if ($spans[$current] > 0) {
                    $align = 'left';
                    if ($spans[$current] > 1) {
                        $align = 'center';
                    }
                    if ($row <= $opt['hdr_rows'] || 
                      $current < $opt['hdr_cols'] || // empty line feature
                      ($current == 0 && $spans[0] == $maxcol)) {
                        $renderer->tableheader_open($spans[$current], $align);
                    } else {
                        $renderer->tablecell_open($spans[$current], $align);
                    }
                    $renderer->cdata($cell);
                    if ($row <= $opt['hdr_rows'] ||
                      $current < $opt['hdr_cols'] ||
                      ($current == 0 && $spans[0] == $maxcol)) {
                        $renderer->tableheader_close();
                    } else {
                        $renderer->tablecell_close();
                    }
                }
                $current++;
            }
            $renderer->tablerow_close();
            $row++;
        }
        $renderer->table_close();

        return true;
    }

    // Explode CSV string, consuming it as we go
    // RFC 4180 claims that a CSV is allowed to have a cell enclosed in ""
    // that embeds a newline.  Convert those newlines to \\ (trying to keep
    // to the DokuWiki syntax) which we will key off of later in render()
    // as an embedded newline.
    // Careful, there could be both embedded newlines, commas and quotes
    // One thing to remember is that a row must end with a newline
    function csv_explode_row(&$str, $delim = ',', $qual = "\"") {
        $len = strlen($str);
        $inside = false;
        $word = '';
        for ($i = 0; $i < $len; ++$i) {
            $next = $i+1;
            if ($str[$i]==$delim && !$inside) {
                $out[] = $word;
                $word = '';
            } elseif ($str[$i] == $qual && (!$inside || $next == $len || $str[$next] == $delim || $str[$next] == "\n")) {
                $inside = !$inside;
            } elseif ($str[$i] == $qual && $next != $len && $str[$next] == $qual) {
                $word .= $str[$i];
                $i++;
            } elseif ($str[$i] == "\n") {
                if ($inside) {
                    $word .= '\\\\';
                } else {
                    $str = substr($str, $next);
                    $out[] = $word;
                    return $out;
                }
            } else {
                $word .= $str[$i];
            }
        }
        $str = substr($str, $next);
        $out[] = $word;
        return $out;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
