<?php
/**
 * PageQuery Plugin: search for and list pages, sorted/grouped by name, date, creator, etc
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author	   Symon Bent <hendrybadao@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN . 'syntax.php');
require_once(DOKU_INC . 'inc/fulltext.php');
require_once(DOKU_PLUGIN . 'pagequery/inc/msort.php');

define ('ID_KEY', 0);
define ('NAME_KEY', 1);
define ('ABST_KEY', 2);
define ('START_KEY', 3);
define ('MAX_COLS', 6);

class syntax_plugin_pagequery extends DokuWiki_Syntax_Plugin {

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
		$this->Lexer->addSpecialPattern('\{\{pagequery>.*?\}\}', $mode, 'plugin_pagequery');
	}

    /**
     * Parses all the pagequery options:
     * Insert the pagequery markup wherever you want your list to appear. E.g:
     *
     *   {{pagequery>}}
     *
     *   {{pagequery>[query];fulltext;sort=key:direction,key2:direction;group;limit=??;cols=?;inwords;proper}}
     *
     * Parameters as follows:
     * 1. query:    any expression directly after the >; can use all Dokuwiki search options (see manual)
     * 2. fulltext: use a full-text search, instead of page_id only [default]
     * 3. sort:     keys to sort by, in order of sorting. Each key can be followed by prefered sorting order
     *              available keys:
     *                  a, ab, abc          by 1st letter, 2 letters, or 3 letters
     *                  name                by page name (no namespace) or 1st heading [not grouped]
     *                  page|id             by full page id, including namespace [not grouped]
     *                  ns                  by namespace (without page name)
     *                  mdate, cdate        by modified|created dates (full) [not grouped]
     *                  m[year][month][day] by modified [year][month][day]; any combination accepted
     *                  c[year][month][day] by created [year][month][day]; any combination accepted
     *                  creator             by page author
     *              date sort default to descending, string sorts to ascending
     * 4. group:    show group headers for each change in sort keys
     *              Note: keys with no duplicate cannot be grouped (i.e. name, page|id, mdate, cdate)
     * 5. limit:    maximum number of results to return
     * 6. inwords:  use real month and day names instead of numeric dates
     * 7. cols:     number of columns in displayed list (max = 6)
     * 8. proper:   display page names and namespace in Proper Case (i.e. no _'s and Capitalised)
     *              header/hdr = group headers only, name = page name only, both = both!
     * 9. border:   turn on borders. 'inside' = between columns; 'outside' => border around table;
     *              'both' => in and out; 'none' => neither
     *10. fullregex:only useful on page name searches; allows a raw regex mode on the full page id
     *11. nostart:  ignore any 'start' pages in namespace (based on "config:start")
     *12. maxns:    maximum namespace level to be displayed; e.g. maxns=3 => one:two:three
     *13. title:    show 1st page heading instead of page name
     *14. snippet:  should an excerpt of the wikipage be shown:
     *              use :tooltip to show as a pop-up only
     *              use :<inline|plain|quoted>, <count>, <extent> to show 1st <count> items in list with an abstract
     *                  extent always choice of chars, words, lines, or find (c? w? l? ~????)
     *15. natsort:  use natural sorting order (good for words beginning with numbers)
     *16. case:     respect case when sorting, i.e. a != A when sorting.  a-z then A-Z (opp. to PHP term, easier on average users)
     *17. underline:show a faint underline between each link for clarity
     *
     * All options are optional, and the list will default to a boring long 1-column list...
     */
	function handle($match, $state, $pos, &$handler) {

		$match = substr($match, 12, -2); // strip markup "{{pagequery>"
		$options = explode(';', $match);
        $query = $options[0];

        // establish some basic option defaults
        $sort = array();
        $fulltext = false;
        $group = false;
        $limit = 0;
        $maxns = 0;
        $cols = 1;
        $proper = 'none';
        $border = 'none';
        $snippet = array('none');
        $title = false;
        $fullregex = false;
        $case = false;
        $natsort = false;
        $underline = false;

        foreach ($options as $option) {
            list($key, $value) = explode('=', $option);
            switch ($key) {
                case 'fulltext':
                    $fulltext = true;
                    break;
                case 'sort':
                    $values = explode(',', $value);
                    foreach ($values as $value) {
                        list($key, $dir) = explode(':', $value);
                        $sort[] = array($key, $dir);
                    }
                    break;
                case 'group':
                    $group = true;
                    break;
                case 'limit':
                    $limit = $value;
                    break;
                case 'maxns':
                    $maxns = $value;
                    break;
                case 'inwords':
                    $inwords = true;
                    break;
                case 'proper':
                    switch ($value) {
                        case 'hdr':
                        case 'header':
                        case 'group':
                            $proper = 'header';
                            break;
                        case 'name':
                        case 'page':
                            $proper = 'name';
                            break;
                        default:
                            $proper = 'both';
                    }
                    break;
                case 'cols':
                    $cols = ($value > MAX_COLS) ? MAX_COLS : $value;
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
				case 'fullregex':
					$fullregex = true;
					break;
                case 'nostart':
                    $nostart = true;
                    break;
                case 'title':
                    $title = true;
                    break;
                case 'abstract':    // old syntax, to be deprecated (2011-03-15)
                case 'snippet':
                    $opts = explode(',', $value);
                    $type = ( ! empty($opts[0])) ? $opts[0] : 'tooltip';
                    $valid = array('none', 'tooltip', 'inline', 'plain', 'quoted');
                    if ( ! in_array($type, $valid)) $type = 'tooltip';  // always valid!
                    $count = ( ! empty($opts[1])) ? $opts[1] : 0;
                    $extent = ( ! empty($opts[2])) ? $opts[2] : '';
                    $snippet = array($type, $count, $extent);
                    break;
                case 'case':
                    $case = true;
                    break;
                case 'natsort':
                    $natsort = true;
                    break;
                case 'underline':
                    $underline = true;
                    break;
            }
        }
		return array($query, $fulltext, $sort, $group, $limit, $maxns, $proper,
                      $cols, $inwords, $border, $fullregex, $nostart, $title,
                      $snippet, $case, $natsort, $underline);
	}

    function render($mode, &$renderer, $data) {
        $sortkeys = array();
        $incl_ns = array();
        $excl_ns = array();

        list($query, $fulltext, $sort, $group, $limit, $maxns, $proper,
              $cols, $inwords, $border, $fullregex, $nostart, $title,
              $snippet, $case, $natsort, $underline) = $data;

        if ($mode == 'xhtml') {
            // first get a raw list of matching results

            if ($fulltext) {
                // full text (Dokuwiki style) searching
                $results = array_keys(ft_pageSearch($query, $highlight));
            } else {
                // by page id only (
                if ($fullregex) {
                    // allow for raw regex mode, for power users, this searches the full page id
                    $pageonly = false;
                } else {
                    list($query, $incl_ns, $excl_ns) = $this->_parse_ns_query($query);
                    $pageonly = true;
                }
                if ($query == '*') $query = '.*';   // a lazy man's option!
                $results = $this->_page_lookup($query, $pageonly, $incl_ns, $excl_ns, $nostart, $maxns);
            }

            if ( ! empty($results)) {
                // this section is where the essential pagequery functionality happens...

                // prepare the necessary sorting arrays, as per users options
                $get_abstract = ($snippet[0] != 'none');
                list($sort_array, $sort_opts, $group_opts) =
                    $this->_build_sorting_array($results, $sort, $title, $proper, $inwords, $case, $natsort, $get_abstract);

                // now do the sorting (inc/msort.php)
                msort($sort_array, $sort_opts);
                // limit the result list length if required; this can only be done after sorting!
                $sort_array = ($limit > 0) ? array_slice($sort_array, 0, $limit) : $sort_array;

                // and finally the grouping
                if ($group) {
                    $keys = array(NAME_KEY, ID_KEY, ABST_KEY);
                    $sorted_results = mgroup($sort_array, $keys, $group_opts);
                } else {
                    foreach ($sort_array as $row) {
                        $sorted_results[] = array(0, $row[NAME_KEY], $row[ID_KEY], $row[ABST_KEY]);
                    }
                }
                $renderer->doc .= $this->_render_list($sorted_results, $cols, $proper, $snippet, $border, $underline);
            } else {
                $renderer->doc .= $this->_render_no_list($query);
            }
            return true;
        } else {
            return false;
        }
    }

    private function _adjusted_height($sorted_results, $ratios) {
        // ratio of different heading heights (%), to ensure more even use of columns (h1 -> h6)
        foreach ($sorted_results as $row) {
            $adjusted_height += $ratios[$row[0]];
        }
        return $adjusted_height;
    }

    /**
     * Render a simple "no results" message
     *
     * @param string $query => original query
     * @return string
     */
    private function _render_no_list($query) {
        $render = '<div id="pagequery" class="noborder">' . DOKU_LF;
        $render .= '<p class="noresults"><span>pagequery</span>' . $this->getLang("no_results") .
                                  '&nbsp; <strong>' . $query . '</strong></p>' . DOKU_LF;
        $render .= '</div>' . DOKU_LF;
        return $render;
    }
    /**
     * Render the final pagequery results list as HTML, indented and in columns as required
     *
     * @param array  $sorted_results
     * @param int    $cols
     * @param bool   $proper
     * @param string $snippet
     * @param string $border
     * @return string => HTML rendered list
     */
    private function _render_list($sorted_results, $cols, $proper, $snippet, $border, $underline) {
        $ratios = array(.80, 1.3, 1.17, 1.1, 1.03, .96, .90);   // height ratios: link, h1, h2, h3, h4, h5, h6
        $render = '';
        $prev_was_heading = false;
        $can_start_col = true;
        $cont_level = 1;
        $col = 0;
        $col_height = $this->_adjusted_height($sorted_results, $ratios) / $cols;
        $cur_height = 0;
        $width = floor(100 / $cols);
        $snippet_cnt = 0;    // needed by the snippet section for tracking
        $jump_txt= $this->getLang('jump_section');
        $is_first = true;

        // basic result page markup (always needed)
        $outer_border = ($border == 'outside' || $border == 'both') ? '' : ' noborder';
        $no_table = ($cols == 1) ? ' notable' : '';
        $top_id = 'top-' . mt_rand();   // fixed anchor point to jump back to at top of the table
        $render .= '<div class="pagequery' . $outer_border . $no_table . '" id="' . $top_id . '">' . DOKU_LF;
        if ($cols > 1) $render .= '<table><tbody><tr>' . DOKU_LF;

        $inner_border = ($border == 'inside' || $border == 'both') ? '' : ' class="noborder" ';

        // now render the pagequery list
        foreach ($sorted_results as $line) {
            list($level, $name, $id, $abstract) = $line;
            $is_heading = ($level > 0);

            // is it time to start a new column?
            if ($can_start_col === false && $col < $cols && $cur_height >= $col_height) {
                $can_start_col = true;
                $col++;
            }

            // how should headings be displayed?
            if ($is_heading) {
                $heading = $name;
                if ($proper == 'header' || $proper == 'both') $heading = $this->_proper($heading);
            }

            // no need for indent if there is no grouping
            if ($group === false) {
                $indent_style = ' class="nogroup"';
            } else {
                $indent = ($is_heading) ? $level - 1 : $cont_level - 1;
                $indent_style = ' style="margin-left:' . $indent * 10 . 'px"';
            }

            // Begin new column if: 1) we are at the start, 2) last item was not a heading or 3) if there is no grouping
            if ($can_start_col && ! $prev_was_heading) {
                $jump_tip = sprintf($jump_txt, $heading);
                // close the previous column if necessary; also adds a 'jump to anchor'
                $col_close = ( ! $is_heading) ? '<a title="'. $jump_tip . '" href="#' .
                                $top_id . '">' . "<h$cont_level>§... </h$cont_level></a>" : '';
                $col_close = ( ! $is_first) ? $col_close . '</ul></td>' . DOKU_LF : '';
                $col_open = ( ! $is_first && ! $is_heading) ? "<h$cont_level$indent_style>" . "$heading...</h$cont_level>" : '';
                $td = ($cols > 1) ? '<td' . $inner_border . ' valign="top" width="' . $width . '%">' : '';
                $render .= $col_close . $td . $col_open . DOKU_LF;
                $can_start_col = false;
                $prev_was_heading = true;    // needed to correctly style page link lists <ul>...
                $cur_height = 0;
            }
            // finally display the appropriate heading or page link(s)
            if ($is_heading) {
                // close previous sub list if necessary
                if ( ! $prev_was_heading) {
                    $render .= '</ul>' . DOKU_LF;
                }
                $render .= "<h$level$indent_style>$heading</h$level>" . DOKU_LF;
                $prev_was_heading = true;
                $cont_level = $level + 1;
            } else {
                // open a new sub list if necessary
                if ($prev_was_heading || $is_first) {
                    $render .= "<ul$indent_style>";
                }
                // deal with normal page links
                $link = $this->_html_wikilink($id, $name, $snippet, $snippet_cnt, $abstract, $underline);
                $snippet_cnt++;
                $render .= $link;
                $prev_was_heading = false;
            }
            $cur_height += $ratios[$level];
            $is_first = false;
        }
        $render .= '</ul>' . DOKU_LF;
        if ($cols > 1) $render .= '</td></tr></tbody></table>' . DOKU_LF;
        $render .= '<a class="top" href="#' . $top_id . '">' . $this->getLang('link_to_top') . '</a>' . DOKU_LF;
        $render .= '</div>' . DOKU_LF;

        return $render;
    }

    /**
     * Renders the page link, plus tooltip, abstract, casing, etc...
     * @param string $id
     * @param bool  $proper
     * @param bool  $title
     * @param mixed $snippet
     * @param int   $snipet_cnt
     * @param bool  $underline
     */
    private function _html_wikilink($id, $name, $snippet_opt, $snippet_cnt, $abstract, $underline) {

        $id = (strpos($id, ':') === false) ? ':' . $id : $id;   // : needed for root pages (root level)

        list($type, $max, $extent) = $snippet_opt;
        $after= '';
        $inline = '';

        if ($type == 'none') {
            // Plain old wikilink
            $link = html_wikilink($id, $name);
        } else {
            $short = $this->_shorten($abstract, $extent);   // shorten BEFORE replacing html entities!
            $short = htmlentities($short, ENT_QUOTES, 'UTF-8');
            $abstract = htmlentities($abstract, ENT_QUOTES, 'UTF-8');
            $link = html_wikilink($id, $name);
            $no_snippet = ($max > 0 && $snippet_cnt >= $max);
            if ($type == 'tooltip' || $no_snippet) {
                $link = $this->_add_tooltip($link, $abstract);
            } elseif ($type == 'quoted' || $type == 'plain') {
                $more = html_wikilink($id, 'more');
                $after = trim($short);
                $after= str_replace("\n\n", "\n", $after);
                $after= str_replace("\n", '<br/>', $after);
                $after= '<div class="' . $type . '">' . $after . $more . '</div>' . DOKU_LF;
            } elseif ($type == 'inline') {
                $inline .= '<span>' . $short . '</span>';
            }
        }
        $noborder = ($underline) ? '' : ' class="noborder"';
        return "<li$noborder>" . $link . $inline . '</li>' . DOKU_LF . $after;
    }

    /**
     * Swap normal link title (popup) for a more useful preview
     *
     * @param string $id    page id
     * @param string $name  display name
     * @return complete href link
     */
    private function _add_tooltip($link, $tooltip) {
        $tooltip = str_replace("\n", '  ', $tooltip);
        $link = preg_replace('/title=\".+?\"/', 'title="' . $tooltip . '"', $link, 1);
        return $link;
    }

    /**
     * return the first part of the $text according to the $amount given
     * @param type $text
     * @param type $amount  c? = ? chars, w? = ? words, l? = ? lines, ~? = search up to text/char/symbol
     */
    private function _shorten($text, $extent, $more = '... ') {
        $elem = $extent[0];
        $cnt = substr($extent, 1);
        switch ($elem) {
            case 'c':
                $result = substr($text, 0, $cnt);
                if ($cnt > 0 && strlen($result) < strlen($text)) $result .= $more;
                break;
            case 'w':
                $words = str_word_count($text, 1, '.');
                $result = implode(' ', array_slice($words, 0, $cnt));
                if ($cnt > 0 && $cnt <= count($words) && $words[$cnt - 1] != '.') $result .= $more;
                break;
            case 'l':
                $lines = explode("\n", $text);
                $lines = array_filter($lines);  // remove blank lines
                $result = implode("\n", array_slice($lines, 0, $cnt));
                if ($cnt > 0 && $cnt < count($lines)) $result .= $more;
                break;
            case "~":
                $result = strstr($text, $cnt, true);
                break;
            default:
                $result = $text;
        }
        return $result;
    }

    /**
     * Changes a wiki page id into proper case (allowing for :'s etc...)
     * @param string    $id    page id
     * @return string
     */
    private function _proper($id) {
         $id = str_replace(':', ': ', $id); // make a little whitespace before words so ucwords can work!
         $id = str_replace('_', ' ', $id);
         $id = ucwords($id);
         $id = str_replace(': ', ':', $id);
         return $id;
    }

    /**
     * Parse out the namespace, and convert to a regex for array search
     *
     * @param  string $query user page query
     * @return string        processed query with necessary regex markup for namespace recognition
     */
    private function _parse_ns_query($query) {
        global $INFO;

        $cur_ns = $INFO['namespace'];
        $incl_ns = array();
        $excl_ns = array();
        $page_qry = '';
        $tokens = explode(' ', $query);
        if (count($tokens) == 1) {
            $page_qry = $query;
        } else {
            foreach ($tokens as $token) {
                if (preg_match('/^(?:\^|-ns:)(.+)$/u', $token, $matches)) {
                    $excl_ns[] = resolve_id($cur_ns, $matches[1]);  // also resolve relative and parent ns
                } elseif (preg_match('/^(?:@|ns:)(.+)$/u', $token, $matches)) {
                    $incl_ns[] = resolve_id($cur_ns, $matches[1]);
                } else {
                    $page_qry .= ' ' . $token;
                }
            }
        }
        $page_qry = trim($page_qry);
        return array($page_qry, $incl_ns, $excl_ns);
    }

    /**
     * Builds the sorting array: array of arrays (0 = id, 1 = name, 2 = abstract, 3 = ... , etc)
     *
     * @param array     $ids        array of page ids to be sorted
     * @param array     $sortkeys   list of array keys to sort by
     * @param bool      $title      use page heading instead of name for sorting
     * @param bool      $proper     use proper case where possible
     * @param bool      $inwords    show dates in words where possible
     * @param bool      $case       honour case when sorting
     * @param bool      $natsort    natural sorting, the human way
     *
     * @return array    $sort_array array of array(one value for each key to be sorted)
     *                   $sort_opts  sorting options for the msort function
     *                   $group_opts grouping options for the mgroup function
     */
    private function _build_sorting_array($ids, $sortkeys, $title, $proper, $inwords, $case, $natsort, $get_abstract) {
        global $conf;

        $sort_opts = array();
        $group_opts = array();
        $abstracts = array();

        $dformat = array();
        $wformat = array();

        $row = 0;
        $end_col = START_KEY + count($sortkeys);

        foreach ($ids as $id) {
            // getting metadata is time-consuming, hence ONCE per displayed row
            $meta = p_get_metadata ($id, false, true);

            if ( ! isset($meta['date']['modified'])) {
                $meta['date']['modified'] = $meta['date']['created'];
            }
            // use page heading instead of page name
            if ($title && isset($meta['title'])) {
                $name = $meta['title'];
            } else {
                $name = noNS($id);
            }
            if ($proper == 'name' || $proper == 'both') {
                $name = $this->_proper($name);
            }
            // first column is the basic page id
            $sort_array[$row][ID_KEY] = $id;
            // second column is the display 'name' (used when sorting by 'name')
            // this also avoids rebuilding the display name when building links later (DRY)
            $sort_array[$row][NAME_KEY] = $name;
            // third column: cache the page abstract if needed; this saves a lot of time later
            // and avoids repeated slow metadata retrievals (v. slow!)
            $sort_array[$row][ABST_KEY] = ($get_abstract) ? $meta['description']['abstract'] : '';

            $col = START_KEY;

            foreach ($sortkeys as $sortkey) {
                $key = $sortkey[0];
                switch ($key) {
                    case 'a':
                        $value = $this->_first($name, 1);
                        break;
                    case 'ab':
                        $value = $this->_first($name, 2);
                        break;
                    case 'abc':
                        $value = $this->_first($name, 3);
                        break;
                    case 'name':
                        // a name column already exists by default (col 1)
                        continue;
                    case 'id':
                    case 'page':
                        $value = $id;
                        break;
                    case 'ns':
                        $value = getNS($id);
                        if (empty($value)) $value = '[' . $conf['start'] . ']';
                        break;
                    case 'creator':
                        $value = $meta['creator'];
                        break;
                    case 'mdate':
                        $value = $meta['date']['modified'];
                        break;
                    case 'cdate':
                        $value = $meta['date']['created'];
                        break;
                    default:
                        // date sorting types (groupable)
                        $dtype = $key[0];
                        if ($dtype == 'c' || $dtype == 'm') {
                            // we only set real date once per id (needed for grouping)
                            // not per sort column--the date should remain same across all columns
                            // this is always the last column!
                            if ($col == START_KEY) {
                                if ($dtype == 'c') {
                                    $date = $meta['date']['created'];
                                } else {
                                    $date = $meta['date']['modified'];
                                }
                                $sort_array[$row][$end_col] = $date;
                            } else {
                                $date = $sort_array[$row][$end_col] ;
                            }
                            // only set date formats once per sort column/key (not per id!)
                            if ($row == 0) {
                                $dformat[$col] = $this->_date_format($key);
                                // collect date in word format for potential use in grouping
                                if ($inwords) {
                                    $wformat[$col] = $this->_date_format_words($dformat[$col]);
                                } else {
                                    $wformat[$col] = '';
                                }
                            }
                            // create a string date used for sorting only
                            // (we cannot just use the real date otherwise it would not group correctly)
                            $value = strftime($dformat[$col], $date);
                        }
                }
                $sort_array[$row][$col] = $value;
                $col++;
            }
            $row++;
        }

        $cnt = START_KEY;
        $order = 0;
        foreach ($sortkeys as $sortkey) {
            list($key, $opt) = $sortkey;

            if ($key == 'name') {
                $col = NAME_KEY;
                // this col number will be re-used next time through
            } else {
                $col = $cnt;
                $cnt++;
            }
            $sort_opts['key'][] = $col;

            // now the sort direction
            switch ($opt) {
                case 'a':
                case 'asc':
                    $dir = MSORT_ASC;
                    break;
                case 'd':
                case 'desc':
                    $dir = MSORT_DESC;
                    break;
                default:
                    // sort descending by default; text ascending
                    // watch for other sort options beginning with c or m ....!
                    if ($key[0] == 'c' || $key[0] == 'm') {
                        $dir = MSORT_DESC;
                    } else {
                        $dir = MSORT_ASC;
                    }
            }
            $sort_opts['dir'][] = $dir;

            // set the sort array's data type
            $is_ns = false;
            switch ($key) {
                case 'mdate':
                case 'cdate':
                    $type = MSORT_NUMERIC;
                    break;
                default:
                    if ($case) {
                        // case sensitive: a-z then A-Z
                        $type = ($natsort) ? MSORT_NAT : MSORT_STRING;
                    } else {
                        // case-insensitive
                        $type = ($natsort) ? MSORT_NAT_CASE : MSORT_STRING_CASE;
                    }
            }
            $sort_opts['type'][] = $type;

            // now establish grouping options
            switch ($key) {
                // name strings and full dates cannot be meaningfully grouped (no duplicates!)
                case 'mdate':
                case 'cdate':
                case 'name':
                case 'id':
                case 'page':
                    $group_by = MGROUP_NONE;
                    break;
                case 'ns':
                    $group_by = MGROUP_NAMESPACE;
                    break;
                default:
                    $group_by = MGROUP_HEADING;
            }
            if ($group_by != MGROUP_NONE) {
                $group_opts['key'][$order] = $col;
                $group_opts['type'][$order] = $group_by;
                $group_opts['dformat'][$order] = $wformat[$col];
                $order++;
            }
        }
        return array($sort_array, $sort_opts, $group_opts, $abstracts);
    }

    // returns first $count letters from $text
    private function _first($text, $count) {
        if ($count > 0) {
            return utf8_substr($text, 0, $count);
        }
    }

    /**
     * Parse the c|m-year-month-day option; used for sorting/grouping
     *
     * @param string  $key
     * @return string
     */
    private function _date_format($key) {
        if (strpos($key, 'year') !== false) $dkey[] = '%Y';
        if (strpos($key, 'month') !== false) $dkey[] = '%m';
        if (strpos($key, 'day') !== false) $dkey[] = '%d';
        $dformat = implode('-', $dkey);
        return $dformat;
    }

    /**
     * Provide month and day format in real words if required
     * used for display only ($dformat is used for sorting/grouping)
     *
     * @param string $dformat
     * @return string
     */
    private function _date_format_words($dformat) {
        $wformat = '';
        switch ($dformat) {
            case '%m':
                $wformat = "%B";
                break;
            case '%d':
                $wformat = "%#d–%A ";
                break;
            case '%Y-%m':
                $wformat = "%B %Y";
                break;
            case '%m-%d':
                $wformat= "%B %#d, %A ";
                break;
            case '%Y-%m-%d':
                $wformat = "%A, %B %#d, %Y";
                break;
        }
        return $wformat;
    }

    /**
     * A heavily customised version of _ft_pageLookup in inc/fulltext.php
     * no sorting!
     */
    private function _page_lookup($query, $pageonly, $incl_ns, $excl_ns, $nostart = true, $maxns = 0) {
        global $conf;

        $queries = trim($query);
        $pages = file($conf['indexdir'] . '/page.idx');

        // first deal with excluded namespaces, then included
        $pages = $this->_filter_ns($pages, $excl_ns, true);

        // now include ONLY the selected namespaces if provided
        $pages = $this->_filter_ns($pages, $incl_ns, false);

        $cnt = count($pages);
        for ($i = 0; $i < $cnt; $i++) {
            $page = $pages[$i];
            if ( ! page_exists($page) || isHiddenPage($page)) {
                unset($pages[$i]);
                continue;
            }
            if ($pageonly) $page = noNS($page);
            /*
             * This is the actual "search" expression!
             * Note: preg_grep cannot be used because of the pageonly option above
             *       (needs to allow for "^" syntax)
             * The @ prevents problems with invalid queries!
             */
            if (@preg_match('/' . $query . '/i', $page) == 0) {
                unset($pages[$i]);
            }
        }
        if ( ! count($pages)) return array();

        $pages = array_map('trim',$pages);

        // check ACL permissions and remove any 'start' pages if req'd
        $start = $conf['start'];
        $pos = strlen($start);
        foreach($pages as $idx => $name) {
            if ($nostart && substr($name, -$pos) == $start) {
                unset($pages[$idx]);
            // TODO: this function is one of slowest in the plugin; solutions?
            } elseif(auth_quickaclcheck($pages[$idx]) < AUTH_READ) {
                unset($pages[$idx]);
            } elseif ($maxns > 0 && (substr_count($name,':') + 1) > $maxns) {
                unset($pages[$idx]);
            }
        }
        return $pages;
    }
    /**
     * Include/Exclude specific namespaces from a list of pages
     * @param type $pages   a list of wiki page ids
     * @param type $ns_qry  namespace(s) to include/exclude
     * @param type $exclude true = exclude
     * @return array
     */
    private function _filter_ns($pages, $ns_qry, $exclude) {
        $invert = ($exclude) ? PREG_GREP_INVERT : 0;
        foreach ($ns_qry as $ns) {
            $regexes[] = '.*' . $ns . ':.*';
        }
        if ( ! empty($regexes)) {
            $regex = '/(' . implode('|', $regexes) . ')/';
            return array_values(preg_grep($regex, $pages, $invert));
        } else {
            return $pages;
        }
    }
}
?>
