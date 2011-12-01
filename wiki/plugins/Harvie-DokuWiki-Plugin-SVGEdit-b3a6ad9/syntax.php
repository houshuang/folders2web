<?php 
/** 
 * SVGEdit Plugin: Nice way, to create, store, edit and embed SVG images into DokuWiki
 * Usage: 
 * embed svg using do=export_svg
 *   {{svg>page.svg}}
 *   {{svg>namespace:page.svg}}
 * base64 encode svg directly (requires ~~NOCACHE~~)
 *   {{SVG>page.svg}}
 *   {{SVG>namespace:page.svg}}
 * base64 encode inline svg directly
 *   <svg args...>...code...</svg>
 * 
 * @license    Copylefted
 * @author     Thomas Mudrunka <harvie--email-cz>
 */ 
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/'); 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/'); 
require_once(DOKU_PLUGIN.'syntax.php'); 
  
class syntax_plugin_svgedit extends DokuWiki_Syntax_Plugin { 

    var $helper = null;

    function getInfo() { 
            return array('author' => 'Thomas Mudrunka',
                         'email'  => 'harvie--email-cz',
                         'date'   => '2010-02-21',
                         'name'   => 'SVG-Edit Plugin',
                         'desc'   => 'Nice way, to create, store, edit and embed SVG images into DokuWiki',
                         'url'    => 'http://www.dokuwiki.org/plugin:svgedit'
                 );
    } 

    function getType() { return 'substition'; }
    function getSort() { return 303; }
    function getPType() { return 'block'; }

    function connectTo($mode) {  
        $this->Lexer->addSpecialPattern("{{svg>.+?}}", $mode, 'plugin_svgedit');  
        $this->Lexer->addSpecialPattern("{{SVG>.+?}}", $mode, 'plugin_svgedit');  
				$this->Lexer->addSpecialPattern("<svg.+?</svg>", $mode, 'plugin_svgedit');
    } 

    function handle($match, $state, $pos, &$handler) {
				$type = substr($match,0,4);
        return array($type, $match); 
    }

    function render($format, &$renderer, $data) {
				if ($format!='xhtml') return;
				global $ID;

				if($data[0]==='<svg') {
					$svgenc = 'data:image/svg+xml;base64,'.base64_encode($data[1]).'" type="image/svg+xml';
        	$renderer->doc .= '<a href="'.$svgenc.'" type="image/svg+xml" /><img src="'.$svgenc.'" alt="svg-image@'.$ID.'" /></a>'."<br />";
					return true;
				}
				if($data[0]==='{{sv') {
					$data[1] = trim(substr($data[1], 6, -2));
					$svgenc = exportlink($data[1],'svg');
					$renderer->doc .= '<a href="'.$svgenc.'" type="image/svg+xml" /><img src="'.$svgenc.'" alt="image:'.htmlspecialchars($data[1]).'" type="image/svg+xml"/></a><br />';
					//$renderer->doc .= '<a href="'.$svgenc.'" type="image/svg+xml" /><object data="'.$svgenc.'" type="image/svg+xml" width="100%"><img src="'.$svgenc.'" alt="image:'.htmlspecialchars($data[1]).'" type="image/svg+xml"/></object></a><br />'; //scrollbars on webkit :-(
					$renderer->doc .= html_wikilink($data[1],'svg@'.$data[1]);
        	return true;
				}
				if($data[0]==='{{SV') {
					$data[1] = trim(substr($data[1], 6, -2));
					$svgenc = 'data:image/svg+xml;base64,'.base64_encode(rawWiki($data[1])).'" type="image/svg+xml';
					$renderer->doc .= '<a href="'.$svgenc.'" type="image/svg+xml" /><img src="'.$svgenc.'" alt="image:'.htmlspecialchars($data[1]).'" /></a><br />';
					$renderer->doc .= html_wikilink($data[1],'SVG@'.$data[1]);
        	return true;
				}
    }
}
