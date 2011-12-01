<?php
/**
 *  Indexmenu Action component
 *
 *  $Id: action.php 113 2009-01-13 16:24:03Z wingedfox $
 *  $HeadURL: https://svn.debugger.ru/repos/common/DokuWiki/Indexmenu2/tags/Indexmenu2.v2.1.2/action.php $
 *
 *  @lastmodified $Date: 2009-01-13 19:24:03 +0300 (Втр, 13 Янв 2009) $
 *  @license      LGPL 2 (http://www.gnu.org/licenses/lgpl.html)
 *  @author       Ilya Lebedev <ilya@lebedev.net>
 *  @version      $Rev: 113 $
 *  @copyright    (c) 2005-2007, Ilya Lebedev
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
 
class action_plugin_indexmenu extends DokuWiki_Action_Plugin {
 
  /**
   * return some info
   */
  function getInfo(){
      preg_match("#^.*?Indexmenu2/([^\\/]+)#"," $HeadURL: https://svn.debugger.ru/repos/common/DokuWiki/Indexmenu2/tags/Indexmenu2.v2.1.2/action.php $ ", $v);
      $v = preg_replace("#.*?((trunk|.v)[\d.]+)#","\\1",$v[1]);
      $b = preg_replace("/\\D/","", " $Rev: 113 $ ");
      return array( 'author' => "Ilya Lebedev"
                   ,'email'  => 'ilya@lebedev.net'
                   ,'date'   => preg_replace("#.*?(\d{4}-\d{2}-\d{2}).*#","\\1",'$Date: 2009-01-13 19:24:03 +0300 (Втр, 13 Янв 2009) $')
                   ,'name'   => "Indexmenu 2 {$v}.$b Action component."
                   ,'desc'   => "Performs special Indexmenu actions."
                   ,'url'    => 'http://wiki.splitbrain.org/plugin:indexmenu2'
                  );
  }
    
  /*
   * plugin should use this method to register its handlers with the dokuwiki's event controller
   */
  function register(&$controller) {
      $controller->register_hook('TPL_METAHEADER_OUTPUT','BEFORE', $this, '_inject_loader');
      $controller->register_hook('PARSER_CACHE_USE', 'BEFORE',  $this, '_purgecache');
      $r = $this->getConf('replace_idx');
      if (@$r) {
          $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'html_index_replacement');
      }
  }
  
  /**
   *  Inject the virtual keyboard loaders
   *
   *  @author Ilya Lebedev <ilya@lebedev.net>
   *  @param $event object target event
   *  @param $param mixed event parameters passed from register_hook
   */
  function _inject_loader (&$event, $param) {
      global $INFO;
      global $ACT;

      if ($ACT != 'show' && $ACT != 'preview') return; // nothing to do for us

      $event->data['script'][] = array( 'type'=>'text/javascript', 'charset'=>'utf-8', 'src' => DOKU_BASE.'/lib/plugins/indexmenu/cms/cms.js'
                                       ,'_data'=>'');
  }

  /**
   * Check for pages changes and eventually purge cache.
   *
   * @author Samuele Tognini <samuele@cli.di.unipi.it>
   * @author Ilya Lebedev <ilya@lebedev.net>
   */
  function _purgecache(&$event, $param) {
    global $ID;
    global $conf;
    //purge only xhtml cache
    if ($event->data->mode != "xhtml") return;
    //Check if it is an indexmenu page
    if (!p_get_metadata($ID,'indexmenu')) return;
    //Check if a page is more recent than purgefile.
    $event->preventDefault();
    $event->stopPropagation();
    $event->result = false;
  }
  /**
   *  Replaces the built-in namespace index with indexmenu one
   *
   *  @param mixed $event event object
   *  @param array $param event params
   *  @author Ilya Lebedev <ilya@lebedev.net>
   */
  function html_index_replacement (&$event, $param) {
    global $conf;
    global $ID;
    global $lang;

    if ('index' != $event->data) return;

    $info = "";
    $depth = (int)@$this->getConf('replace_idx_depth');
    if ($depth < 1 || $depth > 10) $depth = 1;
    $theme = $this->getConf('replace_idx_theme');
    if (@empty($theme)) $theme = 'IndexMenu';
    $ajax = 'ajax'==$this->getConf('replace_idx')?"|js#$theme+ajax":'';

    if ($this->getConf('replace_idx_msg')) {
        echo p_locale_xhtml('index');
        $instr = "";
    } else {
        $instr = "======{$lang['btn_index']}==\n\n";
    }
    $instr .= "{{indexmenu>.#$depth$ajax}}";
    $instr = p_get_instructions($instr);
    $instr = p_render('xhtml', $instr, $info);

    echo $instr;

    // prevent Dokuwiki normal processing of $ACT (it would clean the variable and destroy our 'index' value.
    $event->preventDefault();
    // index command belongs to us, there is no need to hold up Dokuwiki letting other plugins see if its for them
    $event->stopPropagation();
      
  }
}
