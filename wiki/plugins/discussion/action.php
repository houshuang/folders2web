<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Esther Brunner <wikidesign@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_discussion extends DokuWiki_Action_Plugin{

    var $avatar = null;
    var $style = null;
    var $use_avatar = null;

    function getInfo() {
        return array(
                'author' => 'Gina Häußge, Michael Klier, Esther Brunner',
                'email'  => 'dokuwiki@chimeric.de',
                'date'   => @file_get_contents(DOKU_PLUGIN.'discussion/VERSION'),
                'name'   => 'Discussion Plugin (action component)',
                'desc'   => 'Enables discussion features',
                'url'    => 'http://wiki.splitbrain.org/plugin:discussion',
                );
    }

    function register(&$contr) {
        $contr->register_hook(
                'ACTION_ACT_PREPROCESS',
                'BEFORE',
                $this,
                'handle_act_preprocess',
                array()
                );
        $contr->register_hook(
                'TPL_ACT_RENDER',
                'AFTER',
                $this,
                'comments',
                array()
                );
        $contr->register_hook(
                'INDEXER_PAGE_ADD',
                'AFTER',
                $this,
                'idx_add_discussion',
                array()
                );
        $contr->register_hook(
                'TPL_METAHEADER_OUTPUT',
                'BEFORE',
                $this,
                'handle_tpl_metaheader_output',
                array()
                );
        $contr->register_hook(
                'TOOLBAR_DEFINE',
                'AFTER',
                $this,
                'handle_toolbar_define',
                array()
                );
        $contr->register_hook(
                'AJAX_CALL_UNKNOWN',
                'BEFORE',
                $this,
                'handle_ajax_call',
                array()
                );
        $contr->register_hook(
                'TPL_TOC_RENDER',
                'BEFORE',
                $this,
                'handle_toc_render',
                array()
                );
    }

    /**
     * Preview Comments
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function handle_ajax_call(&$event, $params) {
        if($event->data != 'discussion_preview') return;
        $event->preventDefault();
        $event->stopPropagation();
        print p_locale_xhtml('preview');
        print '<div class="comment_preview">';
        if(!$_SERVER['REMOTE_USER'] && !$this->getConf('allowguests')) {
            print p_locale_xhtml('denied');
        } else {
            print $this->_render($_REQUEST['comment']);
        }
        print '</div>';
    }

    /**
     * Adds a TOC item if a discussion exists
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function handle_toc_render(&$event, $params) {
        global $ID;
        global $ACT;
        if($this->_hasDiscussion($title) && $event->data && $ACT != 'admin') {
            $tocitem = array( 'hid' => 'discussion__section',
                              'title' => $this->getLang('discussion'),
                              'type' => 'ul',
                              'level' => 1 );

            array_push($event->data, $tocitem);
        }
    }

    /**
     * Modify Tollbar for use with discussion plugin
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function handle_toolbar_define(&$event, $param) {
        global $ACT;
        if($ACT != 'show') return;

        if($this->_hasDiscussion($title) && $this->getConf('wikisyntaxok')) {
            $toolbar = array();
            foreach($event->data as $btn) {
                if($btn['type'] == 'mediapopup') continue;
                if($btn['type'] == 'signature') continue;
                if($btn['type'] == 'linkwiz') continue;
                if(preg_match("/=+?/", $btn['open'])) continue;
                array_push($toolbar, $btn);
            }
            $event->data = $toolbar;
        }
    }

    /**
     * Dirty workaround to add a toolbar to the discussion plugin
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function handle_tpl_metaheader_output(&$event, $param) {
        global $ACT;
        global $ID;
        if($ACT != 'show') return;

        if($this->_hasDiscussion($title) && $this->getConf('wikisyntaxok')) {
            // FIXME ugly workaround, replace this once DW the toolbar code is more flexible
            @require_once(DOKU_INC.'inc/toolbar.php');
            ob_start();
            print 'NS = "' . getNS($ID) . '";'; // we have to define NS, otherwise we get get JS errors
            toolbar_JSdefines('toolbar');
            $script = ob_get_clean();
            array_push($event->data['script'], array('type' => 'text/javascript', 'charset' => "utf-8", '_data' => $script));
        }
    }

    /**
     * Handles comment actions, dispatches data processing routines
     */
    function handle_act_preprocess(&$event, $param) {
        global $ID;
        global $INFO;
        global $conf;
        global $lang;

        // handle newthread ACTs
        if ($event->data == 'newthread') {
            // we can handle it -> prevent others
            $event->preventDefault();
            $event->data = $this->_newThread();
        }

        // enable captchas
        if (in_array($_REQUEST['comment'], array('add', 'save'))) {
            if (@file_exists(DOKU_PLUGIN.'captcha/action.php')) {
                $this->_captchaCheck();
            }
            if (@file_exists(DOKU_PLUGIN.'recaptcha/action.php')) {
                $this->_recaptchaCheck();
            }
        }

        // if we are not in show mode or someone wants to unsubscribe, that was all for now
        if ($event->data != 'show' && $event->data != 'discussion_unsubscribe' && $event->data != 'discussion_confirmsubscribe') return;

        if ($event->data == 'discussion_unsubscribe' or $event->data == 'discussion_confirmsubscribe') {
            // ok we can handle it prevent others
            $event->preventDefault();

            if (!isset($_REQUEST['hash'])) {
                return false;
            } else {
                $file = metaFN($ID, '.comments');
                $data = unserialize(io_readFile($file));
                $themail = '';
                foreach($data['subscribers'] as $mail => $info)  {
                    // convert old style subscribers just in case
                    if(!is_array($info)) {
                        $hash = $data['subscribers'][$mail];
                        $data['subscribers'][$mail]['hash']   = $hash;
                        $data['subscribers'][$mail]['active'] = true;
                        $data['subscribers'][$mail]['confirmsent'] = true;
                    }

                    if ($data['subscribers'][$mail]['hash'] == $_REQUEST['hash']) {
                        $themail = $mail;
                    }
                }

                if($themail != '') {
                    if($event->data == 'discussion_unsubscribe') {
                        unset($data['subscribers'][$themail]);
                        msg(sprintf($lang['unsubscribe_success'], $themail, $ID), 1);
                    } elseif($event->data == 'discussion_confirmsubscribe') {
                        $data['subscribers'][$themail]['active'] = true;
                        msg(sprintf($lang['subscribe_success'], $themail, $ID), 1);
                    }
                    io_saveFile($file, serialize($data));
                    $event->data = 'show';
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            // do the data processing for comments
            $cid  = $_REQUEST['cid'];
            switch ($_REQUEST['comment']) {
                case 'add':
                    if(empty($_REQUEST['text'])) return; // don't add empty comments
                    if(isset($_SERVER['REMOTE_USER']) && !$this->getConf('adminimport')) {
                        $comment['user']['id'] = $_SERVER['REMOTE_USER'];
                        $comment['user']['name'] = $INFO['userinfo']['name'];
                        $comment['user']['mail'] = $INFO['userinfo']['mail'];
                    } elseif((isset($_SERVER['REMOTE_USER']) && $this->getConf('adminimport') && auth_ismanager()) || !isset($_SERVER['REMOTE_USER'])) {
                        if(empty($_REQUEST['name']) or empty($_REQUEST['mail'])) return; // don't add anonymous comments
                        if(!mail_isvalid($_REQUEST['mail'])) {
                            msg($lang['regbadmail'], -1);
                            return;
                        } else {
                            $comment['user']['id'] = 'test'.hsc($_REQUEST['user']);
                            $comment['user']['name'] = hsc($_REQUEST['name']);
                            $comment['user']['mail'] = hsc($_REQUEST['mail']);
                        }
                    }
                    $comment['user']['address'] = ($this->getConf('addressfield')) ? hsc($_REQUEST['address']) : '';
                    $comment['user']['url'] = ($this->getConf('urlfield')) ? $this->_checkURL($_REQUEST['url']) : '';
                    $comment['subscribe'] = ($this->getConf('subscribe')) ? $_REQUEST['subscribe'] : '';
                    $comment['date'] = array('created' => $_REQUEST['date']);
                    $comment['raw'] = cleanText($_REQUEST['text']);
                    $repl = $_REQUEST['reply'];
                    if($this->getConf('moderate') && !auth_ismanager()) {
                        $comment['show'] = false;
                    } else {
                        $comment['show'] = true;
                    }
                    $this->_add($comment, $repl);
                    break;

                case 'save':
                    $raw  = cleanText($_REQUEST['text']);
                    $this->_save(array($cid), $raw);
                    break;

                case 'delete':
                    $this->_save(array($cid), '');
                    break;

                case 'toogle':
                    $this->_save(array($cid), '', 'toogle');
                    break;
            }
        }
    }

    /**
     * Main function; dispatches the visual comment actions
     */
    function comments(&$event, $param) {
        if ($event->data != 'show') return; // nothing to do for us

        $cid  = $_REQUEST['cid'];
        switch ($_REQUEST['comment']) {
            case 'edit':
                $this->_show(NULL, $cid);
                break;
            default:
                $this->_show($cid);
                break;
        }
    }

    /**
     * Redirects browser to given comment anchor
     */
    function _redirect($cid) {
        global $ID;
        global $ACT;

        if ($ACT !== 'show') return;

        if($this->getConf('moderate') && !auth_ismanager()) {
            msg($this->getLang('moderation'), 1);
            @session_start();
            global $MSG;
            $_SESSION[DOKU_COOKIE]['msg'] = $MSG;
            session_write_close();
            $url = wl($ID);
        } else {
            $url = wl($ID) . '#comment_' . $cid;
        }

        if (function_exists('send_redirect')) {
            send_redirect($url);
        } else {
            header('Location: ' . $url);
        }
        exit();
    }

    /**
     * Shows all comments of the current page
     */
    function _show($reply = NULL, $edit = NULL) {
        global $ID;
        global $INFO;
        global $ACT;

        // get .comments meta file name
        $file = metaFN($ID, '.comments');

        if (!$INFO['exists']) return;
        if (!@file_exists($file) && !$this->getConf('automatic')) return false;
        if (!$_SERVER['REMOTE_USER'] && !$this->getConf('showguests')) return false;

        // load data
        if (@file_exists($file)) {
            $data = unserialize(io_readFile($file, false));
            if (!$data['status']) return false; // comments are turned off
        } elseif (!@file_exists($file) && $this->getConf('automatic') && $INFO['exists']) {
            // set status to show the comment form
            $data['status'] = 1;
            $data['number'] = 0;
        }

        // show discussion wrapper only on certain circumstances
        $cnt = count($data['comments']);
        $keys = @array_keys($data['comments']);
        if($cnt > 1 || ($cnt == 1 && $data['comments'][$keys[0]]['show'] == 1) || $this->getConf('allowguests') || isset($_SERVER['REMOTE_USER'])) {
            $show = true;
            // section title
            $title = ($data['title'] ? hsc($data['title']) : $this->getLang('discussion'));
            ptln('<div id="comment" class="comment_wrapper" style="visibility: hidden;">');
            ptln('<h2><a name="discussion__section" id="discussion__section">', 2);
            ptln($title, 4);
            ptln('</a></h2>', 2);
            ptln('<div class="level2 hfeed">', 2);
        }

        // now display the comments
        if (isset($data['comments'])) {
            if (!$this->getConf('usethreading')) {
                $data['comments'] = $this->_flattenThreads($data['comments']);
                uasort($data['comments'], '_sortCallBack');
            }
            if($this->getConf('newestfirst')) {
                $data['comments'] = array_reverse($data['comments']);
            }
            foreach ($data['comments'] as $key => $value) {
                if ($key == $edit) $this->_form($value['raw'], 'save', $edit); // edit form
                else $this->_print($key, $data, '', $reply);
            }
        }

        // comment form
        if (($data['status'] == 1) && (!$reply || !$this->getConf('usethreading')) && !$edit) $this->_form('');

        if($show) {
            ptln('</div>', 2); // level2 hfeed
            ptln('</div>'); // comment_wrapper
        	ptln('<a href="javascript: document.getElementById("comment").style.visibility="visible";">View comments</a>');
		}

        return true;
    }

    function _flattenThreads($comments, $keys = null) {
        if (is_null($keys))
            $keys = array_keys($comments);

        foreach($keys as $cid) {
            if (!empty($comments[$cid]['replies'])) {
                $rids = $comments[$cid]['replies'];
                $comments = $this->_flattenThreads($comments, $rids);
                $comments[$cid]['replies'] = array();
            }
            $comments[$cid]['parent'] = '';
        }
        return $comments;
    }

    /**
     * Adds a new comment and then displays all comments
     */
    function _add($comment, $parent) {
        global $lang;
        global $ID;
        global $TEXT;

        $otxt = $TEXT; // set $TEXT to comment text for wordblock check
        $TEXT = $comment['raw'];

        // spamcheck against the DokuWiki blacklist
        if (checkwordblock()) {
            msg($this->getLang('wordblock'), -1);
            return false;
        }

        if ((!$this->getConf('allowguests'))
                && ($comment['user']['id'] != $_SERVER['REMOTE_USER']))
            return false; // guest comments not allowed

        $TEXT = $otxt; // restore global $TEXT

        // get discussion meta file name
        $file = metaFN($ID, '.comments');

        // create comments file if it doesn't exist yet
        if(!@file_exists($file)) {
            $data = array('status' => 1, 'number' => 0);
            io_saveFile($file, serialize($data));
        } else {
            $data = array();
            $data = unserialize(io_readFile($file, false));
            if ($data['status'] != 1) return false; // comments off or closed
        }

        if ($comment['date']['created']) {
            $date = strtotime($comment['date']['created']);
        } else {
            $date = time();
        }

        if ($date == -1) {
            $date = time();
        }

        $cid  = md5($comment['user']['id'].$date); // create a unique id

        if (!is_array($data['comments'][$parent])) {
            $parent = NULL; // invalid parent comment
        }

        // render the comment
        $xhtml = $this->_render($comment['raw']);

        // fill in the new comment
        $data['comments'][$cid] = array(
                'user'    => $comment['user'],
                'date'    => array('created' => $date),
                'show'    => true,
                'raw'     => $comment['raw'],
                'xhtml'   => $xhtml,
                'parent'  => $parent,
                'replies' => array(),
                'show'    => $comment['show']
                );

        if($comment['subscribe']) {
            $mail = $comment['user']['mail'];
            if($data['subscribers']) {
                if(!$data['subscribers'][$mail]) {
                    $data['subscribers'][$mail]['hash'] = md5($mail . mt_rand());
                    $data['subscribers'][$mail]['active'] = false;
                    $data['subscribers'][$mail]['confirmsent'] = false;
                } else {
                    // convert old style subscribers and set them active
                    if(!is_array($data['subscribers'][$mail])) {
                        $hash = $data['subscribers'][$mail];
                        $data['subscribers'][$mail]['hash'] = $hash;
                        $data['subscribers'][$mail]['active'] = true;
                        $data['subscribers'][$mail]['confirmsent'] = true;
                    }
                }
            } else {
                $data['subscribers'][$mail]['hash']   = md5($mail . mt_rand());
                $data['subscribers'][$mail]['active'] = false;
                $data['subscribers'][$mail]['confirmsent'] = false;
            }
        }

        // update parent comment
        if ($parent) $data['comments'][$parent]['replies'][] = $cid;

        // update the number of comments
        $data['number']++;

        // notify subscribers of the page
        $data['comments'][$cid]['cid'] = $cid;
        $this->_notify($data['comments'][$cid], $data['subscribers']);

        // save the comment metadata file
        io_saveFile($file, serialize($data));
        $this->_addLogEntry($date, $ID, 'cc', '', $cid);

        $this->_redirect($cid);
        return true;
    }

    /**
     * Saves the comment with the given ID and then displays all comments
     */
    function _save($cids, $raw, $act = NULL) {
        global $ID;

        if(!$cids) return; // do nothing if we get no comment id

        if ($raw) {
            global $TEXT;

            $otxt = $TEXT; // set $TEXT to comment text for wordblock check
            $TEXT = $raw;

            // spamcheck against the DokuWiki blacklist
            if (checkwordblock()) {
                msg($this->getLang('wordblock'), -1);
                return false;
            }

            $TEXT = $otxt; // restore global $TEXT
        }

        // get discussion meta file name
        $file = metaFN($ID, '.comments');
        $data = unserialize(io_readFile($file, false));

        if (!is_array($cids)) $cids = array($cids);
        foreach ($cids as $cid) {

            if (is_array($data['comments'][$cid]['user'])) {
                $user    = $data['comments'][$cid]['user']['id'];
                $convert = false;
            } else {
                $user    = $data['comments'][$cid]['user'];
                $convert = true;
            }

            // someone else was trying to edit our comment -> abort
            if (($user != $_SERVER['REMOTE_USER']) && (!auth_ismanager())) return false;

            $date = time();

            // need to convert to new format?
            if ($convert) {
                $data['comments'][$cid]['user'] = array(
                        'id'      => $user,
                        'name'    => $data['comments'][$cid]['name'],
                        'mail'    => $data['comments'][$cid]['mail'],
                        'url'     => $data['comments'][$cid]['url'],
                        'address' => $data['comments'][$cid]['address'],
                        );
                $data['comments'][$cid]['date'] = array(
                        'created' => $data['comments'][$cid]['date']
                        );
            }

            if ($act == 'toogle') {     // toogle visibility
                $now = $data['comments'][$cid]['show'];
                $data['comments'][$cid]['show'] = !$now;
                $data['number'] = $this->_count($data);

                $type = ($data['comments'][$cid]['show'] ? 'sc' : 'hc');

            } elseif ($act == 'show') { // show comment
                $data['comments'][$cid]['show'] = true;
                $data['number'] = $this->_count($data);

                $type = 'sc'; // show comment

            } elseif ($act == 'hide') { // hide comment
                $data['comments'][$cid]['show'] = false;
                $data['number'] = $this->_count($data);

                $type = 'hc'; // hide comment

            } elseif (!$raw) {          // remove the comment
                $data['comments'] = $this->_removeComment($cid, $data['comments']);
                $data['number'] = $this->_count($data);

                $type = 'dc'; // delete comment

            } else {                   // save changed comment
                $xhtml = $this->_render($raw);

                // now change the comment's content
                $data['comments'][$cid]['date']['modified'] = $date;
                $data['comments'][$cid]['raw']              = $raw;
                $data['comments'][$cid]['xhtml']            = $xhtml;

                $type = 'ec'; // edit comment
            }
        }

        // save the comment metadata file
        io_saveFile($file, serialize($data));
        $this->_addLogEntry($date, $ID, $type, '', $cid);

        $this->_redirect($cid);
        return true;
    }

    /**
     * Recursive function to remove a comment
     */
    function _removeComment($cid, $comments) {
        if (is_array($comments[$cid]['replies'])) {
            foreach ($comments[$cid]['replies'] as $rid) {
                $comments = $this->_removeComment($rid, $comments);
            }
        }
        unset($comments[$cid]);
        return $comments;
    }

    /**
     * Prints an individual comment
     */
    function _print($cid, &$data, $parent = '', $reply = '', $visible = true) {

        if (!isset($data['comments'][$cid])) return false; // comment was removed
        $comment = $data['comments'][$cid];

        if (!is_array($comment)) return false;             // corrupt datatype

        if ($comment['parent'] != $parent) return true;    // reply to an other comment

        if (!$comment['show']) {                            // comment hidden
            if (auth_ismanager()) $hidden = ' comment_hidden';
            else return true;
        } else {
            $hidden = '';
        }

        // print the actual comment
        $this->_print_comment($cid, $data, $parent, $reply, $visible, $hidden);
        // replies to this comment entry?
        $this->_print_replies($cid, $data, $reply, $visible);
        // reply form
        $this->_print_form($cid, $reply);
    }

    function _print_comment($cid, &$data, $parent, $reply, $visible, $hidden)
    {
        global $conf, $lang, $ID, $HIGH;
        $comment = $data['comments'][$cid];

        // comment head with date and user data
        ptln('<div class="hentry'.$hidden.'">', 4);
        ptln('<div class="comment_head">', 6);
        ptln('<a name="comment_'.$cid.'" id="comment_'.$cid.'"></a>', 8);
        $head = '<span class="vcard author">';

        // prepare variables
        if (is_array($comment['user'])) { // new format
            $user    = $comment['user']['id'];
            $name    = $comment['user']['name'];
            $mail    = $comment['user']['mail'];
            $url     = $comment['user']['url'];
            $address = $comment['user']['address'];
        } else {                         // old format
            $user    = $comment['user'];
            $name    = $comment['name'];
            $mail    = $comment['mail'];
            $url     = $comment['url'];
            $address = $comment['address'];
        }
        if (is_array($comment['date'])) { // new format
            $created  = $comment['date']['created'];
            $modified = $comment['date']['modified'];
        } else {                         // old format
            $created  = $comment['date'];
            $modified = $comment['edited'];
        }

        // show username or real name?
        if ((!$this->getConf('userealname')) && ($user)) {
            $showname = $user;
        } else {
            $showname = $name;
        }

        // show avatar image?
        if ($this->_use_avatar()) {
            $user_data['name'] = $name;
            $user_data['user'] = $user;
            $user_data['mail'] = $mail;
            $avatar = $this->avatar->getXHTML($user_data, $name, 'left');
            if($avatar) $head .= $avatar;
        }

        if ($this->getConf('linkemail') && $mail) {
            $head .= $this->email($mail, $showname, 'email fn');
        } elseif ($url) {
            $head .= $this->external_link($this->_checkURL($url), $showname, 'urlextern url fn');
        } else {
            $head .= '<span class="fn">'.$showname.'</span>';
        }

        if ($address) $head .= ', <span class="adr">'.$address.'</span>';
        $head .= '</span>, '.
            '<abbr class="published" title="'. strftime('%Y-%m-%dT%H:%M:%SZ', $created) .'">'.
            dformat($created, $conf['dformat']).'</abbr>';
        if ($comment['edited']) $head .= ' (<abbr class="updated" title="'.
                strftime('%Y-%m-%dT%H:%M:%SZ', $modified).'">'.dformat($modified, $conf['dformat']).
                '</abbr>)';
        ptln($head, 8);
        ptln('</div>', 6); // class="comment_head"

        // main comment content
        ptln('<div class="comment_body entry-content"'.
                ($this->getConf('useavatar') ? $this->_get_style() : '').'>', 6);
        echo ($HIGH?html_hilight($comment['xhtml'],$HIGH):$comment['xhtml']).DOKU_LF;
        ptln('</div>', 6); // class="comment_body"

        if ($visible) {
            ptln('<div class="comment_buttons">', 6);

            // show reply button?
            if (($data['status'] == 1) && !$reply && $comment['show']
                    && ($this->getConf('allowguests') || $_SERVER['REMOTE_USER']) && $this->getConf('usethreading'))
                $this->_button($cid, $this->getLang('btn_reply'), 'reply', true);

            // show edit, show/hide and delete button?
            if ((($user == $_SERVER['REMOTE_USER']) && ($user != '')) || (auth_ismanager())) {
                $this->_button($cid, $lang['btn_secedit'], 'edit', true);
                $label = ($comment['show'] ? $this->getLang('btn_hide') : $this->getLang('btn_show'));
                $this->_button($cid, $label, 'toogle');
                $this->_button($cid, $lang['btn_delete'], 'delete');
            }
            ptln('</div>', 6); // class="comment_buttons"
        }
        ptln('</div>', 4); // class="hentry"
    }

    function _print_form($cid, $reply)
    {
        if ($this->getConf('usethreading') && $reply == $cid) {
            ptln('<div class="comment_replies">', 4);
            $this->_form('', 'add', $cid);
            ptln('</div>', 4); // class="comment_replies"
        }
    }

    function _print_replies($cid, &$data, $reply, &$visible)
    {
        $comment = $data['comments'][$cid];
        if (!count($comment['replies'])) {
            return;
        }
        ptln('<div class="comment_replies"'.$this->_get_style().'>', 4);
        $visible = ($comment['show'] && $visible);
        foreach ($comment['replies'] as $rid) {
            $this->_print($rid, $data, $cid, $reply, $visible);
        }
        ptln('</div>', 4);
    }

    function _use_avatar()
    {
        if (is_null($this->use_avatar)) {
            $this->use_avatar = $this->getConf('useavatar')
                    && (!plugin_isdisabled('avatar'))
                    && ($this->avatar =& plugin_load('helper', 'avatar'));
        }
        return $this->use_avatar;
    }

    function _get_style()
    {
        if (is_null($this->style)){
            if ($this->_use_avatar()) {
                $this->style = ' style="margin-left: '.($this->avatar->getConf('size') + 14).'px;"';
            } else {
                $this->style = ' style="margin-left: 20px;"';
            }
        }
        return $this->style;
    }

    /**
     * Outputs the comment form
     */
    function _form($raw = '', $act = 'add', $cid = NULL) {
        global $lang;
        global $conf;
        global $ID;
        global $INFO;

        // not for unregistered users when guest comments aren't allowed
        if (!$_SERVER['REMOTE_USER'] && !$this->getConf('allowguests')) {
            ?>
            <div class="comment_form">
                <?php echo $this->getLang('noguests'); ?>
            </div>
            <?php
            return false;
        }

        // fill $raw with $_REQUEST['text'] if it's empty (for failed CAPTCHA check)
        if (!$raw && ($_REQUEST['comment'] == 'show')) $raw = $_REQUEST['text'];
        ?>

        <div class="comment_form">
          <form id="discussion__comment_form" method="post" action="<?php echo script() ?>" accept-charset="<?php echo $lang['encoding'] ?>">
            <div class="no">
              <input type="hidden" name="id" value="<?php echo $ID ?>" />
              <input type="hidden" name="do" value="show" />
              <input type="hidden" name="comment" value="<?php echo $act ?>" />
        <?php
        // for adding a comment
        if ($act == 'add') {
        ?>
              <input type="hidden" name="reply" value="<?php echo $cid ?>" />
        <?php
        // for guest/adminimport: show name, e-mail and subscribe to comments fields
        if(!$_SERVER['REMOTE_USER'] or ($this->getConf('adminimport') && auth_ismanager())) {
        ?>
              <input type="hidden" name="user" value="<?php echo clientIP() ?>" />
              <div class="comment_name">
                <label class="block" for="discussion__comment_name">
                  <span><?php echo $lang['fullname'] ?>:</span>
                  <input type="text" class="edit<?php if($_REQUEST['comment'] == 'add' && empty($_REQUEST['name'])) echo ' error'?>" name="name" id="discussion__comment_name" size="50" tabindex="1" value="<?php echo hsc($_REQUEST['name'])?>" />
                </label>
              </div>
              <div class="comment_mail">
                <label class="block" for="discussion__comment_mail">
                  <span><?php echo $lang['email'] ?>:</span>
                  <input type="text" class="edit<?php if($_REQUEST['comment'] == 'add' && empty($_REQUEST['mail'])) echo ' error'?>" name="mail" id="discussion__comment_mail" size="50" tabindex="2" value="<?php echo hsc($_REQUEST['mail'])?>" />
                </label>
              </div>
        <?php
        }

        // allow entering an URL
        if ($this->getConf('urlfield')) {
        ?>
              <div class="comment_url">
                <label class="block" for="discussion__comment_url">
                  <span><?php echo $this->getLang('url') ?>:</span>
                  <input type="text" class="edit" name="url" id="discussion__comment_url" size="50" tabindex="3" value="<?php echo hsc($_REQUEST['url'])?>" />
                </label>
              </div>
        <?php
        }

        // allow entering an address
        if ($this->getConf('addressfield')) {
        ?>
              <div class="comment_address">
                <label class="block" for="discussion__comment_address">
                  <span><?php echo $this->getLang('address') ?>:</span>
                  <input type="text" class="edit" name="address" id="discussion__comment_address" size="50" tabindex="4" value="<?php echo hsc($_REQUEST['address'])?>" />
                </label>
              </div>
        <?php
        }

        // allow setting the comment date
        if ($this->getConf('adminimport') && (auth_ismanager())) {
        ?>
              <div class="comment_date">
                <label class="block" for="discussion__comment_date">
                  <span><?php echo $this->getLang('date') ?>:</span>
                  <input type="text" class="edit" name="date" id="discussion__comment_date" size="50" />
                </label>
              </div>
        <?php
        }

        // for saving a comment
        } else {
        ?>
              <input type="hidden" name="cid" value="<?php echo $cid ?>" />
        <?php
        }
        ?>
              <div class="comment_text">
                <div id="discussion__comment_toolbar">
                  <?php echo $this->getLang('entercomment')?>
                  <?php if($this->getLang('wikisyntaxok')) echo ', ' . $this->getLang('wikisyntax') . ':';?>
                </div>
                <textarea class="edit<?php if($_REQUEST['comment'] == 'add' && empty($_REQUEST['text'])) echo ' error'?>" name="text" cols="80" rows="10" id="discussion__comment_text" tabindex="5"><?php
                  if($raw) {
                      echo formText($raw);
                  } else {
                      echo $_REQUEST['text'];
                  }
                ?></textarea>
              </div>
        <?php //bad and dirty event insert hook
        $evdata = array('writable' => true);
        trigger_event('HTML_EDITFORM_INJECTION', $evdata);
        ?>
              <input class="button comment_submit" id="discussion__btn_submit" type="submit" name="submit" accesskey="s" value="<?php echo $lang['btn_save'] ?>" title="<?php echo $lang['btn_save']?> [S]" tabindex="7" />
              <input class="button comment_preview_button" id="discussion__btn_preview" type="button" name="preview" accesskey="p" value="<?php echo $lang['btn_preview'] ?>" title="<?php echo $lang['btn_preview']?> [P]" />

        <?php if((!$_SERVER['REMOTE_USER'] || $_SERVER['REMOTE_USER'] && !$conf['subscribers']) && $this->getConf('subscribe')) { ?>
              <div class="comment_subscribe">
                <input type="checkbox" id="discussion__comment_subscribe" name="subscribe" tabindex="6" />
                <label class="block" for="discussion__comment_subscribe">
                  <span><?php echo $this->getLang('subscribe') ?></span>
                </label>
              </div>
        <?php } ?>

              <div class="clearer"></div>
              <div id="discussion__comment_preview">&nbsp;</div>
            </div>
          </form>
        </div>
        <?php
        if ($this->getConf('usecocomment')) echo $this->_coComment();
    }

    /**
     * Adds a javascript to interact with coComments
     */
    function _coComment() {
        global $ID;
        global $conf;
        global $INFO;

        $user = $_SERVER['REMOTE_USER'];

        ?>
        <script type="text/javascript"><!--//--><![CDATA[//><!--
          var blogTool  = "DokuWiki";
          var blogURL   = "<?php echo DOKU_URL ?>";
          var blogTitle = "<?php echo $conf['title'] ?>";
          var postURL   = "<?php echo wl($ID, '', true) ?>";
          var postTitle = "<?php echo tpl_pagetitle($ID, true) ?>";
        <?php
        if ($user) {
        ?>
          var commentAuthor = "<?php echo $INFO['userinfo']['name'] ?>";
        <?php
        } else {
        ?>
          var commentAuthorFieldName = "name";
        <?php
        }
        ?>
          var commentAuthorLoggedIn = <?php echo ($user ? 'true' : 'false') ?>;
          var commentFormID         = "discussion__comment_form";
          var commentTextFieldName  = "text";
          var commentButtonName     = "submit";
          var cocomment_force       = false;
        //--><!]]></script>
        <script type="text/javascript" src="http://www.cocomment.com/js/cocomment.js">
        </script>
        <?php
    }

    /**
     * General button function
     */
    function _button($cid, $label, $act, $jump = false) {
        global $ID;

        $anchor = ($jump ? '#discussion__comment_form' : '' );

        ?>
        <form class="button discussion__<?php echo $act?>" method="get" action="<?php echo script().$anchor ?>">
          <div class="no">
            <input type="hidden" name="id" value="<?php echo $ID ?>" />
            <input type="hidden" name="do" value="show" />
            <input type="hidden" name="comment" value="<?php echo $act ?>" />
            <input type="hidden" name="cid" value="<?php echo $cid ?>" />
            <input type="submit" value="<?php echo $label ?>" class="button" title="<?php echo $label ?>" />
          </div>
        </form>
        <?php
        return true;
    }

    /**
     * Adds an entry to the comments changelog
     *
     * @author Esther Brunner <wikidesign@gmail.com>
     * @author Ben Coburn <btcoburn@silicodon.net>
     */
    function _addLogEntry($date, $id, $type = 'cc', $summary = '', $extra = '') {
        global $conf;

        $changelog = $conf['metadir'].'/_comments.changes';

        if(!$date) $date = time(); //use current time if none supplied
        $remote = $_SERVER['REMOTE_ADDR'];
        $user   = $_SERVER['REMOTE_USER'];

        $strip = array("\t", "\n");
        $logline = array(
                'date'  => $date,
                'ip'    => $remote,
                'type'  => str_replace($strip, '', $type),
                'id'    => $id,
                'user'  => $user,
                'sum'   => str_replace($strip, '', $summary),
                'extra' => str_replace($strip, '', $extra)
                );

        // add changelog line
        $logline = implode("\t", $logline)."\n";
        io_saveFile($changelog, $logline, true); //global changelog cache
        $this->_trimRecentCommentsLog($changelog);

        // tell the indexer to re-index the page
        @unlink(metaFN($id, '.indexed'));
    }

    /**
     * Trims the recent comments cache to the last $conf['changes_days'] recent
     * changes or $conf['recent'] items, which ever is larger.
     * The trimming is only done once a day.
     *
     * @author Ben Coburn <btcoburn@silicodon.net>
     */
    function _trimRecentCommentsLog($changelog) {
        global $conf;

        if (@file_exists($changelog) &&
                (filectime($changelog) + 86400) < time() &&
                !@file_exists($changelog.'_tmp')) {

            io_lock($changelog);
            $lines = file($changelog);
            if (count($lines)<$conf['recent']) {
                // nothing to trim
                io_unlock($changelog);
                return true;
            }

            io_saveFile($changelog.'_tmp', '');                  // presave tmp as 2nd lock
            $trim_time = time() - $conf['recent_days']*86400;
            $out_lines = array();

            $num = count($lines);
            for ($i=0; $i<$num; $i++) {
                $log = parseChangelogLine($lines[$i]);
                if ($log === false) continue;                      // discard junk
                if ($log['date'] < $trim_time) {
                    $old_lines[$log['date'].".$i"] = $lines[$i];     // keep old lines for now (append .$i to prevent key collisions)
                } else {
                    $out_lines[$log['date'].".$i"] = $lines[$i];     // definitely keep these lines
                }
            }

            // sort the final result, it shouldn't be necessary,
            // however the extra robustness in making the changelog cache self-correcting is worth it
            ksort($out_lines);
            $extra = $conf['recent'] - count($out_lines);        // do we need extra lines do bring us up to minimum
            if ($extra > 0) {
                ksort($old_lines);
                $out_lines = array_merge(array_slice($old_lines,-$extra),$out_lines);
            }

            // save trimmed changelog
            io_saveFile($changelog.'_tmp', implode('', $out_lines));
            @unlink($changelog);
            if (!rename($changelog.'_tmp', $changelog)) {
                // rename failed so try another way...
                io_unlock($changelog);
                io_saveFile($changelog, implode('', $out_lines));
                @unlink($changelog.'_tmp');
            } else {
                io_unlock($changelog);
            }
            return true;
        }
    }

    /**
     * Sends a notify mail on new comment
     *
     * @param  array  $comment  data array of the new comment
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Esther Brunner <wikidesign@gmail.com>
     */
    function _notify($comment, &$subscribers) {
        global $conf;
        global $ID;
        global $INFO;

        $notify_text = io_readfile($this->localfn('subscribermail'));
        $confirm_text = io_readfile($this->localfn('confirmsubscribe'));
        $subject_notify = '['.$conf['title'].'] '.$this->getLang('mail_newcomment');
        $subject_subscribe = '['.$conf['title'].'] '.$this->getLang('subscribe');
        $from = $conf['mailfrom'];
        $from = str_replace('@USER@',$_SERVER['REMOTE_USER'],$from);
        $from = str_replace('@NAME@',$INFO['userinfo']['name'],$from);
        $from = str_replace('@MAIL@',$INFO['userinfo']['mail'],$from);

        $search = array(
                '@PAGE@',
                '@TITLE@',
                '@DATE@',
                '@NAME@',
                '@TEXT@',
                '@COMMENTURL@',
                '@UNSUBSCRIBE@',
                '@DOKUWIKIURL@',
                );

        // notify page subscribers
        if ($conf['subscribers'] || $conf['notify']) {
            $to   = $conf['notify'];
            $data = array('id' => $ID, 'addresslist' => '', 'self' => false);
            trigger_event('COMMON_NOTIFY_ADDRESSLIST', $data, 'subscription_addresslist');
            $bcc = $data['addresslist'];

            $replace = array(
                    $ID,
                    $conf['title'],
                    dformat($comment['date']['created'], $conf['dformat']),
                    $comment['user']['name'],
                    $comment['raw'],
                    wl($ID, '', true) . '#comment_' . $comment['cid'],
                    wl($ID, 'do=unsubscribe', true, '&'),
                    DOKU_URL,
                    );

                $body = str_replace($search, $replace, $notify_text);
                mail_send($to, $subject_notify, $body, $from, '', $bcc);
        }

        // notify comment subscribers
        if (!empty($subscribers)) {

            foreach($subscribers as $mail => $data) {
                $to = $mail;

                if($data['active']) {
                    $replace = array(
                            $ID,
                            $conf['title'],
                            dformat($comment['date']['created'], $conf['dformat']),
                            $comment['user']['name'],
                            $comment['raw'],
                            wl($ID, '', true) . '#comment_' . $comment['cid'],
                            wl($ID, 'do=discussion_unsubscribe&hash=' . $data['hash'], true, '&'),
                            DOKU_URL,
                            );

                    $body = str_replace($search, $replace, $notify_text);
                    mail_send($to, $subject_notify, $body, $from);
                } elseif(!$data['active'] && !$data['confirmsent']) {
                    $search = array(
                            '@PAGE@',
                            '@TITLE@',
                            '@SUBSCRIBE@',
                            '@DOKUWIKIURL@',
                            );
                    $replace = array(
                            $ID,
                            $conf['title'],
                            wl($ID, 'do=discussion_confirmsubscribe&hash=' . $data['hash'], true, '&'),
                            DOKU_URL,
                            );

                    $body = str_replace($search, $replace, $confirm_text);
                    mail_send($to, $subject_subscribe, $body, $from);
                    $subscribers[$mail]['confirmsent'] = true;
                }
            }
        }
    }

    /**
     * Counts the number of visible comments
     */
    function _count($data) {
        $number = 0;
        foreach ($data['comments'] as $cid => $comment) {
            if ($comment['parent']) continue;
            if (!$comment['show']) continue;
            $number++;
            $rids = $comment['replies'];
            if (count($rids)) $number = $number + $this->_countReplies($data, $rids);
        }
        return $number;
    }

    function _countReplies(&$data, $rids) {
        $number = 0;
        foreach ($rids as $rid) {
            if (!isset($data['comments'][$rid])) continue; // reply was removed
            if (!$data['comments'][$rid]['show']) continue;
            $number++;
            $rids = $data['comments'][$rid]['replies'];
            if (count($rids)) $number = $number + $this->_countReplies($data, $rids);
        }
        return $number;
    }

    /**
     * Renders the comment text
     */
    function _render($raw) {
        if ($this->getConf('wikisyntaxok')) {
            $xhtml = $this->render($raw);
        } else { // wiki syntax not allowed -> just encode special chars
            $xhtml = hsc(trim($raw));
            $xhtml = str_replace("\n", '<br />', $xhtml);
        }
        return $xhtml;
    }

    /**
     * Finds out whether there is a discussion section for the current page
     */
    function _hasDiscussion(&$title) {
        global $ID;

        $cfile = metaFN($ID, '.comments');

        if (!@file_exists($cfile)) {
            if ($this->getConf('automatic')) {
                return true;
            } else {
                return false;
            }
        }

        $comments = unserialize(io_readFile($cfile, false));

        if ($comments['title']) $title = hsc($comments['title']);
        $num = $comments['number'];
        if ((!$comments['status']) || (($comments['status'] == 2) && (!$num))) return false;
        else return true;
    }

    /**
     * Creates a new thread page
     */
    function _newThread() {
        global $ID, $INFO;

        $ns    = cleanID($_REQUEST['ns']);
        $title = str_replace(':', '', $_REQUEST['title']);
        $back  = $ID;
        $ID    = ($ns ? $ns.':' : '').cleanID($title);
        $INFO  = pageinfo();

        // check if we are allowed to create this file
        if ($INFO['perm'] >= AUTH_CREATE) {

            //check if locked by anyone - if not lock for my self
            if ($INFO['locked']) return 'locked';
            else lock($ID);

            // prepare the new thread file with default stuff
            if (!@file_exists($INFO['filepath'])) {
                global $TEXT;

                $TEXT = pageTemplate(array(($ns ? $ns.':' : '').$title));
                if (!$TEXT) {
                    $data = array('id' => $ID, 'ns' => $ns, 'title' => $title, 'back' => $back);
                    $TEXT = $this->_pageTemplate($data);
                }
                return 'preview';
            } else {
                return 'edit';
            }
        } else {
            return 'show';
        }
    }

    /**
     * Adapted version of pageTemplate() function
     */
    function _pageTemplate($data) {
        global $conf, $INFO;

        $id   = $data['id'];
        $user = $_SERVER['REMOTE_USER'];
        $tpl  = io_readFile(DOKU_PLUGIN.'discussion/_template.txt');

        // standard replacements
        $replace = array(
                '@NS@'   => $data['ns'],
                '@PAGE@' => strtr(noNS($id),'_',' '),
                '@USER@' => $user,
                '@NAME@' => $INFO['userinfo']['name'],
                '@MAIL@' => $INFO['userinfo']['mail'],
                '@DATE@' => dformat($conf['dformat']),
                );

        // additional replacements
        $replace['@BACK@']  = $data['back'];
        $replace['@TITLE@'] = $data['title'];

        // avatar if useavatar and avatar plugin available
        if ($this->getConf('useavatar')
                && (@file_exists(DOKU_PLUGIN.'avatar/syntax.php'))
                && (!plugin_isdisabled('avatar'))) {
            $replace['@AVATAR@'] = '{{avatar>'.$user.' }} ';
        } else {
            $replace['@AVATAR@'] = '';
        }

        // tag if tag plugin is available
        if ((@file_exists(DOKU_PLUGIN.'tag/syntax/tag.php'))
                && (!plugin_isdisabled('tag'))) {
            $replace['@TAG@'] = "\n\n{{tag>}}";
        } else {
            $replace['@TAG@'] = '';
        }

        // do the replace
        $tpl = str_replace(array_keys($replace), array_values($replace), $tpl);
        return $tpl;
    }

    /**
     * Checks if the CAPTCHA string submitted is valid
     *
     * @author     Andreas Gohr <gohr@cosmocode.de>
     * @adaption   Esther Brunner <wikidesign@gmail.com>
     */
    function _captchaCheck() {
        if (plugin_isdisabled('captcha') || (!$captcha = plugin_load('helper', 'captcha')))
            return; // CAPTCHA is disabled or not available

        // do nothing if logged in user and no CAPTCHA required
        if (!$captcha->getConf('forusers') && $_SERVER['REMOTE_USER']) return;

        // compare provided string with decrypted captcha
        $rand = PMA_blowfish_decrypt($_REQUEST['plugin__captcha_secret'], auth_cookiesalt());
        $code = $captcha->_generateCAPTCHA($captcha->_fixedIdent(), $rand);

        if (!$_REQUEST['plugin__captcha_secret'] ||
                !$_REQUEST['plugin__captcha'] ||
                strtoupper($_REQUEST['plugin__captcha']) != $code) {

            // CAPTCHA test failed! Continue to edit instead of saving
            msg($captcha->getLang('testfailed'), -1);
            if ($_REQUEST['comment'] == 'save') $_REQUEST['comment'] = 'edit';
            elseif ($_REQUEST['comment'] == 'add') $_REQUEST['comment'] = 'show';
        }
        // if we arrive here it was a valid save
    }

    /**
     * checks if the submitted reCAPTCHA string is valid
     *
     * @author Adrian Schlegel <adrian@liip.ch>
     */
    function _recaptchaCheck() {
        if (plugin_isdisabled('recaptcha') || (!$recaptcha = plugin_load('helper', 'recaptcha')))
            return; // reCAPTCHA is disabled or not available

        // do nothing if logged in user and no reCAPTCHA required
        if (!$recaptcha->getConf('forusers') && $_SERVER['REMOTE_USER']) return;

        $resp = $recaptcha->check();
        if (!$resp->is_valid) {
            msg($recaptcha->getLang('testfailed'),-1);
            if ($_REQUEST['comment'] == 'save') $_REQUEST['comment'] = 'edit';
            elseif ($_REQUEST['comment'] == 'add') $_REQUEST['comment'] = 'show';
        }
    }

    /**
     * Adds the comments to the index
     */
    function idx_add_discussion(&$event, $param) {

        // get .comments meta file name
        $file = metaFN($event->data[0], '.comments');

        if (@file_exists($file)) $data = unserialize(io_readFile($file, false));
        if ((!$data['status']) || ($data['number'] == 0)) return; // comments are turned off

        // now add the comments
        if (isset($data['comments'])) {
            foreach ($data['comments'] as $key => $value) {
                $event->data[1] .= $this->_addCommentWords($key, $data);
            }
        }
    }

    /**
     * Adds the words of a given comment to the index
     */
    function _addCommentWords($cid, &$data, $parent = '') {

        if (!isset($data['comments'][$cid])) return ''; // comment was removed
        $comment = $data['comments'][$cid];

        if (!is_array($comment)) return '';             // corrupt datatype
        if ($comment['parent'] != $parent) return '';   // reply to an other comment
        if (!$comment['show']) return '';               // hidden comment

        $text = $comment['raw'];                        // we only add the raw comment text
        if (is_array($comment['replies'])) {             // and the replies
            foreach ($comment['replies'] as $rid) {
                $text .= $this->_addCommentWords($rid, $data, $cid);
            }
        }
        return ' '.$text;
    }

    /**
     * Only allow http(s) URLs and append http:// to URLs if needed
     */
    function _checkURL($url) {
        if(preg_match("#^http://|^https://#", $url)) {
            return hsc($url);
        } elseif(substr($url, 0, 4) == 'www.') {
            return hsc('http://' . $url);
        } else {
            return '';
        }
    }
}

function _sortCallback($a, $b) {
    if (is_array($a['date'])) { // new format
        $createdA  = $a['date']['created'];
    } else {                         // old format
        $createdA  = $a['date'];
    }

    if (is_array($b['date'])) { // new format
        $createdB  = $b['date']['created'];
    } else {                         // old format
        $createdB  = $b['date'];
    }

    if ($createdA == $createdB)
        return 0;
    else
        return ($createdA < $createdB) ? -1 : 1;
}

// vim:ts=4:sw=4:et:enc=utf-8:
