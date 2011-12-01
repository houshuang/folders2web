<?php
/**
 * DokuWiki Action Plugin quickedit
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Klier <chi@chimeric.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if(!defined('DOKU_LF')) define('DOKU_LF', "\n");

require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */

class action_plugin_quickedit extends DokuWiki_Action_Plugin {

    function getInfo() {
        return array(
                'author' => 'Arthur Lobert',
                'email'  => 'arthur.lobert@thalesgroup.com',
                'date'   => @file_get_contents(DOKU_PLUGIN.'quickedit/VERSION'),
                'name'   => 'quickedit Plugin (action component)',
                'desc'   => 'open edition in text box',
                'url'    => 'http://dokuwiki.org/plugin:quickedit',
            );
    }

    // register hook
    function register(&$controller) {
        $controller->register_hook('PARSER_HANDLER_DONE', 'BEFORE', $this, 'insert_quickedit');
    }

    /**
     * Modifies the final instruction list of a page and adds instructions for
     * an uparow link.
     *
     * Michael Klier <chi@chimeric.de>
     */
    
    function insert_quickedit(&$event, $param){

    	$ins_new = array();
        $ins =& $event->data->calls;
        $num = count($ins);
        $nb_push = 0;
        $no_par = 0;
        for($i=0;$i<$num;$i++) {
        	if($ins[$i - 1][0] == 'section_open') {
          		$nb = $ins[$i - 1][2];
          		$tmp = $i + $nb_push ;
        		$quickedit_start = array('plugin', array('quickedit', array ('start', $tmp, $nb), 1, '~~QUICKEDITSTART~~'));
        		array_push($ins_new, $quickedit_start);
            	$nb_push+=2;
        		$no_par = 1;
        	}
    		if($ins[$i][0] == 'section_close') {

    			$ins_new[$tmp][1][1][2] .='-'.$ins[$i][2];
  			
    			$quickedit_stop = array('plugin', array('quickedit', array ('stop',$tmp, $ins_new[$tmp][1][1][2]), 1, '~~QUICKEDITSTOP~~'));
                array_push($ins_new, $quickedit_stop);
    			$no_par = 0;
    		}
    		if($ins[$i - 1][0] == 'p_open' && $no_par == 0)
    		{	
    			$nb = $ins[$i - 1][2];
          		$tmp = $i + $nb_push ;
        		$quickedit_start = array('plugin', array('quickedit', array ('start', $tmp, $nb), 1, '~~QUICKEDITSTART~~'));
        		array_push($ins_new, $quickedit_start);
            	$nb_push+=2;
    		}
    		if($ins[$i][0] == 'p_close' && $no_par == 0) {
    			$ins_new[$tmp][1][1][2] .= '-'.($ins[$i][2]);
    			$quickedit_stop = array('plugin', array('quickedit', array ('stop',$tmp, $ins_new[$tmp][1][1][2]), 1, '~~QUICKEDITSTOP~~'));
                array_push($ins_new, $quickedit_stop);
    		}
    		array_push($ins_new, $ins[$i]);
    		
        }
        $ins = $ins_new;	     
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
