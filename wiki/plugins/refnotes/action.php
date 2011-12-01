<?php

/**
 * Plugin RefNotes: Default renderer
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

/* Must be run within Dokuwiki */
if (!defined('DOKU_INC') || !defined('DOKU_PLUGIN')) die();

require_once(DOKU_INC . 'inc/JSON.php');
require_once(DOKU_PLUGIN . 'action.php');
require_once(DOKU_PLUGIN . 'refnotes/info.php');
require_once(DOKU_PLUGIN . 'refnotes/config.php');
require_once(DOKU_PLUGIN . 'refnotes/namespace.php');

class action_plugin_refnotes extends DokuWiki_Action_Plugin {

    private $scopeStart;
    private $scopeEnd;
    private $style;
    private $hidden;
    private $inReference;

    /**
     * Return some info
     */
    public function getInfo() {
        return refnotes_getInfo('default notes renderer');
    }

    /**
     * Register callbacks
     */
    public function register($controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'beforeAjaxCallUnknown');
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'beforeTplMetaheaderOutput');
        $controller->register_hook('PARSER_HANDLER_DONE', 'AFTER', $this, 'afterParserHandlerDone');
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'beforeParserCacheUse');
    }

    /**
     *
     */
    public function beforeAjaxCallUnknown($event, $param) {
        if ($event->data == 'refnotes-admin') {
            $event->preventDefault();
            $event->stopPropagation();

            /* Check admin rights */
            if (auth_quickaclcheck($conf['start']) < AUTH_ADMIN) {
                die('access denied');
            }

            switch ($_POST['action']) {
                case 'load-settings':
                    $this->sendConfig();
                    break;

                case 'save-settings':
                    $this->saveConfig($_POST['settings']);
                    break;
            }
        }
    }

    /**
     *
     */
    private function sendConfig() {
        $namespace = refnotes_configuration::load('namespaces');
        $namespace = $this->translateStyles($namespace, 'dw', 'js');

        $config['cookie'] = '{B27067E9-3DDA-4E31-9768-E66F23D18F4A}';
        $config['general'] = refnotes_configuration::load('general');
        $config['namespaces'] = $namespace;
        $config['notes'] = refnotes_configuration::load('notes');

        $json = new JSON();

        header('Content-Type: application/x-suggestions+json');
        print($json->encode($config));
    }

    /**
     *
     */
    private function saveConfig($config) {
        global $config_cascade;

        $json = new JSON(JSON_LOOSE_TYPE);

        $config = $json->decode($config);

        $namespace = $config['namespaces'];
        $namespace = $this->translateStyles($namespace, 'js', 'dw');

        $saved = refnotes_configuration::save('general', $config['general']);
        $saved = $saved && refnotes_configuration::save('namespaces', $namespace);
        $saved = $saved && refnotes_configuration::save('notes', $config['notes']);

        if ($config['general']['reference-db-enable']) {
            $saved = $saved && $this->setupReferenceDatabase($config['general']['reference-db-namespace']);
        }

        /* Touch local config file to expire the cache */
        $saved = $saved && touch(reset($config_cascade['main']['local']));

        header('Content-Type: text/plain');
        print($saved ? 'saved' : 'failed');
    }

    /**
     *
     */
    private function translateStyles($namespace, $from, $to) {
        foreach ($namespace as &$ns) {
            foreach ($ns as $styleName => &$style) {
                $style = $this->translateStyle($styleName, $style, $from, $to);
            }
        }

        return $namespace;
    }

    /**
     *
     */
    private function translateStyle($styleName, $style, $from, $to) {
        static $dictionary = array(
            'refnote-id' => array(
                'dw' => array('1'      , 'a'          , 'A'          , 'i'          , 'I'          , '*'    , 'name'     ),
                'js' => array('numeric', 'latin-lower', 'latin-upper', 'roman-lower', 'roman-upper', 'stars', 'note-name')
            ),
            'reference-base' => array(
                'dw' => array('sup'  , 'text'       ),
                'js' => array('super', 'normal-text')
            ),
            'reference-format' => array(
                'dw' => array(')'           , '()'     , ']'            , '[]'      ),
                'js' => array('right-parent', 'parents', 'right-bracket', 'brackets')
            ),
            'multi-ref-id' => array(
                'dw' => array('ref'        , 'note'   ),
                'js' => array('ref-counter', 'note-counter')
            ),
            'note-id-base' => array(
                'dw' => array('sup'  , 'text'       ),
                'js' => array('super', 'normal-text')
            ),
            'note-id-format' => array(
                'dw' => array(')'           , '()'     , ']'            , '[]'      , '.'  ),
                'js' => array('right-parent', 'parents', 'right-bracket', 'brackets', 'dot')
            ),
            'back-ref-base' => array(
                'dw' => array('sup'  , 'text'       ),
                'js' => array('super', 'normal-text')
            ),
            'back-ref-format' => array(
                'dw' => array('1'      , 'a'    , 'note'   ),
                'js' => array('numeric', 'latin', 'note-id')
            ),
            'back-ref-separator' => array(
                'dw' => array(','    ),
                'js' => array('comma')
            )
        );

        if (array_key_exists($styleName, $dictionary)) {
            $key = array_search($style, $dictionary[$styleName][$from]);

            if ($key !== false) {
                $style = $dictionary[$styleName][$to][$key];
            }
        }

        return $style;
    }

    /**
     *
     */
    private function setupReferenceDatabase($namespace) {
        $success = true;
        $source = $this->localFN('__template');
        $destination = wikiFN(cleanID($namespace . ':template'));
        $destination = preg_replace('/template.txt$/', '__template.txt', $destination);

        if (@filemtime($destination) < @filemtime($source)) {
            if (!file_exists(dirname($destination))) {
                @mkdir(dirname($destination), 0755, true);
            }

            $success = copy($source, $destination);

            touch($destination, filemtime($source));
        }

        return $success;
    }

    /**
     *
     */
    public function beforeTplMetaheaderOutput($event, $param) {
        if (($_REQUEST['do'] == 'admin') && !empty($_REQUEST['page']) && ($_REQUEST['page'] == 'refnotes')) {
            $this->addAdminIncludes($event);
        }
    }

    /**
     *
     */
    private function addAdminIncludes($event) {
        $this->addTemplateHeaderInclude($event, 'admin.js');
        $this->addTemplateHeaderInclude($event, 'json2.js');
        $this->addTemplateHeaderInclude($event, 'admin.css');
    }

    /**
     *
     */
    private function addTemplateHeaderInclude($event, $fileName) {
        $type = '';
        $fileName = DOKU_BASE . 'lib/plugins/refnotes/' . $fileName;

        switch (pathinfo($fileName, PATHINFO_EXTENSION)) {
            case 'js':
                $type = 'script';
                $data = array('type' => 'text/javascript', 'charset' => 'utf-8', 'src' => $fileName, '_data' => '');
                break;

            case 'css':
                $type = 'link';
                $data = array('type' => 'text/css', 'rel' => 'stylesheet', 'href' => $fileName);
                break;
        }

        if ($type != '') {
            $event->data[$type][] = $data;
        }
    }

    /**
     *
     */
    public function afterParserHandlerDone($event, $param) {
        $this->reset();
        $this->scanInstructions($event);

        if (count($this->style) > 0) {
            $this->sortStyles();
            $this->insertStyles($event);
        }

        if (count($this->scopeStart) > 0) {
            $this->renderLeftovers($event);
        }
    }

    /**
     * Reset internal state
     */
    private function reset() {
        $this->scopeStart = array();
        $this->scopeEnd = array();
        $this->style = array();
        $this->hidden = true;
        $this->inReference = false;
    }

    /**
     *
     */
    private function scanInstructions($event) {
        $count = count($event->data->calls);
        for ($i = 0; $i < $count; $i++) {
            $call =& $event->data->calls[$i];

            $this->updateHidden($call);

            if ($call[0] == 'plugin') {
                switch ($call[1][0]) {
                    case 'refnotes_references':
                        $this->handleReference($i, $call[1][1]);
                        break;

                    case 'refnotes_notes':
                        $this->handleNotes($i, $call[1][1]);
                        break;
                }
            }
        }
    }

    /**
     * Determine if references following the current instruction should be hidden
     */
    private function updateHidden($call) {
        $name = ($call[0] == 'plugin') ? 'plugin_' . $call[1][0] : $call[0];

        switch ($name) {
            case 'p_open':
                $this->hidden = true;
                break;

            case 'cdata':
                if (!$this->inReference && (trim($call[1][0]) != '')) {
                    $this->hidden = false;
                }
                break;

            case 'plugin_refnotes_references':
                $this->inReference = ($call[1][1][0] == DOKU_LEXER_ENTER);
                break;

            default:
                if (!$this->inReference) {
                    $this->hidden = false;
                }
                break;
        }
    }

    /**
     *
     */
    private function handleReference($callIndex, &$callData) {
        if ($callData[0] == DOKU_LEXER_ENTER) {
            $this->markScopeStart($callData[1]['ns'], $callIndex);

            if ($this->hidden) {
                $callData[1]['hidden'] = true;
            }
        }
    }

    /**
     * Mark instruction that starts a scope
     */
    private function markScopeStart($namespace, $callIndex) {
        if (array_key_exists($namespace, $this->scopeStart)) {
            if (count($this->scopeStart[$namespace]) < count($this->scopeEnd[$namespace])) {
                $this->scopeStart[$namespace][] = $callIndex;
            }
        }
        else {
            $this->markScopeEnd($namespace, -1);
            $this->scopeStart[$namespace][] = $callIndex;
        }
    }

    /**
     * Mark instruction that ends a scope
     */
    private function markScopeEnd($namespace, $callIndex) {
        $this->scopeEnd[$namespace][] = $callIndex;
    }

    /**
     * Extract style data and replace "split" instructions with "render"
     */
    private function handleNotes($callIndex, &$callData) {
        $namespace = $callData[1]['ns'];
        if ($callData[0] == 'split') {
            if (array_key_exists('inherit', $callData[2])) {
                $index = $this->getStyleIndex($namespace, $callData[2]['inherit']);
            }
            else {
                $index = $this->getStyleIndex($namespace);
            }

            $this->style[] = array('idx' => $index, 'ns' => $namespace, 'data' => $callData[2]);
            $callData[0] = 'render';
            unset($callData[2]);
        }

        $this->markScopeEnd($namespace, $callIndex);
    }

    /**
     * Returns instruction index where the style instruction has to be inserted
     */
    private function getStyleIndex($namespace, $parent = '') {
        if (($parent == '') && (count($this->scopeStart[$namespace]) == 1)) {
            /* Default inheritance for the first scope */
            $parent = refnotes_getParentNamespace($namespace);
        }

        $index = end($this->scopeEnd[$namespace]) + 1;

        if ($parent != '') {
            $start = end($this->scopeStart[$namespace]);
            $end = end($this->scopeEnd[$namespace]);

            while ($parent != '') {
                if (array_key_exists($parent, $this->scopeEnd)) {
                    for ($i = count($this->scopeEnd[$parent]) - 1; $i >= 0; $i--) {
                        $parentEnd = $this->scopeEnd[$parent][$i];
                        if (($parentEnd >= $end) && ($parentEnd < $start)) {
                            $index = $parentEnd + 1;
                            break 2;
                        }
                    }
                }

                $parent = refnotes_getParentNamespace($parent);
            }
        }

        return $index;
    }

    /**
     * Sort the style blocks so that the namespaces with inherited style go after
     * the namespaces they inherit from
     */
    private function sortStyles() {
        /* Sort in ascending order to ensure the default enheritance */
        foreach ($this->style as $key => $style) {
            $index[$key] = $style['idx'];
            $namespace[$key] = $style['ns'];
        }
        array_multisort($index, SORT_ASC, $namespace, SORT_ASC, $this->style);

        /* Sort to ensure explicit enheritance */
        foreach ($this->style as $style) {
            $bucket[$style['idx']][] = $style;
        }

        $this->style = array();

        foreach ($bucket as $b) {
            $inherit = array();
            foreach ($b as $style) {
                if (array_key_exists('inherit', $style['data'])) {
                    $inherit[] = $style;
                }
                else {
                    $this->style[] = $style;
                }
            }

            $inherits = count($inherit);
            if ($inherits > 0) {
                if ($inherits > 1) {
                    /* Perform simplified topological sorting */
                    $target = array();
                    $source = array();

                    for ($i = 0; $i < $inherits; $i++) {
                        $target[$i] = $inherit[$i]['ns'];
                        $source[$i] = $inherit[$i]['data']['inherit'];
                    }

                    for ($i = 0; $i < $inherits; $i++) {
                        foreach ($source as $index => $s) {
                            if (!in_array($s, $target)) {
                                break;
                            }
                        }
                        $this->style[] = $inherit[$index];
                        unset($target[$index]);
                        unset($source[$index]);
                    }
                }
                else {
                    $this->style[] = $inherit[0];
                }
            }
        }
    }

    /**
     * Insert style instructions
     */
    private function insertStyles($event) {
        $calls = count($event->data->calls);
        $styles = count($this->style);
        $call = array();

        for ($c = 0, $s = 0; $c < $calls; $c++) {
            while (($s < $styles) && ($this->style[$s]['idx'] == $c)) {
                $attribute['ns'] = $this->style[$s]['ns'];
                $data[0] = 'style';
                $data[1] = $attribute;
                $data[2] = $this->style[$s]['data'];
                $call[] = $this->getInstruction($data, $event->data->calls[$c][2]);
                $s++;
            }

            $call[] = $event->data->calls[$c];
        }

        $event->data->calls = $call;
    }

    /**
     * Insert render call at the very bottom of the page
     */
    private function renderLeftovers($event) {
        $attribute['ns'] = '*';
        $data[0] = 'render';
        $data[1] = $attribute;
        $lastCall = end($event->data->calls);
        $call = $this->getInstruction($data, $lastCall[2]);

        $event->data->calls[] = $call;
    }

    /**
     * Format data into plugin instruction
     */
    private function getInstruction($data, $offset) {
        $parameters = array('refnotes_notes', $data, DOKU_LEXER_SPECIAL, '');

        return array('plugin', $parameters, $offset);
    }

    /**
     *
     */
    public function beforeParserCacheUse($event, $param) {
        global $ID;

        $cache = $event->data;

        if (isset($cache->page) && ($cache->page == $ID)) {
            if (isset($cache->mode) && (($cache->mode == 'xhtml') || ($cache->mode == 'i'))) {
                $meta = p_get_metadata($ID, 'plugin refnotes');

                if (!empty($meta) && isset($meta['dbref'])) {
                    $this->addDependencies($cache, array_keys($meta['dbref']));
                }
            }
        }
    }

    /**
     * Add extra dependencies to the cache
     */
    private function addDependencies($cache, $depends) {
        foreach ($depends as $file) {
            if (!in_array($file, $cache->depends['files']) && file_exists($file)) {
                $cache->depends['files'][] = $file;
            }
        }
    }
}
