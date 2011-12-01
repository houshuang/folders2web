<?php

/**
 * Plugin BatchEdit
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

/* Must be run within Dokuwiki */
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'admin.php');

class admin_plugin_batchedit extends DokuWiki_Admin_Plugin {

    var $error;
    var $warning;
    var $command;
    var $namespace;
    var $regexp;
    var $replacement;
    var $summary;
    var $minorEdit;
    var $pageIndex;
    var $match;
    var $indent;

    function admin_plugin_batchedit() {
        $this->error = '';
        $this->warning = array();
        $this->command = 'hello';
        $this->namespace = '';
        $this->regexp = '';
        $this->replacement = '';
        $this->summary = '';
        $this->minorEdit = FALSE;
        $this->pageIndex = array();
        $this->match = array();
        $this->indent = 0;
    }

    /**
     * Return some info
     */
    function getInfo() {
        return array(
            'author' => 'Mykola Ostrovskyy',
            'email'  => 'spambox03@mail.ru',
            'date'   => '2009-02-14',
            'name'   => 'BatchEdit',
            'desc'   => 'Edit wiki pages using regular expressions.',
            'url'    => 'http://www.dokuwiki.org/plugin:batchedit',
        );
    }

    /**
     *
     */
    function getLang($id) {
        $string = parent::getLang($id);

        if (func_num_args() > 1) {
            $search = array();
            $replace = array();

            for ($i = 1; $i < func_num_args(); $i++) {
                $search[$i-1] = '{' . $i . '}';
                $replace[$i-1] = func_get_arg($i);
            }

            $string = str_replace($search, $replace, $string);
        }

        return $string;
    }

    /**
     * Handle user request
     */
    function handle() {

        if (!isset($_REQUEST['cmd'])) {
            // First time - nothing to do
            return;
        }

        try {
            $this->_parseRequest();

            switch ($this->command) {
                case 'preview':
                    $this->_preview();
                    break;

                case 'apply':
                    $this->_apply();
                    break;
            }
        }
        catch (Exception $error) {
            $this->error = $this->getLang($error->getMessage());
        }
    }

    /**
     * Output appropriate html
     */
    function html() {
        global $ID;

        ptln('<!-- batchedit -->');
        ptln('<div id="batchedit">');

        $this->_printMessages();

        ptln('<form action="' . wl($ID) . '" method="post">');

        if ($this->error == '') {
            switch ($this->command) {
                case 'preview':
                    $this->_printMatches();
                    break;

                case 'apply':
                    $this->_printMatches();
                    break;
            }
        }

        $this->_printMainForm();

        ptln('</form>');
        ptln('</div>');
        ptln('<!-- /batchedit -->');
    }

    /**
     *
     */
    function _parseRequest() {
        $this->command = $this->_getCommand();
        $this->namespace = $this->_getNamespace();
        $this->regexp = $this->_getRegexp();
        $this->replacement = $this->_getReplacement();
        $this->summary = $this->_getSummary();
        $this->minorEdit = isset($_REQUEST['minor']);
    }

    /**
     *
     */
    function _getCommand() {
        if (!is_array($_REQUEST['cmd'])) {
            throw new Exception('err_invreq');
        }

        $command = key($_REQUEST['cmd']);

        if (($command != 'preview') && ($command != 'apply')) {
            throw new Exception('err_invreq');
        }

        return $command;
    }

    /**
     *
     */
    function _getNamespace() {
        if (!isset($_REQUEST['namespace'])) {
            throw new Exception('err_invreq');
        }

        $namespace = trim($_REQUEST['namespace']);

        if ($namespace != '') {
            global $ID;

            $namespace = resolve_id(getNS($ID), $namespace . ':');

            if ($namespace != '') {
                $namespace .= ':';
            }
        }

        return $namespace;
    }

    /**
     *
     */
    function _getRegexp() {
        if (!isset($_REQUEST['regexp'])) {
            throw new Exception('err_invreq');
        }

        $regexp = trim($_REQUEST['regexp']);

        if ($regexp == '') {
            throw new Exception('err_noregexp');
        }

        if (preg_match('/^([^\w\\\\]|_).+?\1[imsxeADSUXJu]*$/', $regexp) != 1) {
            throw new Exception('err_invregexp');
        }

        return $regexp;
    }

    /**
     *
     */
    function _getReplacement() {
        if (!isset($_REQUEST['replace'])) {
            throw new Exception('err_invreq');
        }

        return $_REQUEST['replace'];
    }

    /**
     *
     */
    function _getSummary() {
        if (!isset($_REQUEST['summary'])) {
            throw new Exception('err_invreq');
        }

        return $_REQUEST['summary'];
    }

    /**
     *
     */
    function _preview() {
        $this->_loadPageIndex();
        $this->_findMatches();
    }

    /**
     *
     */
    function _loadPageIndex() {
        global $conf;

        if (@file_exists($conf['indexdir'] . '/page.idx')) {
            require_once(DOKU_INC . 'inc/indexer.php');

            $this->pageIndex = idx_getIndex('page', '');

            if (count($this->pageIndex) == 0) {
                throw new Exception('err_emptyidx');
            }
        }
        else {
            throw new Exception('err_idxaccess');
        }
    }

    /**
     *
     */
    function _findMatches() {
        if ($this->namespace != '') {
            $pattern = '/^' . $this->namespace . '/';
        }
        else {
            $pattern = '';
        }

        foreach ($this->pageIndex as $p) {
            $page = trim($p);

            if (($pattern == '') || (preg_match($pattern, $page) == 1)) {
                $this->_findPageMatches($page);
            }
        }

        if (count($this->match) == 0) {
            $this->warning[] = $this->getLang('war_nomatches');
        }
    }

    /**
     *
     */
    function _findPageMatches($page) {
        $text = rawWiki($page);
        $count = preg_match_all($this->regexp, $text, $match, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ($count === FALSE) {
            throw new Exception('err_pregfailed');
        }

        for ($i = 0; $i < $count; $i++) {
            $info['original'] = $match[$i][0][0];
            $info['replaced'] = preg_replace($this->regexp, $this->replacement, $info['original']);
            $info['offest'] = $match[$i][0][1];
            $info['before'] = $this->_getBeforeContext($text, $match[$i]);
            $info['after'] = $this->_getAfterContext($text, $match[$i]);
            $info['apply'] = FALSE;

            $this->match[$page][$i] = $info;
        }
    }

    /**
     *
     */
    function _getBeforeContext($text, $match) {
        $length = 50;
        $offset = $match[0][1] - $length;

        if ($offset < 0) {
            $length += $offset;
            $offset = 0;
        }

        $text = substr($text, $offset, $length);
        $count = preg_match_all('/\n/', $text, $match, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ($count > 3) {
            $text = substr($text, $match[$count - 4][0][1] + 1);
        }

        return $text;
    }

    /**
     *
     */
    function _getAfterContext($text, $match) {
        $offset = $match[0][1] + strlen($match[0][0]);
        $text = substr($text, $offset, 50);
        $count = preg_match_all('/\n/', $text, $match, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ($count > 3) {
            $text = substr($text, 0, $match[3][0][1]);
        }

        return $text;
    }

    /**
     *
     */
    function _apply() {
        $this->_loadPageIndex();
        $this->_findMatches();

        if (isset($_REQUEST['apply'])) {
            if (!is_array($_REQUEST['apply'])) {
                throw new Exception('err_invcmd');
            }

            $this->_markRequested(array_keys($_REQUEST['apply']));
            $this->_applyMatches();
        }
    }

    /**
     *
     */
    function _markRequested($request) {
        foreach ($request as $r) {
            list($page, $offset) = explode('#', $r);

            if (array_key_exists($page, $this->match)) {
                $count = count($this->match[$page]);

                for ($i = 0; $i < $count; $i++) {
                    if ($this->match[$page][$i]['offest'] == $offset) {
                        $this->match[$page][$i]['apply'] = TRUE;
                        break;
                    }
                }
            }
        }
    }

    /**
     *
     */
    function _applyMatches() {
        $page = array_keys($this->match);
        foreach ($page as $p) {
            if ($this->_requiresChanges($p)) {
                if ($this->_isEditAllowed($p)) {
                    $this->_editPage($p);
                }
                else {
                    $this->_unmarkDenied($p);
                }
            }
        }
    }

    /**
     *
     */
    function _requiresChanges($page) {
        $result = FALSE;

        foreach ($this->match[$page] as $info) {
            if ($info['apply']) {
                $result = TRUE;
                break;
            }
        }

        return $result;
    }

    /**
     *
     */
    function _isEditAllowed($page) {
        $allowed = TRUE;

        if (auth_quickaclcheck($page) < AUTH_EDIT) {
            $this->warning[] = $this->getLang('war_norights', $page);
            $allowed = FALSE;
        }

        if ($allowed) {
            $lockedBy = checklock($page);
            if ($lockedBy != FALSE) {
                $this->warning[] = $this->getLang('war_pagelock', $page, $lockedBy);
                $allowed = FALSE;
            }
        }

        return $allowed;
    }

    /**
     *
     */
    function _editPage($page) {
        lock($page);

        $text = rawWiki($page);
        $offset = 0;

        foreach ($this->match[$page] as $info) {
            if ($info['apply']) {
                $originalLength = strlen($info['original']);
                $before = substr($text, 0, $info['offest'] + $offset);
                $after = substr($text, $info['offest'] + $offset + $originalLength);
                $text = $before . $info['replaced'] . $after;
                $offset += strlen($info['replaced']) - $originalLength;
            }
        }

        saveWikiText($page, $text, $this->summary, $this->minorEdit);
        unlock($page);
    }

    /**
     *
     */
    function _unmarkDenied($page) {
        $count = count($this->match[$page]);

        for ($i = 0; $i < $count; $i++) {
            $this->match[$page][$i]['apply'] = FALSE;
        }
    }

    /**
     *
     */
    function _printMatches() {
        $view = $this->getLang('lnk_view');
        $edit = $this->getLang('lnk_edit');
        foreach ($this->match as $page => $match) {
            foreach ($match as $info) {
                $original = $this->_prepareText($info['original'], 'search_hit');
                $replaced = $this->_prepareText($info['replaced'], $info['apply'] ? 'applied' : 'search_hit');
                $before = $this->_prepareText($info['before']);
                $after = $this->_prepareText($info['after']);
                $link = wl($page);
                $id = $page . '#' . $info['offest'];

                $this->_ptln('<div class="file">', +2);
                if (!$info['apply']) {
                    $this->_ptln('<input type="checkbox" id="' . $id . '" name="apply[' . $id . ']" value="on" />');
                }
                $this->_ptln('<label for="' . $id . '">' . $id . '</label>');
                $this->_ptln('<a class="view" href="' . $link . '" title="' . $view . '"></a>');
                $this->_ptln('<a class="edit" href="' . $link . '&do=edit" title="' . $edit . '"></a>');
                $this->_ptln('<table><tr>', +2);
                $this->_ptln('<td class="text">' . $before . $original . $after . '</td>');
                $this->_ptln('<td class="arrow"></td>');
                $this->_ptln('<td class="text">' . $before . $replaced . $after . '</td>');
                $this->_ptln('</tr></table>', -2);
                $this->_ptln('</div>', -2);
                ptln('');
            }
        }
    }

    /**
     * Prepare wiki text to be displayed as html
     */
    function _prepareText($text, $highlight = '') {
        $html = htmlspecialchars($text);
        $html = str_replace( "\n", '<br />', $html);

        if ($highlight != '') {
            $html = '<span class="' . $highlight . '">' . $html . '</span>';
        }

        return $html;
    }

    /**
     *
     */
    function _printMessages() {
        if ((count($this->warning) > 0) || ($this->error != '')) {
            $this->_ptln('<div id="messages">', +2);

            $this->_printWarnings();

            if ($this->error != '') {
                $this->_printError();
            }

            $this->_ptln('</div>', -2);
            ptln('');
        }
    }

    /**
     *
     */
    function _printWarnings() {
        foreach($this->warning as $w) {
            $this->_ptln('<div class="notify">', +2);
            $this->_ptln('<b>Warning:</b> ' . $w);
            $this->_ptln('</div>', -2);
        }
    }

    /**
     *
     */
    function _printError() {
        $this->_ptln('<div class="error">', +2);
        $this->_ptln('<b>Error:</b> ' . $this->error);
        $this->_ptln('</div>', -2);
    }

    /**
     *
     */
    function _printMainForm() {

        $this->_ptln('<div id="mainform">', +2);

        // Output hidden values to ensure dokuwiki will return back to this plugin
        $this->_ptln('<input type="hidden" name="do"   value="admin" />');
        $this->_ptln('<input type="hidden" name="page" value="' . $this->getPluginName() . '" />');

        $this->_ptln('<table>', +2);
        $this->_printFormEdit('lbl_ns', 'namespace');
        $this->_printFormEdit('lbl_regexp', 'regexp');
        $this->_printFormEdit('lbl_replace', 'replace');
        $this->_printFormEdit('lbl_summary', 'summary');
        $this->_ptln('</table>', -2);

        $this->_ptln('<input type="submit" class="button" name="cmd[preview]"  value="' . $this->getLang('btn_preview') . '" />');
        $this->_ptln('<input type="submit" class="button" name="cmd[apply]"  value="' . $this->getLang('btn_apply') . '" />');

        $this->_ptln('</div>', -2);
    }

    /**
     *
     */
    function _printFormEdit($title, $name) {
        $value = '';

        if (isset($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
        }

        $this->_ptln( '<tr>', +2);
        $this->_ptln( '<td class="title"><nobr><b>' . $this->getLang($title) . ':</b></nobr></td>');
        $this->_ptln( '<td class="edit"><input type="text" class="edit" name="' . $name . '" value="' . $value . '" /></td>');

        switch ($name) {
            case 'summary':
                $this->_ptln( '<td style="padding-left: 2em">', +2);
                $this->_printCheckBox('lbl_minor', 'minor');
                $this->_ptln( '</td>', -2);
                break;

            default:
                $this->_ptln( '<td></td>');
                break;
        }

        $this->_ptln( '</tr>', -2);
    }

    /**
     *
     */
    function _printCheckBox($title, $name) {
        $html = '<input type="checkbox" id="' . $name . '" name="' . $name . '" value="on"';

        if (isset($_REQUEST[$name])) {
            $html .= ' checked="checked"';
        }

        $this->_ptln($html . ' />');
        $this->_ptln('<label for="' . $name . '">' . $this->getLang($title) . '</label>');
    }

    /**
     *
     */
    function _ptln($string, $indentDelta = 0) {
        if ($indentDelta < 0) {
            $this->indent += $indentDelta;
        }

        ptln($string, $this->indent);

        if ($indentDelta > 0) {
            $this->indent += $indentDelta;
        }
    }
}
