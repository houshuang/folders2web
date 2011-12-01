<?php
/**
 * AJAX call handler for tabinclude plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Ikuo Obataya <I.Obataya@gmail.com>
 */

// This AJAX handler was modified from searchindex plugin by Andreas Gohr

//fix for Opera XMLHttpRequests
if(!count($_POST) && $HTTP_RAW_POST_DATA){
  parse_str($HTTP_RAW_POST_DATA, $_POST);
}

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/pageutils.php');
require_once(DOKU_INC.'inc/auth.php');
require_once(DOKU_INC.'inc/template.php');
require_once(DOKU_INC.'inc/html.php');

require_once(DOKU_PLUGIN.'tabinclude/syntax.php');

//close sesseion
session_write_close();

header('Content-Type: text/plain; charset=utf-8');

//call the requested function
$call = 'ajax_'.$_POST['call'];
if(function_exists($call)){
    $call();
}else{
    print "The called function '".htmlspecialchars($call)."' does not exist!";
}

function ajax_content(){
  global $conf;
  global $ACT;
  global $ID;
  $ID=$_POST['page'];
  if(auth_quickaclcheck($ID) < AUTH_READ){
    exit;
  }
  $ACT = 'show';
  tpl_content_core();
  
  $ti_pl_obj = new syntax_plugin_tabinclude();
  if($ti_pl_obj->getConf('showpagename')==1){
    echo '<hr/><div class="ti_source"><span class="ti_source_page">';
    tpl_link(wl($ID),$ID);
    echo '</span></div>'.NL;
  }
}
?>
