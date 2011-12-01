<?php
/**
 * Subject Index plugin : entry syntax
 * indexes any subject index entries on the page (to data/index/subject.idx by default)
 *
 * Using the ::[heading/sub-heading/]entry[|display text]:: syntax
 * a new subject index entry can be added
 *
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Symon Bent <hendrybadao@gmail.com>
 *
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN . 'subjectindex/inc/common.php');

class syntax_plugin_subjectindex_entry extends DokuWiki_Syntax_Plugin {

	function __construct() {
    }

    function getType() {
		return 'substition';
	}

	function getSort() {
		return 305;
	}

	function getPType(){
		return 'normal';
	}

	function connectTo($mode) {
        // Syntax: {{entry>[idx no./heading/]entry text[|display name]}}    [..] optional
		$pattern = SUBJ_IDX_ENTRY_RGX;
		$this->Lexer->addSpecialPattern($pattern, $mode, 'plugin_subjectindex_entry');
        // Syntax: #tag# -or- #tag_name# (no spaces)
        $pattern = SUBJ_IDX_TAG_RGX;
        $this->Lexer->addSpecialPattern($pattern, $mode, 'plugin_subjectindex_entry');
	}

	function handle($match, $state, $pos, &$handler) {
        // first look for 'special' tag patterns: #tag#
        if($match[0] != '{') {
            $entry = utf8_substr($match, 1, -1);    // remove the '#''s
            $display = str_replace('_', ' ', $entry);
            $index = $this->getConf('subjectindex_tag_idx'); //index page for tags only!
            $is_tag = true;
        } else {
            $end = strpos($match, '>');
            $data = substr($match, $end + 1, -2); // remove {{entry>...}} markup
            list($entry, $display) = explode('|', $data);
            if (is_numeric($entry[0])) {
                // first digit refers to the index page
                list($index, $entry) = explode('/', $entry, 2);
            } else {
                // if missing then use default index_page, i.e. first in config list
                $index = 0;
            }
            $is_tag = false;
        }
        require_once(DOKU_PLUGIN . 'subjectindex/inc/common.php');
        $link_id = clean_id($entry);
        $sep = $this->getConf('subjectindex_display_sep');

        $hide = false;
        if ( ! isset($display)) {
            $display = '';
        // invisible entry, do not display!
        } elseif ($display == '-') {
            $display = '';
            $hide = true;
        // no display so show star by default
        } elseif ((isset($display) && empty($display)) || $display == '*') {
            $display = str_replace('/', $sep, $entry);
        // if display begins with n or n,n then display specific portions of the entry only
        } elseif (preg_match('/^([1-9])(,([1-9]))?$/', $display, $matches) > 0) {
            $start = $matches[1] - 1;
            $len = (count($matches) > 2) ? $matches[4] : 1;
            $levels = explode('/', $entry);
            $display = implode($sep, array_slice($levels, $start, $len));
        }

        $entry = str_replace('/', $sep, $entry);
        $index_page = get_index_page($this->getConf('subjectindex_index_pages'), $index);
		return array($entry, $display, $link_id, $index_page, $hide, $is_tag);
	}

	function render($mode, &$renderer, $data) {
        list($entry, $display, $link_id, $index_page, $hide, $is_tag) = $data;

        if ($mode == 'xhtml') {
            $hidden = ($hide) ? ' hidden' : '';
            $entry = ($is_tag) ? $this->getLang('subjectindex_tag') . $entry : $this->getLang('subjectindex_prefix') . $entry;
			$renderer->doc .= '<a id="' . $link_id . ' " class="entry' . $hidden .
                              '" title="' . $this->html_encode($entry) .
                              '" href="' . wl($index_page) . '#' . $link_id . '">' .
                              $this->html_encode($display) . '</a>' . DOKU_LF;
			return true;
		}
		return false;
	}

    private function _swap_delim($entry, $delims = array(' > ')) {
        return str_replace('/', $delims, $entry);
    }

    private function html_encode($text) {
        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
        return $text;
    }
}
