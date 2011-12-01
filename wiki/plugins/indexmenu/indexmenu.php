<?php

require_once (DOKU_PLUGIN.'/indexmenu/syntax/indexmenu.php');
/*
*  Returns submenu tree for specified namespace
*
*  @param string $ns namespace title
*  @param string $sort sorting mode
*  @return {String} html markup for the submenu
*  @access public
*/
function indexmenu_getsubmenu ($ns,$s) {

  global $conf;
  $opts = array($ns, array('level' => 1, // get only 1st level
                    'ajax' => true,
                    'js' => false,
                    'navigation' => false,
                    'sort' => $s
                   ));

  $im = & new syntax_plugin_indexmenu_indexmenu();
  return preg_replace(array("#^<ul[^>]+>#i","#</ul>$#i"),"",$im->_indexmenu($opts));
}
