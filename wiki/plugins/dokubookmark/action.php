<?php
/**
 * dwlog.php
 * Dokuwiki website tagger - act like a weblog
 *
 * Version 0.5.0
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Robin Gareus <robin@gareus.org>
 * @based_on   http://wiki.splitbrain.org/wiki:tips:weblog_bookmarklet by riny [at] bk [dot] ru
 *
 * see http://mir.dnsalias.com/wiki/dokubookmark
 *
 * USAGE : 
 *  Create bookmark in your browser using bookmarklet part shown below,
 *  Change the window.open statement to reflect the location of your dokuwiki script.
 *
 * BOOKMARKLET : 
 *  javascript:Q=document.selection?document.selection.createRange().text:document.getSelection(); void(window.open('http://your.host/doku.php?do=dokubookmark&te='+encodeURIComponent(Q)+'&ur='+ encodeURIComponent(location.href)+'&ti='+encodeURIComponent(document.title),'dokuwikiadd','scrollbars=yes,resizable=yes,toolbars=yes,width=200,height=100,left=200,top=200,status=yes'));
 *
 */ 
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');
  
/**
 * callback function for dokuwiki search()
 *
 * Build a list of tags from the tag namespace
 * $opts['ns'] is the namespace to browse
 */
function search_tagpages(&$data,$base,$file,$type,$lvl,$opts){
  $return = true;
  $item = array();
  if($type == 'd') {
    // TODO: check if namespace mismatch -> break recursion early.
    return true;
  }elseif($type == 'f' && !preg_match('#\.txt$#',$file)){
    return false;
  }

  $id = pathID($file);
  // TODO: use a regexp ?!
  if (getNS($id) != $opts['ns']) return false;

  //check hidden
  if(isHiddenPage($id)){
    return false;
  }

  //check ACL
  if($type=='f' && auth_quickaclcheck($id) < AUTH_READ){
    return false;
  }

  $data[]=noNS($id);
  return $return;
}

/**
 *
 */
class action_plugin_dokubookmark extends DokuWiki_Action_Plugin {
 
  /**
   * return some info
   */
  function getInfo(){
    return array(
     'author' => 'Robin Gareus',
     'email'  => 'robin@gareus.org',
     'date'   => '2010-05-22',
     'name'   => 'Dokubookmark',
     'desc'   => 'manage your bookmarks with this dokuwiki website tagger.',
     'url'    => 'http://mir.dnsalias.com/wiki/dokubookmark',
     );
  }
 
  /**
   * Register its handlers with the dokuwiki's event controller
   */
  function register(&$controller) {
    $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE',  $this, '_hookdo');
  }

  /**
   * 
   */
  function _hookdo(&$event, $param) {
    if ($this->getConf('enable_save') && $event->data['dokubookmark'] == "Save" ) {
        $this->_save_bookmark($event);
    } else if ($event->data['dokubookmark'] == "Save") {
      global $ACT;
      $ACT="show";
      msg('Direct saving of weblog entries has been disabled. Use your browser back-button and retry.',-1);
    } else if ($event->data == 'dokubookmark') {
        $this->_bookmarkpage(); # this function will call exit();
    }
  }

  /**
   * for use with old tag plugin.
   */
  function _gettags() {
    require_once(DOKU_INC.'inc/search.php');
    global $conf;
    $data = array();
    $tagns = $conf['plugin']['tag']['namespace'];
    if (empty($tagns)) {
      $tagns = 'wiki:tags'; 
    }
    search($data,$conf['datadir'],'search_tagpages',array('ns' => $tagns));
    return($data); 
  }

  /** 
   * drop-in replacement for _gettags() to work with newer tag-plugin
   * requires testing
   */
  function _gettags2() {
    $thlp = plugin_load('helper', 'tag');
    $data = array();
    if (!is_array($thlp->topic_idx)) return $data;
    foreach ($thlp->topic_idx as $k => $v) {
        if (!is_array($v) || empty($v) || (!trim($v[0]))) continue;
        $data[$k] = count($v);
    }
    arsort($data);
    return(array_keys($data)); 
  }


