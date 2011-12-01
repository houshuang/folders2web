<?php
/**
 *  Info Indexmenu: Displays the index of a specified namespace. 
 *
 *  $Id: indexmenu.php 93 2007-05-07 11:56:33Z wingedfox $
 *  $HeadURL: https://svn.debugger.ru/repos/common/DokuWiki/Indexmenu2/tags/Indexmenu2.v2.1.2/syntax/indexmenu.php $
 *
 *  @lastmodified $Date: 2007-05-07 15:56:33 +0400 (Пнд, 07 Май 2007) $
 *  @license      LGPL 2 (http://www.gnu.org/licenses/lgpl.html)
 *  @author       Ilya Lebedev <ilya@lebedev.net>
 *  @version      $Rev: 93 $
 *  @copyright    (c) 2005-2007, Ilya Lebedev
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('INDEXMENU_FS_IMAGES')) define('INDEXMENU_FS_IMAGES',realpath(dirname(__FILE__)."/../templates")."/");
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/search.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_indexmenu_indexmenu extends DokuWiki_Syntax_Plugin {

    /**
     *  sorting target and reverse flag
     *
     */
    var $s_target = array('fn' => 'target', 'title' => 'title' , 'date' => 'date');
    var $s_rev = false;
  /**
   * return some info
   */
  function getInfo() {
      preg_match("#^.+Indexmenu2[/.]([^\\/]+)#"," $HeadURL: https://svn.debugger.ru/repos/common/DokuWiki/Indexmenu2/tags/Indexmenu2.v2.1.2/syntax/indexmenu.php $ ", $v);
      $v = preg_replace("#.*?((trunk|\.v)[\d.]+)#","\\1",$v[1]);
      $b = preg_replace("/\\D/","", " $Rev: 93 $ ");

      return array( 'author' => "Ilya Lebedev"
                   ,'email'  => 'ilya@lebedev.net'
                   ,'date'   => preg_replace("#.*?(\d{4}-\d{2}-\d{2}).*#","\\1",'$Date: 2007-05-07 15:56:33 +0400 (Пнд, 07 Май 2007) $')
                   ,'name'   => "Indexmenu 2 {$v}.$b"
                   ,'desc'   => "Insert the index of a specified namespace.\nJavascript code: http://cms.debugger.ru by Ilya Lebedev."
                   ,'url'    => 'http://wiki.splitbrain.org/plugin:indexmenu2'
                  );
  }
  
  /**
   * What kind of syntax are we?
   */
  function getType(){
    return 'substition';
  }

  function getPType(){
    return 'block';
  } 
  /**
   * Where to sort in?
   */
  function getSort(){
    return 138;
  }
  /**
   *  Emulates getConf for DW releases prior to current RCs
   *
   *  @param string $var variable to get
   *  @return string value
   *  @access protected
   */
  function getConf($var) {
    global $conf;
    if (method_exists(DokuWiki_Syntax_Plugin,'getConf')) {
      if (!$this) {
        $tmp = & new syntax_plugin_indexmenu_indexmenu();
        $res = $tmp->getConf($var);
        unset ($tmp);
        return $res;
      } else {
        return parent::getConf($var);
      }
    } else {
      return @$conf['plugin_indexmenu'][$var];
    }
  }
 
  /**
   * Connect pattern to lexer
   */
  function connectTo($mode) {
    $this->Lexer->addSpecialPattern('{{indexmenu>.+?}}',$mode,'plugin_indexmenu_indexmenu');
    $this->Lexer->addSpecialPattern('{{indexmenu>.+?}}',$mode,'plugin_indexmenu_indexmenu');
  }

  /**
   * Handle the match
   */
  function handle($match, $state, $pos, &$handler){

    return $this->parseOptions(substr($match,12,-2));

  }  
 
  /**
   *  Render output
   */
  function render($mode, &$renderer, $data) {
      switch ($mode) {
          case 'xhtml' :
              $n = $this->_indexmenu($data);
              if (!$n && ($n = $this->getConf('empty_msg'))) {
                  $exists = false;
                  resolve_pageid(getNS(getID()),$data[0],$exists);
                  $n = str_replace('{{ns}}'," ".$data[0]." ",$n);
              }
              $renderer->doc .= $n ;
              return true;
              break;
          case 'metadata' :
              /*
              *  used to purge the cache, if path to the current ID is used
              */
              if ("." == $data[0]) $renderer->meta['indexmenu'] = true;
              return true;
              break;
    }
    return false;
  }
 
  /**
   *  Parse plugin options and return array with them
   *
   *  @author Ilya Lebedev <ilya@lebedev.net>
   *  @param $opts string unparsed options list
   *  @return array options values
   *  @access public
   */
  function parseOptions ($opts) {
    $theme="DokuWiki";

    $level = 0;

    $nons = true;
    /*
    *  split namespace,level,theme
    *  array will have
    *   0 => namespace name and options
    *   1 => optional js settings
    */
    $options = explode('|', $opts, 2);

    /*
    *  split namespace
    *  array will have
    *   0 => namespace name
    *   1 => options
    *   2 => sort mode
    */
    $options[0] = explode("#", trim($options[0]));
    $ns = $options[0][0];
    /*
    *  split namespace options
    *  i.e. 1+nons => array(1,'nons')
    */
    @$options[0][1] = explode("+",$options[0][1]);
    /*
    *  split sort options
    *  i.e. sort+type+rev => array('sort','nons','rev')
    */
    @$options[0][2] = explode("+",$options[0][2]);

    /*
    *  does not matter, if level is not defined
    */
    $level = intval(@$options[0][1][0]);
    $nons = in_array('nons',$options[0][1]);

    /*
    *  now, parse the JS options
    */
    if (!isset($options[1])) {
      $js = false;
      $ajax = false;
      $theme = '';
    } else {
      $js = true;
      /*
      *  split js part
      *  array will have
      *   0 => 'js'
      *   1 => options
      */
      $options[1] = explode("#", $options[1]);
      /*
      *  split js options
      *  i.e. IndexMenu+ajax => array('IndexMenu','nons')
      */
      $options[1][1] = explode('+',$options[1][1]);
      $ajax = in_array('ajax',$options[1][1]);
      /*
      *  change the default theme name only if it really exists
      */
      if (@file_exists(INDEXMENU_FS_IMAGES.$theme."/".$options[1][1][0]."/design.css")) {
        $theme .= "/".$options[1][1][0];
      }
    }
    return array($ns,array( 'level' => $level
                           ,'theme' => $theme
                           ,'nons'  => $nons
                           ,'ajax'  => $ajax
                           ,'js'    => $js
                           ,'sort'  => $options[0][2][1]
                          )
                );
  }
  /**
   *  Return the index 
   *
   *  @author Ilya Lebedev <ilya@lebedev.net>
   */
  function _indexmenu($myns) {
    global $conf;
    global $ID;
 
    $ns = $myns[0];  
    $opts = $myns[1];

    $exists = false;
    $id = resolve_pageid(getNS(getID()),$ns,$exists);
    if ($ns == $conf['start']) $ns = "";
    /*
    *  if 'nons' is set or NS is root, no need to adjust settings
    */
    if (($opts['js'] || $opts['navigation']) && !$opts['nons'] && $ns) {
      $opts['root'] = $ns;
      $ns = (string)getNS($ns);
      /*
      *  we've moved index root 1 level lower, adjust max level than
      */
      if ($opts['level']) $opts['level']++;
    }
    $data = array();
    search($data,$conf['datadir'],"indexmenu_search_index",$opts,"/".utf8_encodeFN(str_replace(':','/',$ns)));
    if ($opts['sort']) {
      $this->s_rev = substr($opts['sort'],0,1)=='!';
      $this->s_target = $this->s_target[preg_replace("#^!#","",$opts['sort'])];

      if ($this->s_target) {
        usort($data,array($this,sortCallback));
      }
    }
    /*
    *  prepare array to convert into nested one
    */
    foreach ($data as $k => $v) {
      $data[$k]['parent_id'] = (string)getNS($v['id']);
    }
    /*
    *  convert array, w/o skipping NSs
    */
    if (!$opts['nons'])
      $data = array2tree($data,$ns);

    /*
    *  indicate empty tree
    */
    if (empty($data)) return false;
    /*
    *  if user want to draw complete wiki index, we need to make one more fake level
    */
    if ($opts['js'] && !$opts['nons'] && !isset($opts['root']) && !$ns) {
      $data = array(array('level'  =>0,
                          'child_nodes' => $data,
                          'type'   => 'd',
                          'open'   => 'true',
                          'id'     => $conf['start'],
                          'target' => $conf['start'],
                          'title' => ($conf['useheading'] && ($title=p_get_first_heading($conf['start'])))?$title:"",
                   ));

    }
    /*
    *  get the list tree
    */
    return syntax_plugin_indexmenu_indexmenu::getHTML ($opts, $opts['navigation']?$this->html_buildlist($data,$opts)
                                                                                 :"<ul>".$this->html_buildlist($data,$opts)."</ul>");
  }
  /**
   *  returns complete HTML for the menu
   *
   *  @param $opts mixed array of the options
   *  @param $html string html layout to be wrapped with javascript code, if needed
   *  @return string
   *  @access public
   */

  function getHTML ($opts, $html) {
      /*
      *  make unique id for current menu
      */
      $idx = 'indexmenu'.str_replace(".","",join(explode(" ",microtime())));
      $add = $opts['js']?"id=\"$idx\"":"class=\"idx\"";
      $html = preg_replace("#<ul[^>]*>#i", "<ul $add>", $html, 1);
    
      /*
      *  if 'nons' is set, then there's no need in JS
      */
      if ($opts['js'] && !$opts['nons']) {
          /*
          *  create ajax callback, if needed
          */
          if ($opts['ajax']) {
              $ajax = <<<EOL
                        ,modifiers : ['ajaxum']
                        ,ajaxum : {
                              fetcher : function (s, callback) {
                                  if ('undefined' == typeof RemoteScript) {
                                      callback ({'state' : false,
                                                 'response' : 'Plugin <a href="http://wiki.splitbrain.org/plugin:remotescript" _target="blank">RemoteScript</a> is not available'});
                                      return;
                                  }
                                  
                                  RemoteScript.query( ['indexmenu','getsubmenu']
                                                     ,{ 'src'  : s
                                                       ,'sort' : '{$opts['sort']}'}
                                                     ,function(js, txt) {
                                                          callback({'state' : !!js,
                                                                    'response' : js||txt});
                                                      }
                                                     ,true);
                              }
                          }
EOL;
          } else {
              $ajax = '';
          }
          $themeRoot = DOKU_BASE.'lib/plugins/indexmenu/templates/';
          $html .= <<<EOL
              <script type="text/javascript"><!--//--><![CDATA[//><!--
                 var cms = new CompleteMenuSolution()
                 cms.initMenu('$idx',{ 'theme': {'name': '{$opts["theme"]}'}
                                      ,'themeRootPath' : '$themeRoot'
                                      ,closeSiblings : false
                                       $ajax
                                     });
              //--><!]]></script>
EOL;
      }
      return $html;
  }
  /**
  * Build an unordered list
  *
  * Build an unordered list from the given $data array
  * Each item in the array has to have a 'level' property
  * the item itself gets printed by the given $func user
  * function. The second and optional function is used to
  * print the <li> tag. Both user function need to accept
  * a single item.
  *
  * Both user functions can be given as array to point to
  * a member of an object.
  *
  * @author Andreas Gohr <andi@splitbrain.org>
  * @author Ilya Lebedev <ilya@lebedev.net>
  */
  function html_buildlist(&$data,&$opts){
      $ret   = array();
  
      foreach ($data as $item) {
          $ret[] = "<li".(($item['type']=='d')?(" class=\"".($item['open']?'open':'closed')."\" "):'').">";
          $ret[] = preg_replace("#^<span[^>]+>(.+)</span>$#i","$1",html_wikilink(":".$item['target'],null));
          /*
          *  append child nodes, if exists
          */
          if ($item['type']=='d') { //isset($item['child_nodes'])) {
              if ($opts['level'] != 0 && ($opts['level'] <= $item['level'])) {
                  /*
                  *  for closed nodes add mark for Ajaxum plugin
                  */
                  if ($opts['ajax'])
                      $ret[] = "<ul ".(!$opts['js']&&!$opts['navigation']?"style=\"display: none\""
                                                                         :"")
                                     ." title=\"{$item['id']}\"><!-- {$item['id']} --></ul>";
              } else {
                  /*
                  *  open nodes process as usual
                  */
                  if (isset($item['child_nodes'])) {
                      $ret[] = "<ul>";
                      /*
                      *  static method used to be able to make menu w/o make class object
                      */
                      $ret[] = syntax_plugin_indexmenu_indexmenu::html_buildlist($item['child_nodes'],$opts);
                      $ret[] = "</ul>";
                  }
              }
          }
          $ret[] = "</li>";
      }
      return join("\n",$ret);
  }
  /**
   *  Sorting callback function
   *
   *  @param array $a first matching
   *  @param array $b second matching
   *  @return int -1,0,1 sorting result
   *  @access protected
   */
  function sortCallback ($a, $b) {
    $t1 = $this->s_rev?$b[$this->s_target]:$a[$this->s_target];
    $t2 = $this->s_rev?$a[$this->s_target]:$b[$this->s_target];
    if ($t1>$t2) return 1;
    if ($t1<$t2) return -1;
    return 0;
  }
} //Indexmenu class end  
  /**
  * Build the browsable index of pages
  *
  * $opts['ns'] is the current namespace
  *
  * @author  Andreas Gohr <andi@splitbrain.org>
  * @author  Ilya Lebedev <ilya@lebedev.net>
  */
  function indexmenu_search_index(&$data,$base,$file,$type,$lvl,$opts){
    global $conf;
    $ret = true;
    
    $item = array();
    if($type == 'd'){
      if ($opts['level']!=0 && $lvl >= $opts['level']) $ret=false;
      if ($opts['nons']) return $ret;
    } elseif($type == 'f' && !preg_match('#\.txt$#',$file)) {
      //don't add
      return false;
    }
    
    /*
    *  get page id by filename
    */
    $id = pathID($file);

    /*
    *  index only 'root' namespace, if requested
    */
    if ($lvl == 1 && isset($opts['root']) && $id != $opts['root']) return false;
    
    /*
    * check for files/folders to skip
    */
    if (syntax_plugin_indexmenu_indexmenu::getConf('skip_index') && preg_match(syntax_plugin_indexmenu_indexmenu::getConf('skip_index'), $file))
      return false;
    
    //check hiddens
    if($type=='f' && isHiddenPage($id)){
      return false;
    }
    
    //check ACL (for namespaces too)
    if(auth_quickaclcheck($id) < AUTH_READ){
      return false;
    }
    
    //check if it's a headpage (acrobatic check)
    if(!$opts['nons'] && $type=='f' && $conf['useheading'] && syntax_plugin_indexmenu_indexmenu::getConf('hide_headpage')) {
      if (noNS(getNS($id))==noNS($id) ||                       // /<ns>/<ns>.txt
          $id==$conf['start'] ||                               // <ns> == <start_page>
          $id==getNS($id).":".$conf['start'] ||                // /<ns>/<start_page>.txt
          @file_exists(dirname(wikiFN($id.":".noNS($id))))     // /<ns>/
                                                               // /<ns>.txt
         ){
        return false;
      }
    }
    /*
    *  bugfix for the
    *  /ns/
    *  /<ns>.txt
    *  case, need to force the 'directory' type
    */
    if ($type == 'f' && file_exists(dirname(wikiFN($id.":".noNS($id))))) $type = 'd';
    

    /*
    *  page target id = global id
    */
    $target = $id;
    if ($type == 'd') {
      /*
      *  this will check 3 kinds of headpage:
      *  1. /<ns>/<ns>.txt
      *  2. /<ns>/
      *     /<ns>.txt
      *  3. /<ns>/   
      *     /<ns>/<start_page>
      */
      $nsa = array( $id.":".noNS($id),
                    $id,
                    $id.":".$conf['start']
                  );
      $nspage = false;
      foreach ($nsa as $nsp) {
        if (@file_exists(wikiFN($nsp)) && auth_quickaclcheck($nsp) >= AUTH_READ) {
          $nspage = $nsp;
          break;
        }
      }
      //headpage exists
      if ($nspage) {
        $target = $nspage;
      } else {
        /*
        *  open namespace index, if headpage does not exists
        */
        $target = $target.':';
      }
    }
    
    //Set all pages at first level
    if ($opts['nons']) {
      $lvl=1;    
    }
    
    $data[]=array( 'id'     => $id
                  ,'date'   => @filectime(wikiFN($target))
                  ,'type'   => $type
                  ,'target' => $target  // id to be used in the menu
                  ,'title'  => ($conf['useheading'] && ($title=p_get_first_heading($target)))?$title:$id // NS title
                  ,'level'  => $lvl
                  ,'open'   => $ret );

    return $ret|$opts['ajax'];

  }  



