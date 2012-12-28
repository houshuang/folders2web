<?php
  /**
  * Plugin ifauth: Displays content at given time. (After next cache update)
  *
  * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
  * @author     Otto Vainio <oiv-ifauth@valjakko.net>
  */

  if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
  if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
  require_once(DOKU_PLUGIN.'syntax.php');

  /**
  * All DokuWiki plugins to extend the parser/rendering mechanism
  * need to inherit from this class
  */
  class syntax_plugin_ifauth extends DokuWiki_Syntax_Plugin {

    /**
    * return some info
    */
    function getInfo(){
      return array(
        'author' => 'Otto Vainio',
        'email'  => 'oiv-ifauth@valjakko.net',
        'date'   => '2005-09-23',
        'name'   => 'ifauth plugin',
        'desc'   => 'Show content at this time',
        'url'    => 'http://wiki.splitbrain.org/wiki:plugins',
      );
    }

    /**
    * What kind of syntax are we?
    */
    function getType(){
      return 'substition';
    }
   /**
    * Paragraph Type
    *
    * Defines how this syntax is handled regarding paragraphs. This is important
    * for correct XHTML nesting. Should return one of the following:
    *
    * 'normal' - The plugin can be used inside paragraphs
    * 'block'  - Open paragraphs need to be closed before plugin output
    * 'stack'  - Special case. Plugin wraps other paragraphs.
    *
    * @see Doku_Handler_Block
    */
    function getPType() {
      return 'normal';
    }

    function getSort(){
      return 360;
    }
    function connectTo($mode) {
      $this->Lexer->addEntryPattern('<ifauth.*?>(?=.*?\x3C/ifauth\x3E)',$mode,'plugin_ifauth');
    }
    function postConnect() {
      $this->Lexer->addExitPattern('</ifauth>','plugin_ifauth');
    }


    /**
    * Handle the match
    */
    function handle($match, $state, $pos, &$handler){
      switch ($state) {
        case DOKU_LEXER_ENTER :

// remove <ifauth and >
          $auth  = trim(substr($match, 8, -1));

// explode wanted auths
          $aauth = explode(",",$auth);
          return array($state, $aauth);
        case DOKU_LEXER_UNMATCHED :  return array($state, $match);
        case DOKU_LEXER_EXIT :       return array($state, '');
      }
      return array();
    }

    /**
    * Create output
    */
    function render($mode, &$renderer, $data) {

// ifauth stoes wanted user/group array
      global $ifauth;

// grps hold curren user groups and userid
      global $grps;
      global $INFO;
      if($mode == 'xhtml'){
        list($state, $match) = $data;
        switch ($state) {
        case DOKU_LEXER_ENTER :

// Store wanted groups/userid
          $ifauth=$match;

// Store current user info. Add '@' to the group names
          $grps=array();
          if (is_array($INFO['userinfo'])) {
            foreach($INFO['userinfo']['grps'] as $val) {
              $grps[]="@" . $val;
            }
          }
          $grps[]=$_SERVER['REMOTE_USER'];
          break;
        case DOKU_LEXER_UNMATCHED :
          $rend=0;

// Loop through each wanted user / group
          foreach($ifauth as $val) {
            $not=0;

// Check negation
            if (substr($val,0,1)=="!") {
              $not=1;
              $val=substr($val,1);
            }
// FIXME More complicated rules may be wanted. Currently any rule that matches for render overrides others.

// If current user/group found in wanted groups/userid, then render.
            if ($not==0 && in_array($val,$grps)) {
              $rend=1;
            }

// If user set as not wanted (!) or not found from current user/group then render.
            if ($not==1 && !in_array($val,$grps)) {
              $rend=1;
            }
          }
          if ($rend>0) {
            $r = p_render('xhtml',p_get_instructions($match),$info);
// Remove '\n<b>\n' from start and '\n</b>\n' from the end.
            if (stristr(substr($r,0,5),"\n<p>\n")) {
              $r = substr($r,5);
            }
            if (stristr(substr($r,-7)," \n</p>\n")) {
              $r = substr($r,0,-7);
            }
            $renderer->doc .= $r;
          }
          $renderer->nocache();
          break;
        case DOKU_LEXER_EXIT :
          break;
        }
        return true;
      }
      return false;
    }
  }
?>