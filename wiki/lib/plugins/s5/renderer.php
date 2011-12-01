<?php
/**
 * Renderer for XHTML output
 *
 * @author Harry Fuecks <hfuecks@gmail.com>
 * @author Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// we inherit from the XHTML renderer instead directly of the base renderer
require_once DOKU_INC.'inc/parser/xhtml.php';

/**
 * The Renderer
 */
class renderer_plugin_s5 extends Doku_Renderer_xhtml {
    var $slideopen = false;
    var $base='';
    var $tpl='';

    /**
     * the format we produce
     */
    function getFormat(){
        // this should be 's5' usally, but we inherit from the xhtml renderer
        // and produce XHTML as well, so we can gain magically compatibility
        // by saying we're the 'xhtml' renderer here.
        return 'xhtml';
    }


    /**
     * Initialize the rendering
     */
    function document_start() {
        global $ID;

        // call the parent
        parent::document_start();

        // store the content type headers in metadata
        $headers = array(
            'Content-Type' => 'text/html; charset=utf-8'
        );
        p_set_metadata($ID,array('format' => array('s5' => $headers) ));
        $this->base = DOKU_BASE.'lib/plugins/s5/ui/';
        $this->tpl  = $this->getConf('template');
    }

    /**
     * Print the header of the page
     *
     * Gets called when the very first H1 header is discovered. It includes
     * all the S5 CSS and JavaScript magic
     */
    function s5_init($title){
        global $conf;
        global $lang;
        global $INFO;
        global $ID;

        //throw away any previous content
        $this->doc = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$conf['lang'].'"
 lang="'.$conf['lang'].'" dir="'.$lang['direction'].'">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.hsc($title).'</title>
<!-- metadata -->
<meta name="generator" content="S5" />
<meta name="version" content="S5 1.1" />
<!-- configuration parameters -->
<meta name="defaultView" content="slideshow" />
<meta name="controlVis" content="hidden" />
<!-- style sheet links -->
<link rel="stylesheet" href="'.DOKU_BASE.'lib/styles/style.css" type="text/css" media="screen" />
<link rel="stylesheet" href="'.$this->base.$this->tpl.'/slides.css" type="text/css" media="projection" id="slideProj" />
<link rel="stylesheet" href="'.$this->base.'default/outline.css" type="text/css" media="screen" id="outlineStyle" />
<link rel="stylesheet" href="'.$this->base.'default/print.css" type="text/css" media="print" id="slidePrint" />
<link rel="stylesheet" href="'.$this->base.'default/opera.css" type="text/css" media="projection" id="operaFix" />
<!-- S5 JS -->
<script src="'.$this->base.'default/slides.js" type="text/javascript"></script>
</head>
<body>
<div class="layout">
<div id="controls"><!-- DO NOT EDIT --></div>
<div id="currentSlide"><!-- DO NOT EDIT --></div>
<div id="header"></div>
<div id="footer">
<h1>'.$ID.'</h1>
<h2>'.hsc($conf['title']).' &#8226; '.strftime($conf['dformat'],$INFO['lastmod']).'</h2>
</div>

</div>
<div class="presentation">
';
    }

    /**
     * Closes the document
     */
    function document_end(){
        // we don't care for footnotes and toc
        // but cleanup is nice
        $this->doc = preg_replace('#<p>\s*</p>#','',$this->doc);

        if($this->slideopen){
            $this->doc .= '</div>'.DOKU_LF; //close previous slide
        }
        $this->doc .= '</div>
                       </body>
                       </html>';
    }

    /**
     * This is what creates new slides
     *
     * A new slide is started for each H2 header
     */
    function header($text, $level, $pos) {
        if($level == 1){
            if(!$this->slideopen){
                $this->s5_init($text); // this is the first slide
                $level = 2;
            }else{
                return;
            }
        }

        if($level == 2){
            if($this->slideopen){
                $this->doc .= '</div>'.DOKU_LF; //close previous slide
            }
            $this->doc .= '<div class="slide">'.DOKU_LF;
            $this->slideopen = true;
        }
        $this->doc .= '<h'.($level-1).'>';
        $this->doc .= $this->_xmlEntities($text);
        $this->doc .= '</h'.($level-1).'>'.DOKU_LF;
    }

    /**
     * Top-Level Sections are slides
     */
    function section_open($level) {
        if($level < 3){
            $this->doc .= '<div class="slidecontent">'.DOKU_LF;
        }else{
            $this->doc .= '<div>'.DOKU_LF;
        }
        // we don't use it 
    }

    /**
     * Throw away footnote
     */
    function footnote_close() {
        // recover footnote into the stack and restore old content
        $footnote = $this->doc;
        $this->doc = $this->store;
        $this->store = '';
    }

    /**
     * No acronyms in a presentation
     */
    function acronym($acronym){
        $this->doc .= $this->_xmlEntities($acronym);
    }

    /**
     * A line stops the slide and start the handout section
     */
    function hr() {
        $this->doc .= '</div>'.DOKU_LF;
        $this->doc .= '<div class="handout">'.DOKU_LF;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
