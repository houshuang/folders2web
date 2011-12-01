<?php
/*
description : Tab control using AJAX for DokuWiki
author      : Ikuo Obataya
email       : I.Obataya@gmail.com
lastupdate  : 2008-10-13
license     : GPL 2 (http://www.gnu.org/licenses/gpl.html)
*/

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
@require_once(DOKU_PLUGIN.'syntax.php');
/**
 * Tab plugin
 */
class syntax_plugin_tabinclude extends DokuWiki_Syntax_Plugin{
 /**
  * return some info
  */
  function getInfo(){
    return array(
    'author' => 'Ikuo Obataya',
    'email'  => 'I.Obataya@gmail.com',
    'date'   => '2008-10-13',
    'name'   => 'Tab control for DokuWiki',
    'desc'   => 'Create tab control
                 {{tab>(page1),(page2),(page3)...}}',
    'url'    => '',
    );
  }
  function getType(){ return 'substition'; }
  function getSort(){ return 158; }
  function connectTo($mode){$this->Lexer->addSpecialPattern('\{\{tabinclude>[^}]*\}\}',$mode,'plugin_tabinclude');}
 /**
  * handle syntax
  */
  function handle($match, $state, $pos, &$handler){
    $match = substr($match,13,-2);
    $pages = explode(',',$match);
    return array($state,$pages);
  }
 /**
  * Render tab control
  *
  * Javascript event: pl_ti_onTabClicked(page)
  */
  function render($mode, &$renderer, $data) {
    global $conf;
    global $ID;
    list($state, $pages) = $data;
    $sz = count($pages);
    if($sz==0) return true;

    if ($mode=='xhtml'){
      $html.= '<div class="pl_ti"><div class="tabcontainer">'.NL;
      for($i=0;$i<$sz;$i++){
        $page = hsc(trim($pages[$i]));
        resolve_pageid(getNS($ID),$page,$exists);
        $title = p_get_metadata($page,'title');
        $title = empty($title)?$page:hsc(trim($title));
  
        if($i==0)
          $html.= '<input id="ti_initpage" type="hidden" value="'.$page.'"/>'.NL;
        $html.='<div class="tabs" id="tab'.$i.'" onclick="pl_ti_onTabClicked(\''.$page.'\')">';
        $html.=$title;
        $html.='</div>'.NL;
      }
      $html.='<div style="clear:left;"></div></div><div class="container">'.NL;
      if($this->getConf('hideloading')==0){
        $html.='<div id="ti_loading">connecting...</div>'.NL;
      }
      $html.='<div id="ti_content"></div>'.NL;
      $html.= '</div></div>'.NL;
      $renderer->doc.=$html;
      return true;
    }else if($mode=='odt'){
      $renderer->strong_open();
      $renderer->doc.='Tab pages';
      $renderer->strong_close();
      $renderer->p_close();

      $renderer->listu_open();
      for($i=0;$i<$sz;$i++){
        $page = hsc(trim($pages[$i]));
        resolve_pageid(getNS($ID),$page,$exists);
        $title = p_get_metadata($page,'title');
        $title = empty($title)?$page:hsc(trim($title));
        $abstract = p_get_metadata($page);

        $renderer->listitem_open();
        $renderer->p_open();
        $renderer->internallink($page,$title);
        $renderer->p_close();
        $renderer->p_open();
        if(is_array($abstract))
          $renderer->doc.=hsc($abstract['description']['abstract']);
        $renderer->p_close();
        $renderer->listitem_close();
      }
      $renderer->listu_close();
      $renderer->p_open();
      return true;
    }
    return false;
  }
} 
?>