<?php
if (! class_exists('syntax_plugin_hr')) {
	if (! defined('DOKU_PLUGIN')) {
		if (! defined('DOKU_INC')) {
			define('DOKU_INC', realpath(dirname(__FILE__) . '/../../') . '/');
		} // if
		define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
	} // if
	// include parent class
	require_once(DOKU_PLUGIN . 'syntax.php');

/**
 * <tt>syntax_plugin_hr.php </tt>- A PHP4 class that implements
 * a <tt>DokuWiki</tt> plugin for <tt>horizonal rule</tt> (HR)
 * elements.
 *
 * <p>
 * Just put four (or more) consecutive hyphens (minus signs) on
 * a separate line:<br>
 * <tt>----</tt>
 * </p>
 * <pre>
 *	Copyright (C) 2005, 2007 DFG/M.Watermann, D-10247 Berlin, FRG
 *			All rights reserved
 *		EMail : &lt;support@mwat.de&gt;
 * </pre>
 * <div class="disclaimer">
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either
 * <a href="http://www.gnu.org/licenses/gpl.html">version 3</a> of the
 * License, or (at your option) any later version.<br>
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * </div>
 * @author <a href="mailto:support@mwat.de">Matthias Watermann</a>
 * @version <tt>$Id: syntax_plugin_hr.php,v 1.4 2007/08/15 12:36:19 matthias Exp $</tt>
 * @since created 29-Aug-2005
 */
class syntax_plugin_hr extends DokuWiki_Syntax_Plugin {

	/**
	 * @publicsection
	 */
	//@{

	/**
	 * Tell the parser whether the plugin accepts syntax mode
	 * <tt>$aMode</tt> within its own markup.
	 *
	 * @param $aMode String The requested syntaxmode.
	 * @return Boolean <tt>TRUE</tt> unless <tt>$aMode</tt> is
	 * <tt>'plugin_hr'</tt> (which would result in a
	 * <tt>FALSE</tt> method result).
	 * @public
	 * @see getAllowedTypes()
	 * @static
	 */
	function accepts($aMode) {
		return FALSE;
	} // accepts()

	/**
	 * Connect lookup pattern to lexer.
	 *
	 * @param $aMode String The desired rendermode.
	 * @public
	 * @see render()
	 */
	function connectTo($aMode) {
		$this->Lexer->addSpecialPattern('\n[\t\x20]*-{4,}[\t\x20]*(?=\n)',
			$aMode, 'plugin_hr');
	} // connectTo()

	/**
	 * Get an associative array with plugin info.
	 *
	 * <p>
	 * The returned array holds the following fields:
	 * <dl>
	 * <dt>author</dt><dd>Author of the plugin</dd>
	 * <dt>email</dt><dd>Email address to contact the author</dd>
	 * <dt>date</dt><dd>Last modified date of the plugin in
	 * <tt>YYYY-MM-DD</tt> format</dd>
	 * <dt>name</dt><dd>Name of the plugin</dd>
	 * <dt>desc</dt><dd>Short description of the plugin (Text only)</dd>
	 * <dt>url</dt><dd>Website with more information on the plugin
	 * (eg. syntax description)</dd>
	 * </dl>
	 * @return Array Information about this plugin class.
	 * @public
	 * @static
	 */
	function getInfo() {
		return array(
			'author' =>	'Matthias Watermann',
			'email' =>	'support@mwat.de',
			'date' =>	'2007-08-15',
			'name' =>	'Horizontal Rule Syntax Plugin',
			'desc' =>	'Add HTML Style Horizontal Rule  [ ---- ]',
			'url' =>	'http://wiki.splitbrain.org/plugin:hr');
	} // getInfo()

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
	 * @return String <tt>'block'</tt>.
	 * @public
	 * @static
	 */
	function getPType() {
		return 'block';
	} // getPType()

	/**
	 * Where to sort in?
	 *
	 * @return Integer <tt>6</tt>.
	 * @public
	 * @static
	 */
	function getSort() {
		// class 'Doku_Parser_Mode_hr' returns 160
		// class 'Doku_Parser_Mode_listblock' returns 10
		// class 'syntax_plugin_lists' returns 8
		return 6;
	} // getSort()

	/**
	 * Get the type of syntax this plugin defines.
	 *
	 * @return String <tt>'substition'</tt> (i.e. 'substitution').
	 * @public
	 * @static
	 */
	function getType() {
		return 'substition';	// sic! should be __substitution__
	} // getType()

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
	function handle($aMatch, $aState, $aPos, &$aHandler) {
		return $aState;
	} // handle()

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
	function render($aFormat, &$aRenderer, &$aData) {
		if (DOKU_LEXER_SPECIAL == $aData) {
			if ('xhtml' != $aFormat) {
				return FALSE;
			} // if
			$hits = array();
			$aRenderer->doc =
				(preg_match('|\s*<p>(?:\s*</p>)*\s*$|', $aRenderer->doc, $hits))
					? substr($aRenderer->doc, 0, -strlen($hits[0])) . '<hr />'
					: preg_replace('|\s*<p>\s*</p>\s*|', '',
						$aRenderer->doc) . '<hr />';
		} // if
		return TRUE;
	} // render()

	//@}
} // class syntax_plugin_hr
} // if
?>
