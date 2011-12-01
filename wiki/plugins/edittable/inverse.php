<?php
/**
 * Renderer for WikiText output
 *
 * @author Adrian Lang <lang@cosmocode.de>
 */

require_once DOKU_INC . 'inc/parser/renderer.php';
require_once DOKU_INC . 'inc/html.php';
require_once DOKU_PLUGIN . 'edittable/common.php';

/**
 * The Renderer
 */
class Doku_Renderer_wiki extends Doku_Renderer {

    // @access public
    var $doc = '';        // will contain the whole document

    private $quotelvl = 0;

    function getFormat(){
        return 'wiki';
    }

    function document_start() {
        //reset some internals
    }

    function document_end() {
        $this->block();
        $this->doc = rtrim($this->doc);
    }

    function header($text, $level, $pos) {
        $this->block();
        if(!$text) return; //skip empty headlines

        // write the header
        $markup = str_repeat('=', 7 - $level);
        $this->doc .= "$markup $text $markup" . DOKU_LF;
    }

    function section_open($level) {
        $this->block();
#        $this->doc .= DOKU_LF;
    }

    function section_close() {
        $this->block();
        $this->doc .= DOKU_LF;
    }

    function cdata($text) {
        if (strlen($text) === 0) {
            $this->not_block();
            return;
        }

        if (!$this->previous_block && trim(substr($text, 0, 1)) === '' && trim($text) !== '') {
            $this->doc .= ' ';
        }
        $this->not_block();
        if (trim(substr($text, -1, 1)) === '' && trim($text) !== '') {
            $this->prepend_not_block = ' ';
        }
        $this->doc .= trim($text);
    }

    function p_close() {
        $this->block();
        if ($this->quotelvl === 0) {
            $this->doc = rtrim($this->doc, DOKU_LF) . DOKU_LF . DOKU_LF;
        }
    }

    function p_open() {
        $this->block();
        if (strlen($this->doc) > 0 && substr($this->doc, 1, -1) !== DOKU_LF) {
            $this->doc .= DOKU_LF . DOKU_LF;
        }
        $this->doc .= str_repeat('>', $this->quotelvl);
    }

    function linebreak() {
        $this->not_block();
        $this->doc .= '\\\\ ';
    }

    function hr() {
        $this->block();
        $this->doc .= '----';
    }

    function block() {
        if (isset($this->prepend_not_block)) {
            unset($this->prepend_not_block);
        }
        $this->previous_block = true;
    }

    function not_block() {
        if (isset($this->prepend_not_block)) {
            $this->doc .= $this->prepend_not_block;
            unset($this->prepend_not_block);
        }
        $this->previous_block = false;
    }

    function strong_open() {
        $this->not_block();
        $this->doc .= '**';
    }

    function strong_close() {
        $this->not_block();
        $this->doc .= '**';
    }

    function emphasis_open() {
        $this->not_block();
        $this->doc .= '//';
    }

    function emphasis_close() {
        $this->not_block();
        $this->doc .= '//';
    }

    function underline_open() {
        $this->not_block();
        $this->doc .= '__';
    }

    function underline_close() {
        $this->not_block();
        $this->doc .= '__';
    }

    function monospace_open() {
        $this->not_block();
        $this->doc .= "''";
    }

    function monospace_close() {
        $this->not_block();
        $this->doc .= "''";
    }

    function subscript_open() {
        $this->not_block();
        $this->doc .= '<sub>';
    }

    function subscript_close() {
        $this->not_block();
        $this->doc .= '</sub>';
    }

    function superscript_open() {
        $this->not_block();
        $this->doc .= '<sup>';
    }

    function superscript_close() {
        $this->not_block();
        $this->doc .= '</sup>';
    }

    function deleted_open() {
        $this->not_block();
        $this->doc .= '<del>';
    }

    function deleted_close() {
        $this->not_block();
        $this->doc .= '</del>';
    }

    function footnote_open() {
        $this->not_block();
        $this->doc .= '((';
    }

    function footnote_close() {
        $this->not_block();
        $this->doc .= '))';
    }

    function listu_open() {
        $this->block();
        if (!isset($this->_liststack)) {
            $this->_liststack = array();
        }
        if (count($this->_liststack) === 0) {
            $this->doc .= DOKU_LF;
        }
        $this->_liststack[] = '*';
    }

    function listu_close() {
        $this->block();
        array_pop($this->_liststack);
        if (count($this->_liststack) === 0) {
            $this->doc .= DOKU_LF;
        }
    }

    function listo_open() {
        $this->block();
        if (!isset($this->_liststack)) {
            $this->_liststack = array();
        }
        if (count($this->_liststack) === 0) {
            $this->doc .= DOKU_LF;
        }
        $this->_liststack[] = '-';
    }

    function listo_close() {
        $this->block();
        array_pop($this->_liststack);
        if (count($this->_liststack) === 0) {
            $this->doc .= DOKU_LF;
        }
    }

