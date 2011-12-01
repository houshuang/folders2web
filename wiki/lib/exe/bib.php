<?php
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/html.php');
require_once(DOKU_INC.'inc/pageutils.php');
require_once(DOKU_INC.'inc/confutils.php');
require_once(DOKU_INC.'inc/auth.php');
require_once(DOKU_INC.'inc/template.php');
require_once(DOKU_PLUGIN.'bibtex/syntax/bibtex.php');
 
//close session
session_write_close();
 
function bib_content() {
 
    $pathToOsbib = DOKU_PLUGIN.'bibtex/OSBib/';
    include_once($pathToOsbib.'format/bibtexParse/PARSEENTRIES.php');
    include_once( $pathToOsbib.'format/BIBFORMAT.php');
 
    $data = parseBibFile($_REQUEST['file']);
 
    /* Get the bibtex entries into an associative array */
    $parse = NEW PARSEENTRIES();
    $parse->expandMacro = TRUE;
    $parse->fieldExtract = TRUE;
    $parse->removeDelimit = TRUE;
    $parse->loadBibtexString($data);
    $parse->extractEntries();
    list($preamble, $strings, $entries) = $parse->returnArrays();
 
    $ref = $_REQUEST['ref'];
    foreach ($entries as $entry) {
        if (trim($entry['bibtexCitation']) == $ref)
            return rawOutput($entry);
    }
}
 
include(template('bib.php'));
 
//Setup VIM: ex: et ts=2 enc=utf-8 :
?>
