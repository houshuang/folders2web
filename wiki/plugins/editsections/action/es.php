<?php
/**
 * DokuWiki Plugin editsections (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Christophe Drevet <dr4ke@dr4ke.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';
include_once DOKU_INC.'inc/infoutils.php';

class action_plugin_editsections_es extends DokuWiki_Action_Plugin {

	var $sections;

	function register(&$controller) {
		$doku_version = getVersionData();
		if ( preg_match('/201.-/', $doku_version['date']) > 0 ) {
			// 2010 or later version
			$controller->register_hook('PARSER_HANDLER_DONE', 'BEFORE', $this, 'rewrite_sections');
			$controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, '_editbutton');
		} else {
			// 2009 or earlier version
			$controller->register_hook('PARSER_HANDLER_DONE', 'BEFORE', $this, 'rewrite_sections_legacy');
		}
		if ($this->getConf('cache') === 'disabled') {
			$controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_cache_use');
		}
		$controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_addconf');
	}

    function _addconf(&$event, $ags) {
        // add conf to JSINFO exported variable
        global $JSINFO;
	$doku_version = getVersionData();
	if ( preg_match('/201.-/', $doku_version['date']) > 0  and $this->getConf('cache') === 'enabled') {
            // dokuwiki >= 2010-11-07 and cache not disabled
            $JSINFO['es_order_type'] = 'flat';
        } else {
            $JSINFO['es_order_type'] = $this->getConf('order_type');
        }
    }

    function _editbutton(&$event, $param) {
	dbglog('HTML_SECEDIT_BUTTON hook', 'editsections plugin');
	$order = $this->getConf('order_type');
	if (count($this->sections) === 0) {
		dbglog('cache in use, reset edit section name');
		// When the page is in cache, the sections are not preprocessed and
		// existing section names are useless: they reference the next section
		// so, we replace them by a section number
		if ( preg_match('/[0-9]$/', $event->data['range']) > 0 ) {
			$event->data['name'] = 'section '.$event->data['secid'];
		} else {
			$event->data['name'] = '';
		}
		return;
	}
        if ($event->data['target'] === 'section') {
		$ind = $event->data['secid'];
		// Compute new values
		$last_ind = count($this->sections) - 1;
		$start = $this->sections[$ind]['start'];
		$event->data['name'] = $this->sections[$ind]['name'];
		if ( $order === 'flat') {
			// flat editing
			$event->data['range'] = strval($start).'-'.strval($this->sections[$ind]['end']);
		} elseif ( $order === 'nested' ) {
			// search end of nested section editing
			$end_ind = $ind;
			while ( ($end_ind + 1 <= $last_ind) and ($this->sections[$end_ind + 1]['level'] > $this->sections[$ind]['level']) ) {
				$end_ind++;
			}
			$event->data['range'] = strval($start).'-'.strval($this->sections[$end_ind]['end']);
			if ($end_ind > $ind) {
				$event->data['name'] .= ' -> '.$this->sections[$end_ind]['name'];
			}
		} else {
			dbglog('ERROR (plugin editsections): section editing type unknown ('.$order.')');
		}
        }
    }

	function _cache_use(&$event, $ags) {
		dbglog('PARSER_CACHE_USE hook', 'editsections plugin');
		global $ID;
		if ( auth_quickaclcheck($ID) >= AUTH_EDIT ) {
			// disable cache only for writers
			$event->_default = 0;
		}
	}
	function rewrite_sections(&$event, $ags) {
		dbglog('PARSER_HANDLER_DONE hook', 'editsections plugin');
		// get the instructions list from the handler
		$calls =& $event->data->calls;
		$edits = array();
		$order = $this->getConf('order_type');
		
		// fake section inserted in first position in order to have an edit button before the first section
		$fakesection = array( array( 'header',				// header entry
		                              array ( $calls[0][1][0],		// Reuse original header name because Dokuwiki
										// may use the first heading for the page name.
		                                      0,			// level 0 since this is not a real header
		                                      1),			// start : will be overwritten in the following loop
		                              1),				// start : will be overwritten in the following loop
		                      array ( 'section_open',			// section_open entry
		                              array(0),				// level
		                              1),				// start : will be overwritten in the following loop
		                      array ( 'section_close',			// section_close entry
		                              array(),				//
		                              1)				// end : will be overwritten in the following loop
		);
		$calls = array_merge($fakesection, $calls);
		// store all sections in a separate array to compute their start, end...
		$this->sections = array();
		$count = 0;
		foreach( $calls as $index => $value ) {
			if ($value[0] === 'header') {
				$count += 1;
				$this->sections[] = array( 'level' => $value[1][1],
				                     'start' => $value[2],
				                     'name' => $value[1][0],
				                     'header' => $index );
			}
			if ($value[0] === 'section_open') {
				if ($value[1][0] !== $this->sections[$count - 1]['level']) {
				}
				if ($value[2] !== $this->sections[$count - 1]['start']) {
				}
				$this->sections[$count - 1]['open'] = $index;
			}
			if ($value[0] === 'section_close') {
				$this->sections[$count - 1]['end'] = $value[2];
				$this->sections[$count - 1]['close'] = $index;
			}
		}
		// Compute new values
		$h_ind = -1; // header index
		$o_ind = -1; // open section index
		$c_ind = -1; // close section index
		$last_ind = count($this->sections) - 1;
		foreach( $this->sections as $index => $value ) {
			// set values in previous header
			if ( $h_ind >= 0 ) {
				// set start of section
				$calls[$h_ind][1][2] = $value['start'];
				$calls[$h_ind][2] = $value['start'];
			}
			// set values in previous section_open
			if ( $o_ind >= 0 ) {
				// set start of section
				$calls[$o_ind][2] = $value['start'];
			}
			// set values in previous section_close
			if ( $c_ind >= 0 ) {
				// set end of section
				$calls[$c_ind][2] = $value['end'];
			}
			// store indexes
			$h_ind = $value['header'];
			$o_ind = $value['open'];
			$c_ind = $value['close'];
		}
		// Now, set values for the last section start = end = last byte of the page
		// If not set, the last edit button disappear and the last section can't be edited
		// without editing entire page
		if ( $h_ind >= 0 ) {
			// set start of section
			$calls[$h_ind][1][2] = $this->sections[$last_ind][end];
			$calls[$h_ind][2] = $this->sections[$last_ind][end];
		}
		if ( $o_ind >= 0 ) {
			// set start of section
			$calls[$o_ind][2] = $this->sections[$last_ind][end];
		}
		if ( $c_ind >= 0 ) {
			// set end of section
			$calls[$c_ind][2] = $this->sections[$last_ind][end];
		}
	}

        function rewrite_sections_legacy(&$event, $ags) {
                // get the instructions list from the handler
                $calls =& $event->data->calls;
                $edits = array();
                $order = $this->getConf('order_type');

                // scan instructions for edit sections
                $size = count($calls);
                for ($i=0; $i<$size; $i++) {
                        if ($calls[$i][0]=='section_edit') {
                                $edits[] =& $calls[$i];
                        }
                }

                // rewrite edit section instructions
                $last = max(count($edits)-1,0);
                for ($i=0; $i<=$last; $i++) {
                        $end = 0;
                        // get data to move
                        $start = $edits[min($i+1,$last)][1][0];
                        $level = $edits[min($i+1,$last)][1][2];
                        $name  = $edits[min($i+1,$last)][1][3];
                        // find the section end point
                        if ($order === 'nested') {
                                $finger = $i+2;
                                while (isset($edits[$finger]) && $edits[$finger][1][2]>$level) {
                                        $finger++;
                                }
                                if (isset($edits[$finger])) {
                                        $end = $edits[$finger][1][0]-1;
                                }
                        } else {
                                $end = $edits[min($i+1,$last)][1][1];
                        }
                        // put the data back where it belongs
                        $edits[$i][1][0] = $start;
                        $edits[$i][1][1] = $end;
                        $edits[$i][1][2] = $level;
                        $edits[$i][1][3] = $name;
                }
                $edits[max($last-1,0)][1][1] = 0;  // set new last section
                $edits[$last][1][0] = -1; // hide old last section
        }

}

// vim:ts=4:sw=4:et:enc=utf-8:
