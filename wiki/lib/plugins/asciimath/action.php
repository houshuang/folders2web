<?php
/**
 * Action Plugin:   Load AsciiMath javascript module as a header link
 * 
 * Based on example action plugin by: @author     Samuele Tognini <samuele@cli.di.unipi.it>
 * @author     Jens Lauritsen <info at epidata.dk>
    * This plugin uses ASCIIMathML.js version 1.4.8 Aug 30, 2007, (c) Peter Jipsen http://www.chapman.edu/~jipsen
    * But in the form used in the R-project. Copied from the javascript used in:
    * http://wiki.r-project.org/rwiki/doku.php?do=show&id=wiki%3Aasciimathml
    * Latest version at http://www.chapman.edu/~jipsen/mathml/ASCIIMathML.js
    * For changes see http://www.chapman.edu/~jipsen/mathml/asciimathchanges.txt
    * If you use it on a webpage, please send the URL to jipsen@chapman.edu
    * Note: This plugin ONLY SUPPORTS version 1.4.8 of ASCIIMathML.js
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
 
class action_plugin_asciimath extends DokuWiki_Action_Plugin {
 
  /**
   * return some info
   */
  function getInfo(){
    return array(
		 'author' => 'J Lauritsen',
		 'email'  => 'Info at EpiData.dk',
		 'date'   => '2009-02-09',
		 'name'   => 'Asciimathml (action plugin component)',
		 'desc'   => 'Add Math shwoing to dokuwiki pages based on AsciiMathML component',
		 'url'    => 'http://www.epidata.org/dokuwiki',
		 );
  }
 
  /**
   * Register its handlers with the DokuWiki's event controller
  */ 
   function register(&$controller) {
     $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE',  $this, '_hookjs');  
    }
 
  /**
   * Hook js script into page headers.
   *
   * Copied from example by: @author Samuele Tognini <samuele@cli.di.unipi.it>
   * J Lauritsen <info at Epidata.dk>
   */

  function _hookjs(&$event, $param) {
	$event->data["script"][] = array ("type" => "text/javascript",
                                          "charset" => "utf-8",
					  "_data" => "",
					  "src" => DOKU_BASE."lib/plugins/asciimath/asciimathml148r.js"
				          );
  }
}
