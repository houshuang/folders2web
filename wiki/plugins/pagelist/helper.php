<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Esther Brunner <wikidesign@gmail.com>
 * @author     Gina Häußge <osd@foosel.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');

class helper_plugin_pagelist extends DokuWiki_Plugin {

    /* public */

    var $page       = NULL;    // associative array for page to list
    // must contain a value to key 'id'
    // can contain: 'title', 'date', 'user', 'desc', 'comments',
    // 'tags', 'status' and 'priority'

    var $style      = '';      // table style: 'default', 'table', 'list'
    var $showheader = false;   // show a heading line
    var $column     = array(); // which columns to show
    var $header     = array(); // language strings for table headers
	var $sort       = false;   // alphabetical sort of pages by pagename
	
    var $plugins    = array(); // array of plugins to extend the pagelist
    var $discussion = NULL;    // discussion class object
    var $tag        = NULL;    // tag class object

    var $doc        = '';      // the final output XHTML string

    /* private */

    var $_meta      = NULL;    // metadata array for page

    /**
     * Constructor gets default preferences
     *
     * These can be overriden by plugins using this class
     */
    function helper_plugin_pagelist() {
        $this->style      = $this->getConf('style');
        $this->showheader = $this->getConf('showheader');
        $this->showfirsthl    = $this->getConf('showfirsthl');
		$this->sort       = $this->getConf('sort');
		
        $this->column = array(
                'page'     => true,
                'date'     => $this->getConf('showdate'),
                'user'     => $this->getConf('showuser'),
                'desc'     => $this->getConf('showdesc'),
                'comments' => $this->getConf('showcomments'),
                'linkbacks'=> $this->getConf('showlinkbacks'),
                'tags'     => $this->getConf('showtags'),
                );

        $this->plugins = array(
                'discussion' => 'comments',
                'linkback'   => 'linkbacks',
                'tag'        => 'tags',
                );
    }

    function getMethods() {
        $result = array();
        $result[] = array(
                'name'   => 'addColumn',
                'desc'   => 'adds an extra column for plugin data',
                'params' => array(
                    'plugin name' => 'string',
                    'column key' => 'string'),
                );
        $result[] = array(
                'name'   => 'setFlags',
                'desc'   => 'overrides standard values for showfooter and firstseconly settings',
                'params' => array('flags' => 'array'),
                'return' => array('success' => 'boolean'),
                );
        $result[] = array(
                'name'   => 'startList',
                'desc'   => 'prepares the table header for the page list',
                );
        $result[] = array(
                'name'   => 'addPage',
                'desc'   => 'adds a page to the list',
                'params' => array("page attributes, 'id' required, others optional" => 'array'),
                );
        $result[] = array(
                'name'   => 'finishList',
                'desc'   => 'returns the XHTML output',
                'return' => array('xhtml' => 'string'),
                );
        return $result;
    }

    /**
     * Adds an extra column for plugins
     */
    function addColumn($plugin, $col) {
        $this->plugins[$plugin] = $col;
        $this->column[$col] = true;
    }

    /**
     * Overrides standard values for style, showheader and show(column) settings
     */
    function setFlags($flags) {
        if (!is_array($flags)) return false;

        $columns = array('date', 'user', 'desc', 'comments', 'linkbacks', 'tags');
        foreach ($flags as $flag) {
            switch ($flag) {
                case 'default':
                    $this->style = 'default';
                    break;
                case 'table':
                    $this->style = 'table';
                    break;
                case 'list':
                    $this->style = 'list';
                    break;
                case 'header':
                    $this->showheader = true;
                    break;
                case 'noheader':
                    $this->showheader = false;
                    break;
                case 'firsthl':
                    $this->showfirsthl = true;
                    break;
                case 'nofirsthl':
                    $this->showfirsthl = false;
                    break;
                case 'sort':
                	$this->sort = true;
                	break;
                case 'nosort':
                	$this->sort = false;
                	break;
            }

            if (substr($flag, 0, 2) == 'no') {
                $value = false;
                $flag  = substr($flag, 2);
            } else {
                $value = true;
            }
            
            if (in_array($flag, $columns)) $this->column[$flag] = $value;
        }
        return true;
    }

    /**
     * Sets the list header
     */
    function startList() {

        // table style
        switch ($this->style) {
            case 'table':
                $class = 'inline';
                break;
            case 'list':
                $class = 'ul';
                break;
            default:
                $class = 'pagelist';
        }
        $this->doc = '<table class="'.$class.'">'.DOKU_LF;
        $this->page = NULL;

        // check if some plugins are available - if yes, load them!
        foreach ($this->plugins as $plug => $col) {
            if (!$this->column[$col]) continue;
            if (plugin_isdisabled($plug) || (!$this->$plug = plugin_load('helper', $plug)))
                $this->column[$col] = false;
        }

        // header row
        if ($this->showheader) {
            $this->doc .= DOKU_TAB.'<tr>'.DOKU_LF.DOKU_TAB.DOKU_TAB;
            $columns = array('page', 'date', 'user', 'desc');
            foreach ($columns as $col) {
                if ($this->column[$col]) {
                    if (!$this->header[$col]) $this->header[$col] = hsc($this->getLang($col));
                    $this->doc .= '<th class="'.$col.'">'.$this->header[$col].'</th>';
                }
            }
            foreach ($this->plugins as $plug => $col) {
                if ($this->column[$col]) {
                    if (!$this->header[$col]) $this->header[$col] = hsc($this->$plug->th());
                    $this->doc .= '<th class="'.$col.'">'.$this->header[$col].'</th>';
                }
            }
            $this->doc .= DOKU_LF.DOKU_TAB.'</tr>'.DOKU_LF;
        }
        return true;
    }

