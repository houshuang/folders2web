<?php
  /**
   * bibtex-Plugin: Parses bibtex-blocks
   *
   * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
   * @author     Christophe Ambroise <ambroise@utc.fr>
   * @date       2005-08-10
   */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

function sortByField($a, $b)
{
  global $SortField;
  $f1 = $a[$SortField];
  $f2 = $b[$SortField];

  if ($f1 == $f2) return 0;

  return ($f1 < $f2) ? -1 : 1;
}

function rawOutput($entry)
{
    $res = '<h2>' . $entry['title'] . '</h2><h3>Bibtex entry :</h3>';

    $res .= "<pre>@" . $entry['bibtexEntryType'] . " { " . $entry['bibtexCitation'] . ",\n";
    foreach($entry as $key => $value)
        if($key != 'bibtexCitation' && $key != 'bibtexEntryType' && $key != 'file' && $key != 'lang'){
            $res .= "    " . $key . " = { " . $value . " },\n";
        }
    $res .= "}</pre>";
    return $res;
}

function parseBibFile($page)
{
    $bib_file = DOKU_INC . "data/pages/bib/" . $page . ".txt";

    $f = fopen($bib_file, "r");
    $bib_file_string = "";
    if ($f)
    {
        while (!feof($f))
        {
            $bib_file_string = $bib_file_string . fgets($f, 1024);
        }

        return $bib_file_string;
    }
    print "<font color=#FF0000>Can't open file</font><br>\n";
    return false;
}

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_bibtex_bibtex extends DokuWiki_Syntax_Plugin {

    var $options = array(
        'style' => 'APA',
        'file' => 'bib',
                     );

    function getInfo(){
        return array(
            'author' => 'Yann Hodique',
            'email'  => 'yann.hodique@gmail.com',
            'date'   => '2006-12-09',
            'name'   => 'BibTeX Plugin (bibtex component)',
            'desc'   => 'parses bibtex blocks',
            'url'    => 'http://hodique.info'
                     );
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){ return 'substition'; }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 358;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<BIBTEX: [ !=.\w+]*>',$mode,'plugin_bibtex_bibtex');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos) {
        $match = substr($match,9,-1);
        return array($match);
    }
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        // global $conf;

        foreach (explode(" ", $data[0]) as $opt) {
            list($key,$value) = explode("=",$opt);
            $this->options[$key] = $value;
        }
        if($mode == 'xhtml') {
           $renderer->doc .= $this->createCitations();
           return true;
        }
    }

    function createCitations() {
        global $conf;
        $pathToOsbib = DOKU_PLUGIN.'bibtex/OSBib/';
        include_once($pathToOsbib.'format/bibtexParse/PARSEENTRIES.php');
        include_once( $pathToOsbib.'format/BIBFORMAT.php');

        $data = parseBibFile($this->options['file']);

        /* Get the bibtex entries into an associative array */
        $parse = NEW PARSEENTRIES();
        $parse->expandMacro = TRUE;
        $parse->fieldExtract = TRUE;
        $parse->removeDelimit = TRUE;
        $parse->loadBibtexString($data);
        $parse->extractEntries();
        list($preamble, $strings, $entries) = $parse->returnArrays();


        $sort = $this->options['sort'];

        if ($sort[0] == '!') { $reverse = true; $sort = substr($sort, 1); }
        else $reverse = false;

        if ($sort != '')
        {
            $SortField = $this->options['sort'];
            usort($entries, "sortByField");

            if ($reverse) {
                $entries = array_reverse($entries);
            }
        }


        /* Format the entries array  for html output */
        $bibformat = NEW BIBFORMAT($pathToOsbib, TRUE);
        $bibformat->cleanEntry=TRUE; // The entries will be transformed into nice utf8
        list($info, $citation, $styleCommon, $styleTypes) = $bibformat->loadStyle(DOKU_PLUGIN.'bibtex/OSBib/styles/bibliography/', $this->options['style']);
        $bibformat->getStyle($styleCommon, $styleTypes);

        $citations='<dl>';
        foreach ($entries as $entry){
            // Get the resource type ('book', 'article', 'inbook' etc.)
            $resourceType = $entry['bibtexEntryType'];
            // In this case, BIBFORMAT::preProcess() adds all the resource elements automatically to the BIBFORMAT::item array...
            $bibformat->preProcess($resourceType, $entry);
            // Finally, get the formatted resource string ready for printing to the web browser or exporting to RTF, OpenOffice or plain text
            $citations.= '<dt><div class="bibtexdt">[' . $entry['year'] . ", " . $entry['bibtexEntryType'] . $this->toDownload($entry) . '] (<a href=/_bib/' . $this->options['file'] . '/' . trim($entry['bibtexCitation']) . '>BIB</a>)</div></dt><dd><div class="bibtexdd">'.   $bibformat->map()    . "</div></dd> \n" ;
        }
        $citations.= "</dl>";

        return $citations;

        //$entry['bibtexCitation']

    }



    function toDownload($entry) {
    	$string = '';
        if(array_key_exists('file',$entry)){
            $string= $string."| ".$this->internalmedia($entry['file']);
        }
        if(array_key_exists('url',$entry)){
            $string= $string."| ".$this->externallink($entry['url'],"url");
        }
        return $string;
    }


    function externallink($url, $name = NULL) {
        global $conf;

        $name = $this->_getLinkTitle($name, $url, $isImage);

        // add protocol on simple short URLs
        if(substr($url,0,3) == 'ftp' && (substr($url,0,6) != 'ftp://')) $url = 'ftp://'.$url;
        if(substr($url,0,3) == 'www') $url = 'http://'.$url;

        if ( !$isImage ) {
            $class='urlextern';
        } else {
            $class='media';
        }

        //prepare for formating
        $link['target'] = $conf['target']['extern'];
        $link['style']  = '';
        $link['pre']    = '';
        $link['suf']    = '';
        $link['more']   = 'onclick="return svchk()" onkeypress="return svchk()"';
        $link['class']  = $class;
        $link['url']    = $url;
        $link['name']   = $name;
        $link['title']  = $this->_xmlEntities($url);
        if($conf['relnofollow']) $link['more'] .= ' rel="nofollow"';

        //output formatted
        return $this->_formatLink($link);
    }


    function internalmedia ($src, $title=NULL, $align=NULL, $width=NULL,
                            $height=NULL, $cache=NULL, $linking=NULL) {
        global $conf;
        global $ID;
        resolve_mediaid(getNS($ID),$src, $exists);

        $link = array();
        $link['class']  = 'media';
        $link['style']  = '';
        $link['pre']    = '';
        $link['suf']    = '';
        $link['more']   = 'onclick="return svchk()" onkeypress="return svchk()"';
        $link['target'] = $conf['target']['media'];
        $link['title']  = $this->_xmlEntities($src);
        list($ext,$mime) = mimetype($src);
        if(substr($mime,0,5) == 'image'){
            // link only jpeg images
            // if ($ext != 'jpg' && $ext != 'jpeg') $noLink = TRUE;
            $link['url'] = ml($src,array('id'=>$ID,'cache'=>$cache),($linking=='direct'));
        }elseif($mime == 'application/x-shockwave-flash'){
            // don't link flash movies
            $noLink = TRUE;
        }else{
            // add file icons
            $link['class'] = 'urlextern';
            if(@file_exists(DOKU_INC.'lib/images/fileicons/'.$ext.'.png')){
                $link['style']='background-image: url('.DOKU_BASE.'lib/images/fileicons/'.$ext.'.png)';
            }elseif(@file_exists(DOKU_INC.'lib/images/fileicons/'.$ext.'.gif')){
                $link['style']='background-image: url('.DOKU_BASE.'lib/images/fileicons/'.$ext.'.gif)';
            }else{
                $link['style']='background-image: url('.DOKU_BASE.'lib/images/fileicons/file.gif)';
            }
            $link['url'] = ml($src,array('id'=>$ID,'cache'=>$cache),true);
        }
        $link['name']   = $this->_media ($src, $title, $align, $width, $height, $cache);

        //output formatted
        if ($linking == 'nolink' || $noLink){
            return  $link['name'];
        } else {
            return $this->_formatLink($link);
        }
    }


    function _formatLink($link){
        //make sure the url is XHTML compliant (skip mailto)
        if(substr($link['url'],0,7) != 'mailto:'){
            $link['url'] = str_replace('&','&amp;',$link['url']);
            $link['url'] = str_replace('&amp;amp;','&amp;',$link['url']);
        }
        //remove double encodings in titles
        $link['title'] = str_replace('&amp;amp;','&amp;',$link['title']);

        $ret  = '';
        $ret .= $link['pre'];
        $ret .= '<a href="'.$link['url'].'"';
        if($link['class'])  $ret .= ' class="'.$link['class'].'"';
        if($link['target']) $ret .= ' target="'.$link['target'].'"';
        if($link['title'])  $ret .= ' title="'.$link['title'].'"';
        if($link['style'])  $ret .= ' style="'.$link['style'].'"';
        if($link['more'])   $ret .= ' '.$link['more'];
        $ret .= '>';
        $ret .= $link['name'];
        $ret .= '</a>';
        $ret .= $link['suf'];
        return $ret;
    }

    /**
     * Construct a title and handle images in titles
     *
     * @author Harry Fuecks <hfuecks@gmail.com>
     */
    function _getLinkTitle($title, $default, & $isImage, $id=NULL) {
        global $conf;

        $isImage = FALSE;
        if ( is_null($title) ) {
            if ($conf['useheading'] && $id) {
                $heading = p_get_first_heading($id);
                if ($heading) {
                    return $this->_xmlEntities($heading);
                }
            }
            return $this->_xmlEntities($default);
        } else if ( is_string($title) ) {
            return $this->_xmlEntities($title);
        } else if ( is_array($title) ) {
            $isImage = TRUE;
            return $this->_imageTitle($title);
        }
    }

    function _xmlEntities($string) {
        return htmlspecialchars($string);
    }

    function _simpleTitle($name){
        global $conf;

        if($conf['useslash']){
            $nssep = '[:;/]';
        }else{
            $nssep = '[:;]';
        }
        $name = preg_replace('!.*'.$nssep.'!','',$name);
        //if there is a hash we use the ancor name only
        $name = preg_replace('!.*#!','',$name);
        return $name;
    }

    /**
     * Renders internal and external media
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function _media ($src, $title=NULL, $align=NULL, $width=NULL,
                     $height=NULL, $cache=NULL) {

        $ret = '';

        list($ext,$mime) = mimetype($src);
        if(substr($mime,0,5) == 'image'){
            //add image tag
            $ret .= '<img src="'.ml($src,array('w'=>$width,'h'=>$height,'cache'=>$cache)).'"';
            $ret .= ' class="media'.$align.'"';

            if (!is_null($title)) {
                $ret .= ' title="'.$this->_xmlEntities($title).'"';
                $ret .= ' alt="'.$this->_xmlEntities($title).'"';
            }elseif($ext == 'jpg' || $ext == 'jpeg'){
                //try to use the caption from IPTC/EXIF
                require_once(DOKU_INC.'inc/JpegMeta.php');
                $jpeg =& new JpegMeta(mediaFN($src));
                if($jpeg !== false) $cap = $jpeg->getTitle();
                if($cap){
                    $ret .= ' title="'.$this->_xmlEntities($cap).'"';
                    $ret .= ' alt="'.$this->_xmlEntities($cap).'"';
                }
            }else{
                $ret .= ' alt=""';
            }

            if ( !is_null($width) )
                $ret .= ' width="'.$this->_xmlEntities($width).'"';

            if ( !is_null($height) )
                $ret .= ' height="'.$this->_xmlEntities($height).'"';

            $ret .= ' />';

        }elseif($mime == 'application/x-shockwave-flash'){
            $ret .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'.
                ' codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0"';
            if ( !is_null($width) ) $ret .= ' width="'.$this->_xmlEntities($width).'"';
            if ( !is_null($height) ) $ret .= ' height="'.$this->_xmlEntities($height).'"';
            $ret .= '>'.DOKU_LF;
            $ret .= '<param name="movie" value="'.ml($src).'" />'.DOKU_LF;
            $ret .= '<param name="quality" value="high" />'.DOKU_LF;
            $ret .= '<embed src="'.ml($src).'"'.
                ' quality="high"';
            if ( !is_null($width) ) $ret .= ' width="'.$this->_xmlEntities($width).'"';
            if ( !is_null($height) ) $ret .= ' height="'.$this->_xmlEntities($height).'"';
            $ret .= ' type="application/x-shockwave-flash"'.
                ' pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>'.DOKU_LF;
            $ret .= '</object>'.DOKU_LF;

        }elseif(!is_null($title)){
            // well at least we have a title to display
            $ret .= $this->_xmlEntities($title);
        }else{
            // just show the sourcename
            $ret .= $this->_xmlEntities(noNS($src));
        }

        return $ret;
    }
}
?>
