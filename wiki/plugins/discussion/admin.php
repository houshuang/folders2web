<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Esther Brunner <wikidesign@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'admin.php');

class admin_plugin_discussion extends DokuWiki_Admin_Plugin {

    function getInfo() {
        return array(
                'author' => 'Gina Häußge, Michael Klier, Esther Brunner',
                'email'  => 'dokuwiki@chimeric.de',
                'date'   => @file_get_contents(DOKU_PLUGIN.'discussion/VERSION'),
                'name'   => 'Discussion Plugin (admin component)',
                'desc'   => 'Moderate discussions',
                'url'    => 'http://wiki.splitbrain.org/plugin:discussion',
                );
    }

    function getMenuSort() { return 200; }
    function forAdminOnly() { return false; }

    function handle() {
        global $lang;

        $cid = $_REQUEST['cid'];
        if (is_array($cid)) $cid = array_keys($cid);

        $action =& plugin_load('action', 'discussion');
        if (!$action) return; // couldn't load action plugin component

        switch ($_REQUEST['comment']) {
            case $lang['btn_delete']:
                $action->_save($cid, '');
                break;

            case $this->getLang('btn_show'):
                $action->_save($cid, '', 'show');
                break;

            case $this->getLang('btn_hide'):
                $action->_save($cid, '', 'hide');
                break;

            case $this->getLang('btn_change'):
                $this->_changeStatus($_REQUEST['status']);
                break;
        }
    }

    function html() {
        global $conf;

        $first = $_REQUEST['first'];
        if (!is_numeric($first)) $first = 0;
        $num = ($conf['recent']) ? $conf['recent'] : 20;

        ptln('<h1>'.$this->getLang('menu').'</h1>');

        $threads = $this->_getThreads();

        // slice the needed chunk of discussion pages
        $more = ((count($threads) > ($first + $num)) ? true : false);
        $threads = array_slice($threads, $first, $num);

        foreach ($threads as $thread) {
            $comments = $this->_getComments($thread);
            $this->_threadHead($thread);
            if ($comments === false) {
                ptln('</div>', 6); // class="level2"
                continue;
            }

            ptln('<form method="post" action="'.wl($thread['id']).'">', 8);
            ptln('<div class="no">', 10);
            ptln('<input type="hidden" name="do" value="admin" />', 10);
            ptln('<input type="hidden" name="page" value="discussion" />', 10);
            echo html_buildlist($comments, 'admin_discussion', array($this, '_commentItem'), array($this, '_li_comment'));
            $this->_actionButtons($thread['id']);
        }
        $this->_browseDiscussionLinks($more, $first, $num);

    }

    /**
     * Returns an array of pages with discussion sections, sorted by recent comments
     */
    function _getThreads() {
        global $conf;

        require_once(DOKU_INC.'inc/search.php');

        // returns the list of pages in the given namespace and it's subspaces
        $items = array();
        search($items, $conf['datadir'], 'search_allpages', '');

        // add pages with comments to result
        $result = array();
        foreach ($items as $item) {
            $id = $item['id'];

            // some checks
            $file = metaFN($id, '.comments');
            if (!@file_exists($file)) continue; // skip if no comments file

            $date = filemtime($file);
            $result[] = array(
                    'id'   => $id,
                    'file' => $file,
                    'date' => $date,
                    );
        }

        // finally sort by time of last comment
        usort($result, array('admin_plugin_discussion', '_threadCmp'));

        return $result;
    }

    /**
     * Callback for comparison of thread data. 
     * 
     * Used for sorting threads in descending order by date of last comment. 
     * If this date happens to be equal for the compared threads, page id 
     * is used as second comparison attribute.
     */
    function _threadCmp($a, $b) {
        if ($a['date'] == $b['date']) {
            return strcmp($a['id'], $b['id']);
        }
        return ($a['date'] < $b['date']) ? 1 : -1;
    }

    /**
     * Outputs header, page ID and status of a discussion thread
     */
    function _threadHead($thread) {
        $id = $thread['id'];

        $labels = array(
                0 => $this->getLang('off'),
                1 => $this->getLang('open'),
                2 => $this->getLang('closed')
                );
        $title = p_get_metadata($id, 'title');
        if (!$title) $title = $id;
        ptln('<h2 name="'.$id.'" id="'.$id.'">'.hsc($title).'</h2>', 6);
        ptln('<form method="post" action="'.wl($id).'">', 6);
        ptln('<div class="mediaright">', 8);
        ptln('<input type="hidden" name="do" value="admin" />', 10);
        ptln('<input type="hidden" name="page" value="discussion" />', 10);
        ptln($this->getLang('status').': <select name="status" size="1">', 10);
        foreach ($labels as $key => $label) {
            $selected = (($key == $thread['status']) ? ' selected="selected"' : '');
            ptln('<option value="'.$key.'"'.$selected.'>'.$label.'</option>', 12);
        }
        ptln('</select> ', 10);
        ptln('<input type="submit" class="button" name="comment" value="'.$this->getLang('btn_change').'" class"button" title="'.$this->getLang('btn_change').'" />', 10);
        ptln('</div>', 8);
        ptln('</form>', 6);
        ptln('<div class="level2">', 6);
        ptln('<a href="'.wl($id).'" class="wikilink1">'.$id.'</a> ', 8);
        return true;
    }

    /**
     * Returns the full comments data for a given wiki page
     */
    function _getComments(&$thread) {
        $id = $thread['id'];

        if (!$thread['file']) $thread['file'] = metaFN($id, '.comments');
        if (!@file_exists($thread['file'])) return false; // no discussion thread at all

        $data = unserialize(io_readFile($thread['file'], false));

        $thread['status'] = $data['status'];
        $thread['number'] = $data['number'];
        if (!$data['status']) return false;   // comments are turned off
        if (!$data['comments']) return false; // no comments

        $result = array();
        foreach ($data['comments'] as $cid => $comment) {
            $this->_addComment($cid, $data, $result);
        }

        if (empty($result)) return false;
        else return $result;
    }

    /**
     * Recursive function to add the comment hierarchy to the result
     */
    function _addComment($cid, &$data, &$result, $parent = '', $level = 1) {
        if (!is_array($data['comments'][$cid])) return; // corrupt datatype
        $comment = $data['comments'][$cid];
        if ($comment['parent'] != $parent) return;      // answer to another comment

        // okay, add the comment to the result
        $comment['id'] = $cid;
        $comment['level'] = $level;
        $result[] = $comment;

        // check answers to this comment
        if (count($comment['replies'])) {
            foreach ($comment['replies'] as $rid) {
                $this->_addComment($rid, $data, $result, $cid, $level + 1);
            }
        }
    }

    /**
     * Checkbox and info about a comment item
     */
    function _commentItem($comment) {
        global $conf;

        // prepare variables
        if (is_array($comment['user'])) { // new format
            $name    = $comment['user']['name'];
            $mail    = $comment['user']['mail'];
        } else {                         // old format
            $name    = $comment['name'];
            $mail    = $comment['mail'];
        }
        if (is_array($comment['date'])) { // new format
            $created  = $comment['date']['created'];
        } else {                         // old format
            $created  = $comment['date'];
        }
        $abstract = preg_replace('/\s+?/', ' ', strip_tags($comment['xhtml']));
        if (utf8_strlen($abstract) > 160) $abstract = utf8_substr($abstract, 0, 160).'...';

        return '<input type="checkbox" name="cid['.$comment['id'].']" value="1" /> '.
            $this->email($mail, $name, 'email').', '.strftime($conf['dformat'], $created).': '.
            '<span class="abstract">'.$abstract.'</span>';
    }

    /**
     * list item tag
     */
    function _li_comment($comment) {
        $show = ($comment['show'] ? '' : ' hidden');
        return '<li class="level'.$comment['level'].$show.'">';
    }

    /**
     * Show buttons to bulk remove, hide or show comments
     */
    function _actionButtons($id) {
        global $lang;

        ptln('<div class="comment_buttons">', 12);
        ptln('<input type="submit" name="comment" value="'.$this->getLang('btn_show').'" class="button" title="'.$this->getLang('btn_show').'" />', 14);
        ptln('<input type="submit" name="comment" value="'.$this->getLang('btn_hide').'" class="button" title="'.$this->getLang('btn_hide').'" />', 14);
        ptln('<input type="submit" name="comment" value="'.$lang['btn_delete'].'" class="button" title="'.$lang['btn_delete'].'" />', 14);
        ptln('</div>', 12); // class="comment_buttons"
        ptln('</div>', 10); // class="no"
        ptln('</form>', 8);
        ptln('</div>', 6); // class="level2"
        return true;
    }

    /**
     * Displays links to older newer discussions
     */
    function _browseDiscussionLinks($more, $first, $num) {
        global $ID;

        if (($first == 0) && (!$more)) return true;

        $params = array('do' => 'admin', 'page' => 'discussion');
        $last = $first+$num;
        ptln('<div class="level1">', 8);
        if ($first > 0) {
            $first -= $num;
            if ($first < 0) $first = 0;
            $params['first'] = $first;
            ptln('<p class="centeralign">', 8);
            $ret = '<a href="'.wl($ID, $params).'" class="wikilink1">&lt;&lt; '.$this->getLang('newer').'</a>';
            if ($more) {
                $ret .= ' | ';
            } else {
                ptln($ret, 10);
                ptln('</p>', 8);
            }
        } else if ($more) {
            ptln('<p class="centeralign">', 8);
        }
        if ($more) {
            $params['first'] = $last;
            $ret .= '<a href="'.wl($ID, $params).'" class="wikilink1">'.$this->getLang('older').' &gt;&gt;</a>';
            ptln($ret, 10);
            ptln('</p>', 8);
        }
        ptln('</div>', 6); // class="level1"
        return true;
    }

    /**
     * Changes the status of a comment
     */
    function _changeStatus($new) {
        global $ID;

        // get discussion meta file name
        $file = metaFN($ID, '.comments');
        $data = unserialize(io_readFile($file, false));

        $old = $data['status'];
        if ($old == $new) return true;

        // save the comment metadata file
        $data['status'] = $new;
        io_saveFile($file, serialize($data));

        // look for ~~DISCUSSION~~ command in page file and change it accordingly
        $patterns = array('~~DISCUSSION:off\2~~', '~~DISCUSSION\2~~', '~~DISCUSSION:closed\2~~');
        $replace = $patterns[$new];
        $wiki = preg_replace('/~~DISCUSSION([\w:]*)(\|?.*?)~~/', $replace, rawWiki($ID));
        saveWikiText($ID, $wiki, $this->getLang('statuschanged'), true);

        return true;
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
