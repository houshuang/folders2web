<?php /**
 *  This plugin provides alternative heading syntax.
 *  It converts h1. to h6. to the DokuWiki headings
 *
 *  @author     Adam B. Ross <abr.programmer@gmail.com>
 *  @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *  @version    htag syntax plugin, v0.9
 *  @since      23-Jul-2007
**/
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
class syntax_plugin_htag extends DokuWiki_Syntax_Plugin {
 
        function getInfo() {
                return array ( 
                        'author' => 'Adam B. Ross',
                        'email' => 'grayside@gmail.com',
                        'date' => '2007-07-23',
                        'name' => 'Heading Level Tag',
                        'desc' => 'Adds outline-style markup (h1.~) syntax for headings.',
                        'url' => 'http://www.dokuwiki.org/plugin:htag'
                );
        }
 
        // header specific
        function getType() { return 'baseonly'; }
 
        // headings shouldn't be parsed..
        function accepts($mode) { return false; }
 
        function connectTo( $mode ) {
                $this->Lexer->addSpecialPattern( '^[hH][1-6]\.[ \t]*[^\n]+(?=\n)', $mode, 'plugin_htag' );
        }
 
        // Doku_Parser_Mode 60
        // header (numbered headers) 45
        function getSort() { return 44; }
 
        function handle( $match, $state, $pos, &$handler )
        {
                global $conf;
                preg_match( '/^h\d/i', $match, $htag );
                $title = substr( $match, 3 );
                $title = trim($title);
                $level = substr( $htag[0], 1, 1 );
 
                if( $handler->status['section'] ) $handler->_addCall('section_close',array(), $pos);
                // if( $level <= $conf['maxseclevel'] ) {
                //     $handler->_addCall('section_edit',array($handler->status['section_edit_start'], $pos-1,
                //                 $handler->status['section_edit_level'], $handler->status['section_edit_title']), $pos);
                //     $handler->status['section_edit_start'] = $pos;
                //     $handler->status['section_edit_level'] = $level;
                //     $handler->status['section_edit_title'] = $title;
                // }
                $handler->_addCall('header',array($title,$level,$pos), $pos);
                $handler->_addCall('section_open',array($level),$pos);
                $handler->status['section'] = true;
 
                return true;
        }
 
        function render( $format, &$renderer, $data )
        {
                return true;
        }
 
}