/**
 * Converts an associative array into tree
 * @author Anton Makarenko php[at]ripfolio[dot]com
 * @copyright GPL
 *
 * @param array $source_arr
 * @param mixed $parent_id
 * @param string $key_children
 * @param string $key_id
 * @param string $key_parent_id
 * @return array $tree
 *
 * @example :
 * $source = array(
 *         array('id'=>1, 'parent_id'=>0, 'foo'=>'bar'),
 *         array('id'=>2, 'parent_id'=>1, 'foo'=>'barr'),
 *         array('id'=>3, 'parent_id'=>1, 'foo'=>'barrr')
 *         );
 * $tree = array2tree($source, 0);
 */
function array2tree($source_arr, $parent_id, $key_children='child_nodes', $key_id='id', $key_parent_id='parent_id')
{
        $tree=array();
        if (empty($source_arr))
                return $tree;
        _array2treer($source_arr, $tree, $parent_id, $parent_id, $key_children, $key_id, $key_parent_id);
        return $tree;
}
/**
 * A private function. Background for array2tree. It is unnecessarily to use this function directly
 * @author Anton Makarenko php[at]ripfolio[dot]com
 * @copyright GPL
 *
 * @param array $source_arr
 * @param array &$_this
 * @param mixed $parent_id
 * @param mixed $_this_id
 * @param string $key_children
 * @param string $key_id
 * @param string $key_parent_id
 * @return null
 */
function _array2treer($source_arr, &$_this, $parent_id, $_this_id, $key_children, $key_id, $key_parent_id)
{
        // populate current children
        foreach ($source_arr as $value)
                if ($value[$key_parent_id]===$_this_id)
                        $_this[$key_children][$value[$key_id]]=$value;
        if (isset($_this[$key_children]))
        {
                // populate children of the current children
                foreach ($_this[$key_children] as $value)
                        _array2treer($source_arr, $_this[$key_children][$value[$key_id]], $parent_id, $value[$key_id], $key_children, $key_id, $key_parent_id);
                // make the tree root look pretty (more convenient to use such tree)
                if ($_this_id===$parent_id)
                        $_this=$_this[$key_children];
        }
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
