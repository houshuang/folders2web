<?php
/**
 * AJAX call handler for tagindex admin plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Gina Häußge, Michael Klier <dokuwiki@chimeric.de>
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Arthur Lobert <arthur.lobert@gmail.com>
 */

if (!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) . '/../../../') . '/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if (!defined('DOKU_REG')) define ('DOKU_REG', DOKU_PLUGIN.'autolink3/register/');
if (!defined('DOKU_PAGE')) define ('DOKU_PAGE', DOKU_INC.'data/pages');
if (!defined('DOKU_PAGES')) define ('DOKU_PAGES', realpath(DOKU_PAGE));
if (!defined('NL')) define('NL', "\n");

define('AUTH_NONE',0);
define('AUTH_READ',1);
define('AUTH_EDIT',2);
define('AUTH_CREATE',4);
define('AUTH_UPLOAD',8);
define('AUTH_DELETE',16);
define('AUTH_ADMIN',255);

//fix for Opera XMLHttpRequests
if(!count($_POST) && $HTTP_RAW_POST_DATA)
{
	parse_str($HTTP_RAW_POST_DATA, $_POST);
}

require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/events.php');
require_once(DOKU_INC.'inc/pageutils.php');
require_once(DOKU_INC.'inc/html.php');
require_once(DOKU_INC.'inc/auth.php');
require_once(DOKU_INC.'inc/actions.php');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/pageutils.php');
require_once(DOKU_INC.'inc/auth.php');
require_once(DOKU_INC.'inc/search.php');
require_once(DOKU_INC.'inc/indexer.php');
	
//close session
session_write_close();
header('Content-Type: text/plain; charset=utf-8');

//clear all index files
if (@file_exists($conf['indexdir'].'/page.idx'))// new word length based index
{
	$tag_idx = $conf['indexdir'].'/topic.idx';
}
else
{                                          // old index
$tag_idx = $conf['cachedir'].'/topic.idx';
}
$tag_helper =& plugin_load('helper', 'tag');

//call the requested function
$call = 'ajax_'.$_POST['call'];
if(function_exists($call))
{
	$call();
}
else
{
	print "The called function '".htmlspecialchars($call)."' does not exist!";
}

function ajax_save_page()
{
		if ($_POST['range'][0] == '0' && $_POST['range'][1] == '-')
			$_POST['range'][0] = '1';
	$tab = rawWikiSlices($_POST['range'], $_POST['page']);
	if ($tab[2])
		$text = $tab[0].$_POST['text'].'='.$tab[2];
	else 	
		$text = $tab[0].$_POST['text'].$tab[2];
	saveWikiText($_POST['page'],$text,$_POST['sub'], $_POST['minor']);
	unlock($_POST['page']);
	print 1;
}

function ajax_get_auth()
{
	global $INFO;
	$INFO = pageinfo();
	
	if ($INFO['editable'] == 1){
		print 1;
	}
	else{
		print false;
	}
}

function ajax_get_text() {

		lock($_POST['page']);
		$lock = $conf['lockdir'].'/_tagindexer.lock';
		// we're finished
		if ($_POST['range'][0] == '0' && $_POST['range'][1] == '-')
			$_POST['range'][0] = '1';
		$t = rawWikiSlices($_POST['range'], $_POST['page'], false);
		print 	$t[1];
	}

?>