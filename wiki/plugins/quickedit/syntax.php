<?php
/**
 * DokuWiki Syntax Plugin quickedit
 *
 * Shows an arrow image which links to the top of the page.
 * The image can be defined via the configuration manager.
 *
 * Syntax:  ~~QUICKEDIT~~
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Michael Klier <chi@chimeric.de>
 */
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_quickedit extends DokuWiki_Syntax_Plugin {
 
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Arthur Lobert',
            'email'  => 'arthur.lobert@thalesgroup.com',
            'date'   => @file_get_contents(DOKU_PLUGIN.'quickedit/VERSION'),
            'name'   => 'Plugin Quickedit (syntax component)',
            'desc'   => 'Edit your page in live with texbox',
            'url'    => 'http://dokuwiki.org/plugin:quickedit',
        );
    }
 
    /**
     * Syntax Type
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     */
    function getType()  { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort()  { return 304; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) { $this->Lexer->addSpecialPattern('~~QUICKEDITSTART~~',$mode,'plugin_quickedit'); 
    	// $this->Lexer->addSpecialPattern('~~QUICKEDIT~~',$mode,'plugin_quickedit'); 
   }
 		
    /**
     * Handle the match
     */
   // function handle($match, $state, $pos, &$handler){    
   // }
 
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
    	global $ID;
    	
    	if  ($data[0] == 'start')
    	{
    		$toto = 'quickedit_start'. $data[1];
    		$titi = 'quickedit_stop'. $data[1];
    		$renderer->doc .= "
    		<div id='quickedit' onDblClick= 'plugin_quickedit_go(".$data[1].",\"".$data[2]."\",\"".$ID."\",\"".$_REQUEST['do']."\" )'>
    			<div id= '".$toto."' style = 'border : 0px black solid ; display : block'>
    			<input type='hidden' id='old' value='0' />";
    	}
    	if ($data[0] == 'stop')
    	{
    		$toto = 'quickedit_start'. $data[1];
    		$titi = 'quickedit_stop'. $data[1];
    		$renderer->doc .= "</div>
    			<div id='".$titi."' style ='border : 1px lightgrey solid ; padding : 5px 10px 5px 5px ; display : none'>
    					<textarea id='quickedit_textbox".$data[1]."' rows=3 cols=100 style='width:100%;height:100%' ></textarea>
    					<div style= 'margin-top : 5px;'>
    					<label class='nowrap' for='edit__summary'>Edit summary 
    						<input type='text' id='editsummary".$data[1]."' name='summary' value='' class='edit' size='50' tabindex='2' />
    					</label>
    					<label class='nowrap' for='minoredit'>
    						<input type='checkbox' id='minoredit".$data[1]."' name='minor' value='1' tabindex='3' />
    							<span>
    							 	Minor Changes
    							</span>
    					</label>
    					<input style='position : relative ; bottom : -4px' type='image' src='".DOKU_BASE."/lib/plugins/quickedit/ressources/add.gif' onClick='quickedit_save(".$data[1].",\"".$data[2]."\",\"".$ID."\")'/>
    					<input style='position : relative ; bottom : -4px ' type='image' src='".DOKU_BASE."/lib/plugins/quickedit/ressources/delete.gif' onClick='quickedit_cancel(".$data[1].",\"".$data[2]."\",\"".$ID."\")'/>
    				</div>
    			</div>
    			<div id='load".$data[1]."' style = 'display : none'>
    				<img src='".DOKU_BASE."/lib/images/loading.gif' />
    			</div>
    		</div>";}

	}
}
// vim:ts=4:sw=4:et:enc=utf-8:
