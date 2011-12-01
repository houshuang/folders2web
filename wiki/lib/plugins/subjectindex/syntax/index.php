<?php
/**
 * Part of Subject Index plugin:
 *
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author	   Symon Bent <symonbent@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_subjectindex_index extends DokuWiki_Syntax_Plugin {

	function getType() {
		return 'substition';
	}

	function getPType() {
		return 'block';
	}

	function getSort() {
		return 98;
	}

	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{subjectindex>.*?\}\}', $mode, 'plugin_subjectindex_index');
	}

	function handle($match, $state, $pos, &$handler) {

		$match = substr($match, 15, -2); // strip "{{subjectindex>...}}" markup

        // defaults
        $abstract = true;       // show abstract of page content
        $border = 'none';       // show borders around table columns
        $cols = 1;              // number of columns in subject index (max=6)
        $index = 0;             // which index page to use and display (0-9)...hopefully 10 is enough
        $proper = false;        // use proper-case for page names
        $title = false;         // use title instead of name
        $noAtoZ = false;         // turn off the A,B,C main headings
        $index_max = count(explode(';', $this->getConf('subjectindex_index_pages')));

		$args = explode(';', $match);
        foreach ($args as $arg) {
            list($key, $value) = explode('=', $arg);
            switch ($key) {
                case 'abstract':
                    $abstract = true;
                    break;
                case 'border':
                    switch ($value) {
                        case 'none':
                        case 'inside':
                        case 'outside':
                        case 'both':
                            $border = $value;
                            break;
                        default:
                            $border = 'both';
                    }
                    break;
                case 'cols':
                    $cols = ($value > 6) ? 6 : $value;
                    break;
                case 'index':
                    $index = ($value < $index_max && $value < 10) ? $value : 0;
                    break;
                case 'proper':
                    $proper = true;
                    break;
                case 'title':
                    $title = true;
                    break;
                case 'noAtoZ':
                    $noAtoZ = true;
                    break;
                default:
            }
        }
		return array($abstract, $border, $cols, $index, $proper, $title, $noAtoZ);
	}

    function render($mode, &$renderer, $data) {
        if ($mode == 'xhtml') {
            $renderer->info['cache'] = false;
            list($abstract, $border, $cols, $index, $proper, $title, $noAtoZ) = $data;

            require_once(DOKU_PLUGIN . 'subjectindex/inc/common.php');
            $subject_idx = file(get_subj_index($this->getConf('subjectindex_data_dir')));
            if (empty($subject_idx)) {
                $renderer->doc .= $this->getLang('empty_index');
                return false;
            }
            require_once (DOKU_INC . 'inc/indexer.php');
            $page_idx = idx_getIndex('page', '');

            list($lines, $heights) = $this->_create_index($index, $subject_idx, $page_idx, $noAtoZ);
            $renderer->doc .= $this->_render_index($lines, $heights, $cols, $border, $proper, $title, $abstract);
        } else {
            return false;
        }
    }

    private function _create_index($index, $subject_idx, $page_idx, $noAtoZ) {
        // grab only items for chosen subject index number
        $subject_idx = preg_grep('/^' . $index . '.+/', $subject_idx);

        // ratio of different heading heights (%), to ensure more even use of columns (h1 -> h6)
        $ratios = array(1.3, 1.17, 1.1, 1.03, .96, .90);

        $prev_entry = array();
        $lines = array();
        $heights = array();
        $links = array();

        // first build a list of valid subject entries to be rendered, plus their heights
        list($next_entry, $next_pid) = $this->_split_entry(current($subject_idx));
        do {
            $entry = $next_entry;
            $pid = $next_pid;

            $next = next($subject_idx);
            if ($next !== false) {
                list($next_entry, $next_pid) = $this->_split_entry($next);
            } else {
                $next_entry = '';
                $next_pid = '';
            }

            $page = rtrim($page_idx[intval($pid)], "\n\r");
            if ( ! valid_page($page)) continue;

            // split current entry into levels
            // a-z, header1, header2 etc...
            $cur_levels = explode('/', $entry);
            if ( ! $noAtoZ) {
                $az = array(strtoupper($entry[0]));
                $cur_levels = array_merge($az, $cur_levels); // [0] => A-Z heading
            }
            $max_level = count($cur_levels) - 1;   // array pos of last entry level
            for ($i = 0; $i <= $max_level; $i++) {
                // we can add the page link only if this is the final level
                $is_link = ($max_level == $i);
                $cur_level = $cur_levels[$i];

                if ($is_link) {
                    $links[] = $page;
                }
                // all comparison are caseless (this is an A-Z index after all!)
                $next_is_different = strcasecmp($entry, $next_entry) != 0;

                // we only make headings that are different from the previous
                $is_new_level = ! isset($prev_levels[$i]) || $cur_level != $prev_levels[$i];

                // only render if this is a new level than previous,
                // and next line is different; this ensures that links will be grouped!
                if ($next_is_different && $is_new_level) {
                    $lvl = ($i > 5) ? 6 : $i + 1; // html heading number 1-6 (forgive the magic no's)
                    if ($proper) $cur_level = ucwords($cur_level);
                    if ($is_link) {
                        $anchor = clean_id($entry);
                        $lines[] = array($lvl, $cur_level, $links, $anchor);
                        $links = array();
                    } else {
                        $lines[] = array($lvl, $cur_level, '' ,'');
                    }
                    $heights[] = $ratios[$lvl] - 1;
                }
            }
            if ($next_is_different) {
                $prev_levels = $cur_levels;
            }
        } while ($next !== false);

        return array($lines, $heights);
    }

    private function _render_index($lines, $heights, $cols, $border, $proper, $title, $abstract) {
        // try to get a realistic column height, based on all headers
        $col_height = array_sum($heights) / $cols;
        $height = current($heights);
        $prev_was_link = true;
        $links = '';

        $width = floor(100 / $cols);

        // now render the subject index table

        $noborder_css = ' class="noborder" ';
        $border_style = ($border == 'outside' || $border == 'both') ? '' : $noborder_css;
        // fixed point to jump back to at top of the table
        $top_id = 'top-' . mt_rand();
        $render = '<div id="subjectindex"' . $border_style . '>' . DOKU_LF;
        $render .= '<table id="' . $top_id . '">' . DOKU_LF;

        foreach ($lines as $line) {
            // are we ready to start a new column? (up to max allowed)
            if ($col == 0 || ( ! $new_col && ($col < $cols && $cur_height > $col_height))) {
                $cur_height = 0;
                $new_col = true;
                $col++;
            }
            // new column, start only at headings for clarity (not at page links!)
            if ($new_col && $prev_was_link) {
                $border_style = ($border == 'inside' || $border == 'both') ? '' : $noborder_css;
                // close the previous column if necessary
                $close = ($idx > 0) ? '</td>' . DOKU_LF : '';
                $render .= $close . '<td' . $border_style . ' valign="top"   width="' . $width . '%">' . DOKU_LF;
                $new_col = false;
            }
            // render each entry line
            list($lvl, $cur_level, $pages, $anchor) = $line;
            $indent_css = ' style="margin-left:' . ($lvl - 1) * 10 . 'px"';
            $entry = "<h$lvl$indent_css";
            // render page links
            if ( ! empty($pages)) {
                $cnt = 0;
                $freq = '';
                foreach($pages as $page) {
                    if ( ! empty($links)) $links .= ' | ';
                    $links .= $this->_render_wikilink($page, $proper, $title, $abstract, $anchor);
                    $cnt++;
                }
                if ($cnt > 1) $freq = '<span class="frequency">' . count($pages) . '</span>';
                $anchor = ' id="' . $anchor . '"';
                $entry .= "$entry$anchor>$cur_level$freq" . '<span class="links">' . "$links</span></h$lvl>";
                $prev_was_link = true;
                $links = '';
            // render headings
            } else {
                $entry .= "$entry>$cur_level</h$lvl>";
                $prev_was_link = false;
            }
            $render .= $entry . DOKU_LF;

            $cur_height += $height;
            $height = next($heights);
        }
        $render .= '</td></table>' . DOKU_LF;
        $render .= '<a class="top" href="#' . $top_id . '">' . $this->getLang('link_to_top') . '</a>';
        $render .= '</div>' . DOKU_LF;
        return $render;
    }

    private function _split_entry($entry) {
        list($text, $pid) = explode('|', $entry);
        // remove the index page number
        list($_, $text) = explode('/', $text, 2);
        return array($text, $pid);
    }

    /**
     * Renders a complete page link, plus tooltip, abstract, casing, etc...
     * @param string $id
     * @param bool  $proper
     * @param bool  $title
     * @param mixed $abstract
     */
    private function _render_wikilink($id, $proper, $title, $abstract, $anchor) {

        $id = (strpos($id, ':') === false) ? ':' . $id : $id;   // : needed for root pages

        // does the user want to see the "title" instead "pagename"
        if ($title) {
            $value = p_get_metadata($id, 'title', true);
            $name = (empty($value)) ? $this->_proper(noNS($id)) : $value;
        } elseif ($proper) {
            $name = $this->_proper(noNS($id));
        } else {
            $name = '';
        }

        // show the "abstract" as a tooltip
        $link = html_wikilink($id, $name);
        $link = $this->_add_page_anchor($link, $anchor);
        if ($abstract) {
            $link = $this->_add_tooltip($link, $id);
        }
        return $link;
    }

    private function _proper($id) {
         $id = str_replace(':', ': ', $id);
         $id = str_replace('_', ' ', $id);
         $id = ucwords($id);
         $id = str_replace(': ', ':', $id);
         return $id;
    }

    /**
     * swap normal link title (popup) for a more useful preview
     *
     * @param string $id    page id
     * @param string $name  display name
     * @return complete href link
     */
    private function _add_tooltip($link, $id) {
        $tooltip = $this->_abstract($id);
        if (!empty($tooltip)) {
            $tooltip = str_replace("\n", '  ', $tooltip);
            $link = preg_replace('/title=\".+?\"/', 'title="' . $tooltip . '"', $link, 1);
        }
        return $link;
    }

    private function _abstract($id) {
        $meta = p_get_metadata($id, 'description abstract', true);
        return htmlspecialchars($meta, ENT_IGNORE, 'UTF-8');
    }

    private function _add_page_anchor($link, $anchor) {
        $link = preg_replace('/\" class/', '#' . $anchor . '" class', $link, 1);
        return $link;
    }
}