    function listitem_open($level) {
        $this->block();
        $this->doc .= str_repeat(' ', $level * 2) . end($this->_liststack) . ' ';
    }

    function listcontent_close() {
        $this->block();
        $this->doc .= DOKU_LF;
    }

    function unformatted($text) {
        $this->not_block();
        if (strpos($text, '%%') !== false) {
            $this->doc .= "<nowiki>$text</nowiki>";
        } else {
            $this->doc .= "%%$text%%";
        }
    }

    function php($text, $wrapper='code') {
        $this->not_block();
        $this->doc .= "<php>$text</php>";
    }

    function phpblock($text) {
        $this->block();
        $this->doc .= "<PHP>$text</PHP>";
    }

    function html($text, $wrapper='code') {
        $this->not_block();
        $this->doc .= "<html>$text</html>";
    }

    function htmlblock($text) {
        $this->block();
        $this->doc .= "<HTML>$text</HTML>";
    }

    function quote_open() {
        $this->block();
        if (substr($this->doc, -(++$this->quotelvl)) === DOKU_LF . str_repeat('>', $this->quotelvl - 1)) {
            $this->doc .= '>';
        } else {
            $this->doc .= DOKU_LF . str_repeat('>', $this->quotelvl);
        }
        $this->prepend_not_block = ' ';
    }

    function quote_close() {
        $this->block();
        $this->quotelvl--;
        if (strrpos($this->doc, DOKU_LF) === strlen($this->doc) - 1) {
            return;
        }
        $this->doc .= DOKU_LF . DOKU_LF;
    }

    function preformatted($text) {
        $this->block();
        $this->doc .= preg_replace('/^/m', '  ', $text) . DOKU_LF;
    }

    function file($text, $language=null, $filename=null) {
        $this->_highlight('file',$text,$language,$filename);
    }

    function code($text, $language=null, $filename=null) {
        $this->_highlight('code',$text,$language,$filename);
    }

    function _highlight($type, $text, $language=null, $filename=null) {
        $this->block();
        $this->doc .= "<$type";
        if ($language != null) {
            $this->doc .= " $language";
        }
        if ($filename != null) {
            $this->doc .= " $filename";
        }
        $this->doc .= ">$text</$type>";
    }

    function acronym($acronym) {
        $this->not_block();
        $this->doc .= $acronym;
    }

    function smiley($smiley) {
        $this->not_block();
        $this->doc .= $smiley;
    }

    function entity($entity) {
        $this->not_block();
        $this->doc .= $entity;
    }

    function multiplyentity($x, $y) {
        $this->not_block();
        $this->doc .= "{$x}x{$y}";
    }

    function singlequoteopening() {
        $this->not_block();
        $this->doc .= "'";
    }

    function singlequoteclosing() {
        $this->not_block();
        $this->doc .= "'";
    }

    function apostrophe() {
        $this->not_block();
        $this->doc .= "'";
    }

    function doublequoteopening() {
        $this->not_block();
        $this->doc .= '"';
    }

    function doublequoteclosing() {
        $this->not_block();
        $this->doc .= '"';
    }

    /**
     */
    function camelcaselink($link) {
        $this->not_block();
        $this->doc .= $link;
    }


    function locallink($hash, $name = null){
        $this->not_block();
        $this->doc .= "[[#$hash";
        if ($name !== null) {
            $this->doc .= '|';
            $this->_echoLinkTitle($name);
        }
        $this->doc .= ']]';
    }

    function internallink($id, $name = null, $search=null,$returnonly=false,$linktype='content') {
        $this->not_block();
        $this->doc .= "[[$id";
        if ($name !== null) {
            $this->doc .= '|';
            $this->_echoLinkTitle($name);
        }
        $this->doc .= ']]';
    }

    function externallink($url, $name = null) {
        $this->not_block();
        if ($name !== null && !in_array($url, array($name, 'http://' . $name))) {
            $this->doc .= "[[$url|";
            $this->_echoLinkTitle($name);
            $this->doc .= ']]';
        } else {
            if ($url === "http://$name") {
                $url = $name;
            }
            $this->doc .= $url;
        }
    }

    /**
     */
    function interwikilink($match, $name = null, $wikiName, $wikiUri) {
        $this->not_block();
        $this->doc .= "[[$wikiName>$wikiUri";
        if ($name !== null) {
            $this->doc .= '|';
            $this->_echoLinkTitle($name);
        }
        $this->doc .= ']]';
    }

    /**
     */
    function windowssharelink($url, $name = null) {
        $this->not_block();
        $this->doc .= "[[$url";
        if ($name !== null) {
            $this->doc .= '|';
            $this->_echoLinkTitle($name);
        }
        $this->doc .= "]]";
    }

    function emaillink($address, $name = null) {
        $this->not_block();
        if ($name === null) {
            $this->doc .= "<$address>";
        } else {
            $this->doc .= "[[$address|";
            $this->_echoLinkTitle($name);
            $this->doc .= ']]';
        }
    }