  /**
   * 
   */
  function _bookmarkpage() {
    global $conf;
    require_once(DOKU_PLUGIN.'dokubookmark/helper.php');

    // Parse and prepare variables.
    $selection = rawurldecode($_GET['te']);  // selected text
    $url       = rawurldecode($_GET['ur']);  // URL
    $title     = rawurldecode($_GET['ti']);  // page title
    $timestamp = date($this->getConf('dateformat')); 

    #$wikitpl = "====== @T@ ======\n[[@U@]]\n----\n@S@\n\n{{tag>Bookmark}}"; 
    #$wikitpl = "====== @T@ ======\n~~META:url=@U@~~\n@S@\n\n{{tag>Bookmark}}"; 
    $wikitpl  = str_replace('\n',"\n", $this->getConf('wikitemplate'));

    $foo = $_SERVER['REMOTE_USER'];
    if (!isset($foo) || empty($foo)) {
      $foo= 'anonymous';
    }

    $data=array(
      'baseurl'   => $conf['baseurl'].DOKU_BASE.'doku.php',
      'wikiidtpl' => $this->getConf('namespace'),
      'wikitpl'   => $wikitpl,
      'timestamp' => $timestamp,
      'title'     => $title,
      'url'       => $url,
      'foo'       => $foo,
      'selection' => $selection);

    $dwtpl=pageTemplate(array(parseWikiIdTemplate($data['wikiidtpl'], $data)));
    if ($dwtpl) {
      $data['wikitpl'] = $dwtpl;
    }

    # parse Preset configuration
    $cfg_presets=array();
    foreach (explode(';', trim($this->getConf('presets'))) as $p) {
      $l=explode('=',$p,2);
      $d=explode('|',$l[1],2);
      if (empty($d[0]) || empty($l[0])) continue;

      $tpl='';
      if (!empty($d[1])) {
	# TODO: optionally specify template-file instead of 'namespace:_template.txt'
        #$file = wikiFN($d[1]);
        #if (@file_exists($file)){
        #   $tpl = io_readFile($file);
        # TODO: replace Placeholders alike ../../../inc/common.php pageTemplate() ?!
        #} else {
          $tpl = pageTemplate(array($d[1])); 
        #}
      }

      # allow ID-only presets, if template ns == '-' 
      if (empty($tpl) && $d[1] != '-') {
        $tpl = str_replace('\n',"\n", $this->getConf('wikitemplate'));
      }

      $n=parseWikiIdTemplate($d[0], $data);
      $file = wikiFN($n);

		# TODO: check if we'd be allowed to create/edit this page
		# else save will fail later :( - or hide this preset,
		# or push session on stack and opt to log-on.

      # check if a page with this preset's ID already exists
      if (@file_exists($file)){
        msg('preset \''.$l[0].'\' - a page with <a href="'.wl($n).'">this ID</a> already exists.', -1);
      } else {
        $cfg_presets[$l[0]]=array('id' => $d[0], 'tpl' => $tpl); 
      }

    } # done.  now $cfg_presets holds an array of presets;

    $options   = array(
      'tagbox'      => $this->getConf('tagbox'), 
      'tagboxtable' => $this->getConf('tagboxtable'), 
      'enable_save' => $this->getConf('enable_save'),
      'preset'      => count($cfg_presets)>0, 
      'presets'     => $cfg_presets
    );

    # output the page and form

    printHeader();
    if(function_exists('html_msgarea')){
      html_msgarea();
    }
    printForm($data, $options, $this->_gettags());
    printFooter();
    exit;
  } 

  /**
   *
   * - used only if 'enabled_save' is configured.
   */
  function _save_bookmark(&$event) {
    global $conf;
    global $ACT;
    global $ID;

    // we can handle it -> prevent others
    $event->stopPropagation();
    $event->preventDefault();
    
    // check if we are allowed to create this file
    if (auth_quickaclcheck($ID) < AUTH_CREATE){
      $ACT = 'show';
      msg('You may not create bookmarks in this namespace - go back with your browser\'s back-button and change the page ID.',-1);
      return;
    }

    $file = wikiFN($ID);
    
    //check if locked by anyone - if not lock for my self      
    if (checklock($ID)){
      $ACT = 'locked';
      #return;  ??
    } else {
      lock($ID);
    }

    if (@file_exists($file)){
      $ACT = 'edit';
      msg('Page did already exist. entered edit mode. feel free to go back with your browser\'s back-button and change the page ID.',-1);
      return;
    }
  
    global $TEXT;
    global $INFO;
    global $conf;
    
    $TEXT = $_POST['wikitext'];
    if (!$TEXT) {
      $ACT = 'show';
      msg('empty wiki page text. page has not been created.',-1);
      return;
    }
    #if (!$TEXT) 
    #  $TEXT = pageTemplate($ID);# FIXME: evaluate $this->conf('namespace') ?!
    #if (!$TEXT) $TEXT = "====== $title ======\n\n\n\n".
    #                    "{{tag>Bookmark}}\n"; # TODO wrap $_GET['ur']; ?!

    if(checkSecurityToken()){
      $ACT = act_save($ACT);
    } else {
      $ACT = 'show';
      msg('Security Token did not match. Possible CSRF attack.',-1);
    }
  } 
}
//Setup VIM: ex: et ts=2 enc=utf-8 :
