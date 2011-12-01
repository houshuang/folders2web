<?php
/**
 * This is the template for the snippets popup
 * @author Michael Klier <chi@chimeric.de>
 */
if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../../');
define('DOKU_MEDIAMANAGER',1); // needed to get proper CSS/JS

require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/lang/en/lang.php');
require_once(DOKU_INC.'inc/lang/'.$conf['lang'].'/lang.php');
require_once(DOKU_INC.'inc/media.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/search.php');
require_once(DOKU_INC.'inc/template.php');
require_once(DOKU_INC.'inc/auth.php');

if(isset($conf['plugin']['snippets']['snippets_page'])) {
    $page = $conf['plugin']['snippets']['snippets_page'];
} else {
    $page = 'snippets'; // use default if not set
}

if(!page_exists($page) or auth_quickaclcheck($page) < AUTH_READ) die();
$snippets =& plugin_load('syntax', 'snippets');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $conf['lang']?>" lang="<?php echo $conf['lang']?>" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php print $snippets->getLang('popup_title') . ' [' . $conf['title'] . ']' ?></title>
    <?php tpl_metaheaders()?>
    <link rel="shortcut icon" href="<?php echo DOKU_TPL?>images/favicon.ico" />
  </head>
  <body>
    <div class="dokuwiki">
      <div id="plugin_snippets__sidepane">
        <div id="plugin_snippets__opts"></div>
        <div id="plugin_snippets__idx">
          <?php print p_wiki_xhtml($page); ?>
        </div>
      </div>
      <div id="plugin_snippets__preview"></div>
    </div>
  </body>
</html>