    function internalmedia ($src, $title=null, $align=null, $width=null,
                            $height=null, $cache=null, $linking=null) {
        $this->not_block();
        $this->doc .= '{{';
        if ($align === 'center' || $align === 'right') {
            $this->doc .= ' ';
        }
        $this->doc .= $src;

        $params = array();
        if ($width !== null) {
            $params[0] = $width;
            if ($height !== null) {
                $params[0] .= "x$height";
            }
        }
        if ($cache !== 'cache') {
            $params[] = $cache;
        }
        if ($linking !== 'details') {
            $params[] = $linking;
        }
        if (count($params) > 0) {
            $this->doc .= '?';
        }
        $this->doc .= join('&', $params);

        if ($align === 'center' || $align === 'left') {
            $this->doc .= ' ';
        }
        if ($title != null) {
            $this->doc .= "|$title";
        }
        $this->doc .= '}}';
    }

    function externalmedia ($src, $title=null, $align=null, $width=null,
                            $height=null, $cache=null, $linking=null) {
        $this->internalmedia($src, $title, $align, $width, $height, $cache, $linking);
    }

    /**
     * Renders an RSS feed
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function rss ($url,$params){
        $this->block();
        $this->doc .= '{{rss>' . $url;
        $vals = array();
        if ($params['max'] !== 8) {
            $vals[] = $params['max'];
        }
        if ($params['reverse']) {
            $vals[] = 'reverse';
        }
        if ($params['author']) {
            $vals[] = 'author';
        }
        if ($params['date']) {
            $vals[] = 'date';
        }
        if ($params['details']) {
            $vals[] = 'desc';
        }
        if ($params['refresh'] !== 14400) {
            $val = '10m';
            foreach(array('d' => 86400, 'h' => 3600, 'm' => 60) as $p => $div) {
                $res = $params['refresh'] / $div;
                if ($res === intval($res)) {
                    $val = "$res$p";
                    break;
                }
            }
            $vals[] = $val;
        }
        if (count($vals) > 0) {
            $this->doc .= ' ' . join(' ', $vals);
        }
        $this->doc .= '}}';
    }

    function table_open() {
        $this->block();
        $this->_table = array();
        $this->_row = 0;
        $this->_rowspans = array();
    }

    function table_close($pos) {
        $this->block();
        $this->doc .= table_to_wikitext($this->_table);
    }

    function tablerow_open() {
        $this->block();
        $this->_table[++$this->_row] = array();
        $this->_key = 1;
        while (isset($this->_rowspans[$this->_key])) {
            --$this->_rowspans[$this->_key];
            if ($this->_rowspans[$this->_key] === 1) {
                unset($this->_rowspans[$this->_key]);
            }
            ++$this->_key;
        }
    }

    function tablerow_close(){
        $this->block();
    }

    function tableheader_open($colspan = 1, $align = null, $rowspan = 1){
        $this->_cellopen('th', $colspan, $align, $rowspan);
    }

    function _cellopen($tag, $colspan, $align, $rowspan) {
        $this->block();
        $this->_table[$this->_row][$this->_key] = compact('tag', 'colspan', 'align', 'rowspan');
        if ($rowspan > 1) {
            $this->_rowspans[$this->_key] = $rowspan;
            $this->_ownspan = true;
        }
        $this->_pos = strlen($this->doc);
    }

    function tableheader_close(){
        $this->_cellclose();
    }

    function _cellclose() {
        $this->block();
        $this->_table[$this->_row][$this->_key]['text'] = trim(substr($this->doc, $this->_pos));
        $this->doc = substr($this->doc, 0, $this->_pos);
        $this->_key += $this->_table[$this->_row][$this->_key]['colspan'];
        while (isset($this->_rowspans[$this->_key]) && !$this->_ownspan) {
            --$this->_rowspans[$this->_key];
            if ($this->_rowspans[$this->_key] === 1) {
                unset($this->_rowspans[$this->_key]);
            }
            ++$this->_key;
        }
        $this->_ownspan = false;
    }

    function tablecell_open($colspan = 1, $align = null, $rowspan = 1){
        $this->_cellopen('td', $colspan, $align, $rowspan);
    }

    function tablecell_close(){
        $this->_cellclose();
    }

    function plugin($name, $args, $state, $match) {
        $this->not_block();
        // This will break for plugins which provide a catch-all render method
        // like the do or pagenavi plugins
#        $plugin =& plugin_load('syntax',$name);
#        if($plugin === null || !$plugin->render($this->getFormat(),$this,$args)) {
            $this->doc .= $match;
#        }
    }

    function _echoLinkTitle($title) {
        if (is_array($title)) {
            extract($title);
            $this->internalmedia($src, $title, $align, $width, $height, $cache,
                                 $linking);
        } else {
            $this->doc .= $title;
        }
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
