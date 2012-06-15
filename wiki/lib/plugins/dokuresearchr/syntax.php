<?php
/**
* Plugin Skeleton: Displays "Hello World!"
*
* Syntax: <TEST> - will be replaced with "Hello World!"
*
* @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
* @author     Christopher Smith <chris@jalakai.co.uk>
*/

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
* All DokuWiki plugins to extend the parser/rendering mechanism
* need to inherit from this class
*/
class syntax_plugin_dokuresearchr extends DokuWiki_Syntax_Plugin {


	/**
	* Get the type of syntax this plugin defines.
	*
	* @param none
	* @return String <tt>'substition'</tt> (i.e. 'substitution').
	* @public
	* @static
	*/
function getType(){
	return 'substition';
}

/**
* What kind of syntax do we allow (optional)
*/
function getAllowedTypes() {
	return array('substition','formatting');
}

/**
* Define how this plugin is handled regarding paragraphs.
*
* <p>
* This method is important for correct XHTML nesting. It returns
* one of the following values:
* </p>
* <dl>
* <dt>normal</dt><dd>The plugin can be used inside paragraphs.</dd>
* <dt>block</dt><dd>Open paragraphs need to be closed before
* plugin output.</dd>
* <dt>stack</dt><dd>Special case: Plugin wraps other paragraphs.</dd>
* </dl>
* @param none
* @return String <tt>'block'</tt>.
* @public
* @static
*/
function getPType(){
	return 'normal';
}

/**
* Where to sort in?
*
* @param none
* @return Integer <tt>6</tt>.
* @public
* @static
*/
function getSort(){
	return 281;
}


/**
* Connect lookup pattern to lexer.
*
* @param $aMode String The desired rendermode.
* @return none
* @public
* @see render()
*/
function connectTo($mode) {
	$this->Lexer->addSpecialPattern('\[@.+?\]',$mode,'plugin_dokuresearchr');
	//      $this->Lexer->addEntryPattern('<TEST>',$mode,'plugin_test');
}

//    function postConnect() {
	//      $this->Lexer->addExitPattern('</TEST>','plugin_test');
	//    }


	/**
	* Handler to prepare matched data for the rendering process.
	*
	* <p>
	* The <tt>$aState</tt> parameter gives the type of pattern
	* which triggered the call to this method:
	* </p>
	* <dl>
	* <dt>DOKU_LEXER_ENTER</dt>
	* <dd>a pattern set by <tt>addEntryPattern()</tt></dd>
	* <dt>DOKU_LEXER_MATCHED</dt>
	* <dd>a pattern set by <tt>addPattern()</tt></dd>
	* <dt>DOKU_LEXER_EXIT</dt>
	* <dd> a pattern set by <tt>addExitPattern()</tt></dd>
	* <dt>DOKU_LEXER_SPECIAL</dt>
	* <dd>a pattern set by <tt>addSpecialPattern()</tt></dd>
	* <dt>DOKU_LEXER_UNMATCHED</dt>
	* <dd>ordinary text encountered within the plugin's syntax mode
	* which doesn't match any pattern.</dd>
	* </dl>
	* @param $aMatch String The text matched by the patterns.
	* @param $aState Integer The lexer state for the match.
	* @param $aPos Integer The character position of the matched text.
	* @param $aHandler Object Reference to the Doku_Handler object.
	* @return Integer The current lexer state for the match.
	* @public
	* @see render()
	* @static
	*/
function handle($match, $state, $pos, &$handler){
	switch ($state) {
		case DOKU_LEXER_ENTER :
		break;
		case DOKU_LEXER_MATCHED :
		break;
		case DOKU_LEXER_UNMATCHED :
		break;
		case DOKU_LEXER_EXIT :
		break;
		case DOKU_LEXER_SPECIAL :
		$citekey = substr($match,2,-1);
		$json =file_get_contents(dirname ( __FILE__ )."/json.tmp");
		$t = json_decode($json,true);
		$entry = $t[$citekey];
		$cit =  $entry[0];
		$year = $entry[1];
		return array($citekey,$cit,$year,$entry[2]);
		break;
	}
	return array();
}

/**
* Handle the actual output creation.
*
* <p>
* The method checks for the given <tt>$aFormat</tt> and returns
* <tt>FALSE</tt> when a format isn't supported. <tt>$aRenderer</tt>
* contains a reference to the renderer object which is currently
* handling the rendering. The contents of <tt>$aData</tt> is the
* return value of the <tt>handle()</tt> method.
* </p>
* @param $aFormat String The output format to generate.
* @param $aRenderer Object A reference to the renderer object.
* @param $aData Array The data created by the <tt>handle()</tt>
* method.
* @return Boolean <tt>TRUE</tt> if rendered successfully, or
* <tt>FALSE</tt> otherwise.
* @public
* @see handle()
*/
function render($mode, &$renderer, $data)
{
	if($mode == 'xhtml')
	{
		global $ID;
		if($data[1] != '')
		{
			if (page_exists(":ref:".$data[0])) {
				$linktext = "<a href='/wiki/ref:" . $data[0] . "' class='wikilink1'>";
			}
			else
			{
				$linktext = '<u>';
			}
			$renderer->doc .= "<span class='tooltip_winlike'>".$linktext.$data[1].", ".$data[2] . "</u></a><span class=\"tip\">".$data[3]."</span></span></span>";
		}
		else
		{
			$renderer->doc .= $renderer->internallink(":ref:".$data[0]);
		}
		return true;
	}
	if($mode == 'metadata')
	{
		$renderer->internallink(":ref:".$data[0]);
		return true;
	}
	return false;
}
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
?>