    /**
     * Sets a list row
     */
    function addPage($page) {

        $id = $page['id'];
        if (!$id) return false;
        $this->page = $page;
        $this->_meta = NULL;

        // priority and draft
        if (!isset($this->page['draft'])) {
            $this->page['draft'] = ($this->_getMeta('type') == 'draft');
        }
        $class = '';
        if (isset($this->page['priority'])) $class .= 'priority'.$this->page['priority']. ' ';
        if ($this->page['draft']) $class .= 'draft ';
        if ($this->page['class']) $class .= $this->page['class'];
        if(!empty($class)) $class = ' class="' . $class . '"';

        $this->doc .= DOKU_TAB.'<tr'.$class.'>'.DOKU_LF;

        $this->_pageCell($id);    
        if ($this->column['date']) $this->_dateCell();
        if ($this->column['user']) $this->_userCell();
        if ($this->column['desc']) $this->_descCell();
        foreach ($this->plugins as $plug => $col) {
            if ($this->column[$col]) $this->_pluginCell($plug, $col, $id);
        }

        $this->doc .= DOKU_TAB.'</tr>'.DOKU_LF;
        return true;
    }

    /**
     * Sets the list footer
     */
    function finishList() {
        if (!isset($this->page)) $this->doc = '';
        else $this->doc .= '</table>'.DOKU_LF;

        // reset defaults
        $this->helper_plugin_pagelist();

        return $this->doc;
    }

    /* ---------- Private Methods ---------- */

    /**
     * Page title / link to page
     */
    function _pageCell($id) {

        // check for page existence
        if (!isset($this->page['exists'])) {
            if (!isset($this->page['file'])) $this->page['file'] = wikiFN($id);
            $this->page['exists'] = @file_exists($this->page['file']);
        }
        if ($this->page['exists']) $class = 'wikilink1';
        else $class = 'wikilink2';

        // handle image and text titles
        if ($this->page['image']) {
            $title = '<img src="'.ml($this->page['image']).'" class="media"';
            if ($this->page['title']) $title .= ' title="'.hsc($this->page['title']).'"'.
                ' alt="'.hsc($this->page['title']).'"';
            $title .= ' />';
        } else {
            if (!$this->page['title']) {
                if($this->showfirsthl) {
                    $this->page['title'] = $this->_getMeta('title');
                } else {
                    $this->page['title'] = $this->id;
                }
            }
            if (!$this->page['title']) $this->page['title'] = str_replace('_', ' ', noNS($id));
            $title = hsc($this->page['title']);
        }

        // produce output
        $content = '<a href="'.wl($id).($this->page['section'] ? '#'.$this->page['section'] : '').
            '" class="'.$class.'" title="'.$id.'">'.$title.'</a>';
        if ($this->style == 'list') $content = '<ul><li>'.$content.'</li></ul>';
        return $this->_printCell('page', $content);
    }

    /**
     * Date - creation or last modification date if not set otherwise
     */
    function _dateCell() {    
        global $conf;

        if($this->column['date'] == 2) {
            $this->page['date'] = $this->_getMeta(array('date', 'modified'));
        } elseif(!$this->page['date'] && $this->page['exists']) {
            $this->page['date'] = $this->_getMeta(array('date', 'created'));
        }

        if ((!$this->page['date']) || (!$this->page['exists'])) {
            return $this->_printCell('date', '');
        } else {
            return $this->_printCell('date', strftime($conf['dformat'], $this->page['date']));
        }
    }

    /**
     * User - page creator or contributors if not set otherwise
     */
    function _userCell() {
        if (!array_key_exists('user', $this->page)) {
            if ($this->column['user'] == 2) {
                $users = $this->_getMeta('contributor');
                if (is_array($users)) $this->page['user'] = join(', ', $users);
            } else {
                $this->page['user'] = $this->_getMeta('creator');
            }
        }
        return $this->_printCell('user', hsc($this->page['user']));
    }

    /**
     * Description - (truncated) auto abstract if not set otherwise
     */
    function _descCell() {
        if (!array_key_exists('desc', $this->page)) {
            $desc = $this->_getMeta(array('description', 'abstract'));
        } else {
            $desc = $this->page['desc'];
        }
        $max = $this->column['desc'];
        if (($max > 1) && (utf8_strlen($desc) > $max)) $desc = utf8_substr($desc, 0, $max).'…';
        return $this->_printCell('desc', hsc($desc));
    }

    /**
     * Plugins - respective plugins must be installed!
     */
    function _pluginCell($plug, $col, $id) {
        if (!isset($this->page[$col])) $this->page[$col] = $this->$plug->td($id);
        return $this->_printCell($col, $this->page[$col]);
    }

    /**
     * Produce XHTML cell output
     */
    function _printCell($class, $content) {
        if (!$content) {
            $content = '&nbsp;';
            $empty   = true;
        } else {
            $empty   = false;
        }
        $this->doc .= DOKU_TAB.DOKU_TAB.'<td class="'.$class.'">'.$content.'</td>'.DOKU_LF;
        return (!$empty);
    }


    /**
     * Get default value for an unset element
     */
    function _getMeta($key) {
        if (!$this->page['exists']) return false;
        if (!isset($this->_meta)) $this->_meta = p_get_metadata($this->page['id']);
        if (is_array($key)) return $this->_meta[$key[0]][$key[1]];
        else return $this->_meta[$key];
    }

}
// vim:ts=4:sw=4:et: 
