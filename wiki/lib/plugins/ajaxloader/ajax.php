<?php
/**
 * DokuWiki AJAX call handler
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Adrian Lang <lang@cosmocode.de>
 */

//fix for Opera XMLHttpRequests
if(!count($_POST) && !empty($HTTP_RAW_POST_DATA)){
  parse_str($HTTP_RAW_POST_DATA, $_POST);
}

if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');
require_once DOKU_INC.'inc/init.php';
//close session
session_write_close();

header('Content-Type: text/html; charset=utf-8');

if(isset($_POST['call']))
  $call = $_POST['call'];
else if(isset($_GET['call']))
  $call = $_GET['call'];
else
  exit;


include 'common.php';

# Fallback if everything fails
include DOKU_INC.'lib/exe/ajax.php';

//Setup VIM: ex: et ts=2 enc=utf-8 :
