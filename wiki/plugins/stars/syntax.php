<?php
/*
 
Stars Plugin for DokuWiki
Use for difficulty, rating, etc

@License: GPL 2 (http://www.gnu.org/licenses/gpl.html)
@author: Collin "Keeyai" Green - http://keeyai.com
@url: http://keeyai.com/projects-and-releases/
@version: 1.1


Original code from the Skill plugin by iDo - http://www.dokuwiki.org/plugin:skill

Modified by Keeyai to include some useful changes mentioned in the comments and other functionality
	- star limit by anon and kenc
	- span instead of div by anon
	- new star image instead of transparency
	- added classes for styling purposes ( span.starspan, img.starimage, img.halfstarimage, img.emptystarimage)
	- show half stars  (number is floored to the nearest half)
	- packaged to work with plugin manager


Usage:  [stars=num] where num is a number, eg:  5, or a ratio, eg: 5/7
				limits the number of stars to 10 -- ratios over ten, eg: 100/500,  will be reduced, eg: 2/10
				
Examples:
	show 2 stars:						[stars=2]
	show 3/10 stars:				[stars=3/10]
	show 4.5/5 stars:				[stars=4.5/5]

Note:
	to use custom images, just replace the star.gif, halfstar.gif, and emptystar.cif files
	 	
TODO:  other image options?  control panel?
 	
*/
 
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

if(!defined('DOKU_PATH')) define('DOKU_PATH', dirname($_SERVER['PHP_SELF']));

require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_stars extends DokuWiki_Syntax_Plugin {

  function getInfo()
	{
      return array(
          'author' => 'Keeyai',
          'email'  => 'keeyai@keeyai.com',
          'date'   => '27/09/2008',
          'name'   => 'Stars Plugin',
          'desc'   => 'Show stars for difficulty, ratings, etc',
          'url'    => 'http://www.dokuwiki.org/plugin:stars',
      );
  } // end function getInfo()

  function getType(){ return 'substition'; }// spelling error is intentional to work with dokuwiki}
  function getSort(){ return 107; }

  function connectTo($mode) 
	{
		$this->Lexer->addSpecialPattern("\[stars=\d*\.?\d*/?\d*\.?\d*\]",$mode,'plugin_stars');
  } // end function connectTo($mode)

  function handle($match, $state, $pos, &$handler)
	{
      $match = substr($match,7,-1); // Strip markup
			$match=split('/',$match); // Strip size

      if (!isset($match[1])) 
				$match[1] = $match[0];
				
			if ($match[0]>$match[1]) 
				$match[1]=$match[0];
	
			if ($match[1]>10) 
			{
		    $match[0] = 10 * $match[0] / $match[1];
		    $match[1] = 10;
		  } // end if ($match[1]>10) 

      return $match;
  }  // end function handle($match, $state, $pos, &$handler)

  function render($mode, &$renderer, $data) 
	{
      if($mode == 'xhtml')
			{       		
          $renderer->doc .= $this->_Stars($data);
          return true;
      } // end if($mode == 'xhtml')
      return false;
  } // end function render($mode, &$renderer, $data) 
 
	function _Stars($d) 
	{
		$string="<span class='starspan' alt='" . $d[0] . '/' . $d[1] . " stars'>";
		
		// render full stars
		for($i=1; $i<=$d[0]; $i++) 
			$string .= '<img class="starimage" src="' . DOKU_PATH .'/lib/plugins/stars/star.gif" alt=""  />';
			
		// render half star if necessary
		if($i-.5 <= $d[0])
		{
			$string .= '<img class="halfstarimage" src="' . DOKU_PATH .'/lib/plugins/stars/halfstar.gif" alt=""  />';
			$i+= .5;
		} // end if($i-$d[0] > 0)
		
		for($i;$i<=$d[1];$i++) 
			$string .= '<img class="emptystarimage" src="' . DOKU_PATH .'/lib/plugins/stars/emptystar.gif" alt="" />';

		$string .= '</span>';
		
		return $string;
	} // end function _Stars($d) 
	
} // end class syntax_plugin_stars extends DokuWiki_Syntax_Plugin {
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>