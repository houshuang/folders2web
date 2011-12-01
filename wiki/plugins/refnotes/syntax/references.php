<?php

/**
 * Plugin RefNotes: Reference collector/renderer
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

/* Must be run within Dokuwiki */
if (!defined('DOKU_INC') || !defined('DOKU_PLUGIN')) die();

require_once(DOKU_PLUGIN . 'syntax.php');
require_once(DOKU_PLUGIN . 'refnotes/info.php');
require_once(DOKU_PLUGIN . 'refnotes/locale.php');
require_once(DOKU_PLUGIN . 'refnotes/config.php');
require_once(DOKU_PLUGIN . 'refnotes/namespace.php');

class syntax_plugin_refnotes_references extends DokuWiki_Syntax_Plugin {

    private $mode;
    private $entrySyntax;
    private $exitSyntax;
    private $entryPattern;
    private $exitPattern;
    private $handlePattern;
    private $core;
    private $database;
    private $handling;
    private $embedding;
    private $capturedNote;
    private $docBackup;

    /**
     * Constructor
     */
    public function __construct() {
        $this->mode = substr(get_class($this), 7);
        $this->entrySyntax = '[(';
        $this->exitSyntax = ')]';
        $this->core = NULL;
        $this->database = NULL;
        $this->handling = false;
        $this->embedding = false;
        $this->capturedNote = NULL;
        $this->docBackup = '';

        $this->initializePatterns();
    }

    /**
     *
     */
    private function initializePatterns() {
        if (refnotes_configuration::getSetting('replace-footnotes')) {
            $entry = '(?:\(\(|\[\()';
            $exit = '(?:\)\)|\)\])';
            $name ='(?:@@FNT\d+|#\d+|[[:alpha:]]\w*)';
        }
        else {
            $entry = '\[\(';
            $exit = '\)\]';
            $name ='(?:#\d+|[[:alpha:]]\w*)';
        }

        $namespace ='(?:(?:[[:alpha:]]\w*)?:)*';
        $text = '.*?';

        $nameMatch = '\s*' . $namespace . $name .'\s*';
        $lookaheadExit = '(?=' . $exit . ')';
        $nameEntry = $nameMatch . $lookaheadExit;

        $optionalName = $name .'?';
        $define = '\s*' . $namespace . $optionalName .'\s*>';
        $optionalDefine = '(?:' . $define . ')?';
        $lookaheadExit = '(?=' . $text . $exit . ')';
        $defineEntry = $optionalDefine . $lookaheadExit;

        $this->entryPattern = $entry . '(?:' . $nameEntry . '|' . $defineEntry . ')';
        $this->exitPattern = $exit;
        $this->handlePattern = '/(\s*)' . $entry . '\s*(' . $namespace . $optionalName . ').*/';
    }

    /**
     * Return some info
     */
    public function getInfo() {
        return refnotes_getInfo('references syntax');
    }

    /**
     * What kind of syntax are we?
     */
    public function getType() {
        return 'formatting';
    }

    public function accepts($mode) {
        if ($mode == $this->mode) {
            return true;
        }

        return parent::accepts($mode);
    }

    /**
     * What modes are allowed within our mode?
     */
    public function getAllowedTypes() {
        return array (
            'formatting',
            'substition',
            'protected',
            'disabled'
        );
    }

    /**
     * Where to sort in?
     */
    public function getSort() {
        return 145;
    }

    public function connectTo($mode) {
        $this->Lexer->addEntryPattern($this->entryPattern, $mode, $this->mode);
    }

    public function postConnect() {
        $this->Lexer->addExitPattern($this->exitPattern, $this->mode);
    }

    /**
     * Handle the match
     */
    public function handle($match, $state, $pos, $handler) {
        switch ($state) {
            case DOKU_LEXER_ENTER:
                if (!$this->handling) {
                    return $this->handleEnter($match, $pos, $handler);
                }
                break;

            case DOKU_LEXER_EXIT:
                if ($this->handling) {
                    return $this->handleExit($match, $pos);
                }
                break;
        }

        $handler->_addCall('cdata', array($match), $pos);

        return false;
    }

    /**
     * Create output
     */
    public function render($mode, $renderer, $data) {
        $result = false;

        try {
            switch ($mode) {
                case 'xhtml':
                    $result = $this->renderXhtml($renderer, $data);
                    break;

                case 'metadata':
                    $result = $this->renderMetadata($renderer, $data);
                    break;
            }
        }
        catch (Exception $error) {
            msg($error->getMessage(), -1);
        }

        return $result;
    }

    /**
     *
     */
    private function handleEnter($syntax, $pos, $handler) {
        if (preg_match($this->handlePattern, $syntax, $match) == 0) {
            return false;
        }

        list($namespace, $name) = refnotes_parseName($match[2]);

        if (!$this->embedding && ($name != '')) {
            $this->embedding = true;
            $fullName = $namespace . $name;
            $database = $this->getDatabase();

            if ($database->isDefined($fullName)) {
                $this->embedPredefinedNote($database->getNote($fullName), $pos, $handler);
            }

            $this->embedding = false;
        }

        $this->handling = true;

        $info['ns'] = $namespace;
        $info['name'] = $name;

        return array(DOKU_LEXER_ENTER, $info);
    }

    /**
     *
     */
    private function getDatabase() {
        if ($this->database == NULL) {
            $locale = new refnotes_localization($this);
            $this->database = new refnotes_reference_database($locale);
        }

        return $this->database;
    }

    /**
     *
     */
    private function embedPredefinedNote($note, $pos, $handler) {
        $text = $this->entrySyntax . $note['name'] . '>' . $note['text'] . $this->exitSyntax;
        $callWriter = new refnotes_nested_call_writer($handler->CallWriter);

        $this->parseNestedText($text, $handler, $callWriter);
        $callWriter->process($note['inline'], $note['source'], $pos);
    }

    /**
     *
     */
    private function parseNestedText($text, $handler, $nestedWriter) {
        $callWriterBackup = $handler->CallWriter;
        $handler->CallWriter = $nestedWriter;

        /*
            HACK: If doku.php parses a number of pages during one call (it's common after the cache
            clean-up) $this->Lexer can be a different instance from the one used in the current parser
            pass. Here we ensure that $handler is linked to $this->Lexer while parsing the nested text.
        */
        $handlerBackup = $this->Lexer->_parser;
        $this->Lexer->_parser = $handler;

        $this->Lexer->parse($text);

        $this->Lexer->_parser = $handlerBackup;
        $handler->CallWriter = $callWriterBackup;
    }

    /**
     *
     */
    private function handleExit($syntax, $pos) {
        $this->handling = false;

        return array(DOKU_LEXER_EXIT);
    }

    /**
     *
     */
    public function renderXhtml($renderer, $data) {
        switch ($data[0]) {
            case DOKU_LEXER_ENTER:
                $this->renderXhtmlEnter($renderer, $data[1]);
                break;

            case DOKU_LEXER_EXIT:
                $this->renderXhtmlExit($renderer);
                break;
        }

        return true;
    }

    /**
     * Renders reference link and starts renderer output capture
     */
    private function renderXhtmlEnter($renderer, $info) {
        $core = $this->getCore();

        $inline = false;
        if (array_key_exists('inline', $info)) {
            $inline = $info['inline'];
        }

        $note = $core->addReference($info['ns'], $info['name'], $info['hidden'], $inline);
        if (($note != NULL) && !$info['hidden']) {
            $renderer->doc .= $note->renderReference();
        }

        $this->startCapture($renderer, $note);
    }

    /**
     * Stops renderer output capture
     */
    private function renderXhtmlExit($renderer) {
        $this->stopCapture($renderer);
    }

    /**
     *
     */
    public function renderMetadata($renderer, $data) {
        if ($data[0] == DOKU_LEXER_ENTER) {
            $source = '';

            if ( array_key_exists('source', $data[1])) {
                $source = $data[1]['source'];
            }

            if (($source != '') && ($source != '{configuration}')) {
                $renderer->meta['plugin']['refnotes']['dbref'][wikiFN($source)] = true;
            }
        }

        return true;
    }

    /**
     *
     */
    private function getCore() {
        if ($this->core == NULL) {
            $this->core = plugin_load('helper', 'refnotes');
            if ($this->core == NULL) {
                throw new Exception('Helper plugin "refnotes" is not available or invalid.');
            }
        }

        return $this->core;
    }

    /**
     * Starts renderer output capture
     */
    private function startCapture($renderer, $note) {
        $this->capturedNote = $note;
        $this->docBackup = $renderer->doc;
        $renderer->doc = '';
    }

    /**
     * Stops renderer output capture
     */
    private function stopCapture($renderer) {
        $text = trim($renderer->doc);
        if ($text != '') {
            $this->capturedNote->setText($text);
        }

        $renderer->doc = $this->docBackup;
        $this->capturedNote = NULL;
        $this->docBackup = '';
    }
}

class refnotes_nested_call_writer extends Doku_Handler_Nest {

    /**
     *
     */
    public function process($inline, $source, $pos) {
        $index = $this->findFirstReference();

        if ($index >= 0) {
            if ($inline) {
                $this->calls[$index][1][1][1]['inline'] = true;
            }

            $this->calls[$index][1][1][1]['source'] = $source;
            $this->calls[$index][1][1][1]['hidden'] = true;

            $this->CallWriter->writeCall(array("nest", array($this->calls), $pos));
        }
    }

    /**
     *
     */
    private function findFirstReference() {
        $index = -1;
        $calls = count($this->calls);

        for ($c = 0; $c < $calls; $c++) {
            if (($this->calls[$c][0] == 'plugin') &&
                ($this->calls[$c][1][0] == 'refnotes_references') &&
                ($this->calls[$c][1][1][0] == DOKU_LEXER_ENTER)) {
                $index = $c;
            }
        }

        return $index;
    }
}

class refnotes_reference_database {

    private $note;
    private $key;
    private $noteRenderer;
    private $page;
    private $namespace;

    /**
     * Constructor
     */
    public function __construct($locale) {
        $this->page = array();
        $this->namespace = array();

        $this->loadNotesFromConfiguration();

        if (refnotes_configuration::getSetting('reference-db-enable')) {
            $this->noteRenderer['basic'] = new refnotes_basic_note_renderer();
            $this->noteRenderer['harvard'] = new refnotes_harvard_note_renderer($locale);

            $this->loadKeys($locale);
            $this->loadPages();
            $this->loadNamespaces();
        }
    }

    /**
     *
     */
    private function loadNotesFromConfiguration() {
        $this->note = refnotes_configuration::load('notes');

        foreach ($this->note as &$note) {
            $note['source'] = '{configuration}';
        }
    }

    /**
     *
     */
    private function loadKeys($locale) {
        foreach ($locale->getByPrefix('dbk') as $key => $text) {
            $this->key[$this->normalizeKeyText($text)] = $key;
        }
    }

    /**
     *
     */
    public function getKey($text) {
        $result = '';
        $text = $this->normalizeKeyText($text);

        if (array_key_exists($text, $this->key)) {
            $result = $this->key[$text];
        }

        return $result;
    }

    /**
     *
     */
    public function getNoteRenderer($name) {
        if (array_key_exists($name, $this->noteRenderer)) {
            $result = $this->noteRenderer[$name];
        }
        else {
            $result = $this->noteRenderer['basic'];
        }

        return $result;
    }

    /**
     *
     */
    private function normalizeKeyText($text) {
        return preg_replace('/\s+/', ' ', utf8_strtolower(trim($text)));
    }

    /**
     *
     */
    private function loadPages() {
        global $conf;

        if (file_exists($conf['indexdir'] . '/page.idx')) {
            require_once(DOKU_INC . 'inc/indexer.php');

            $pageIndex = idx_getIndex('page', '');
            $namespace = refnotes_configuration::getSetting('reference-db-namespace');
            $namespacePattern = '/^' . trim($namespace, ':') . ':/';
            $cache = new refnotes_reference_database_cache();

            foreach ($pageIndex as $pageId) {
                $pageId = trim($pageId);

                if ((preg_match($namespacePattern, $pageId) == 1) && file_exists(wikiFN($pageId))) {
                    $this->page[$pageId] = new refnotes_reference_database_page($this, $cache, $pageId);
                }
            }

            $cache->save();
        }
    }

    /**
     *
     */
    private function loadNamespaces() {
        foreach ($this->page as $pageId => $page) {
            foreach ($page->getNamespaces() as $ns) {
                $this->namespace[$ns][] = $pageId;
            }
        }
    }

    /**
     *
     */
    public function isDefined($name) {
        $result = array_key_exists($name, $this->note);

        if (!$result) {
            list($namespace, $temp) = refnotes_parseName($name);

            if (array_key_exists($namespace, $this->namespace)) {
                $this->loadNamespaceNotes($namespace);

                $result = array_key_exists($name, $this->note);
            }
        }

        return $result;
    }

    /**
     *
     */
    private function loadNamespaceNotes($namespace) {
        foreach ($this->namespace[$namespace] as $pageId) {
            if (array_key_exists($pageId, $this->page)) {
                $this->note = array_merge($this->note, $this->page[$pageId]->getNotes());

                unset($this->page[$pageId]);
            }
        }

        unset($this->namespace[$namespace]);
    }

    /**
     *
     */
    public function getNote($name) {
        return array_merge(array('name' => $name), $this->note[$name]);
    }
}

class refnotes_reference_database_page {

    private $database;
    private $id;
    private $fileName;
    private $namespace;
    private $note;

    /**
     * Constructor
     */
    public function __construct($database, $cache, $id) {
        $this->database = $database;
        $this->id = $id;
        $this->fileName = wikiFN($id);
        $this->namespace = array();
        $this->note = array();

        if ($cache->isCached($this->fileName)) {
            $this->namespace = $cache->getNamespaces($this->fileName);
        }
        else {
            $this->parse();

            $cache->update($this->fileName, $this->namespace);
        }
    }

    /**
     *
     */
    private function parse() {
        $text = io_readWikiPage($this->fileName, $this->id);
        $call = p_cached_instructions($this->fileName);
        $calls = count($call);

        for ($c = 0; $c < $calls; $c++) {
            if ($call[$c][0] == 'table_open') {
                $c = $this->parseTable($call, $calls, $c, $text);
            }
        }
    }

    /**
     *
     */
    private function parseTable($call, $calls, $c, $text) {
        $row = 0;
        $column = 0;
        $columns = 0;
        $valid = true;

        for ( ; $c < $calls; $c++) {
            switch ($call[$c][0]) {
                case 'tablerow_open':
                    $column = 0;
                    break;

                case 'tablerow_close':
                    if ($row == 0) {
                        $columns = $column;
                    }
                    else {
                        if ($column != $columns) {
                            $valid = false;
                            break 2;
                        }
                    }
                    $row++;
                    break;

                case 'tablecell_open':
                case 'tableheader_open':
                    $cellOpen = $call[$c][2];
                    break;

                case 'tablecell_close':
                case 'tableheader_close':
                    $table[$row][$column] = trim(substr($text, $cellOpen, $call[$c][2] - $cellOpen), "^| ");
                    $column++;
                    break;

                case 'table_close':
                    break 2;
            }
        }

        if ($valid && ($row > 1) && ($columns > 1)) {
            $this->handleTable($table, $columns, $row);
        }

        return $c;
    }

    /**
     *
     */
    private function handleTable($table, $columns, $rows) {
        $key = array();
        for ($c = 0; $c < $columns; $c++) {
            $key[$c] = $this->database->getKey($table[0][$c]);
        }

        if (!in_array('', $key)) {
            $this->handleDataSheet($table, $columns, $rows, $key);
        }
        else {
            if ($columns == 2) {
                $key = array();
                for ($r = 0; $r < $rows; $r++) {
                    $key[$r] = $this->database->getKey($table[$r][0]);
                }

                if (!in_array('', $key)) {
                    $this->handleDataCard($table, $rows, $key);
                }
            }
        }
    }

    /**
     * The data is organized in rows, one note per row. The first row contains the caption.
     */
    private function handleDataSheet($table, $columns, $rows, $key) {
        for ($r = 1; $r < $rows; $r++) {
            $field = array();

            for ($c = 0; $c < $columns; $c++) {
                $field[$key[$c]] = $table[$r][$c];
            }

            $this->handleNote($field);
        }
    }

    /**
     * Every note is stored in a separate table. The first column of the table contains
     * the caption, the second one contains the data.
     */
    private function handleDataCard($table, $rows, $key) {
        $field = array();

        for ($r = 0; $r < $rows; $r++) {
            $field[$key[$r]] = $table[$r][1];
        }

        $this->handleNote($field);
    }

    /**
     *
     */
    private function handleNote($field) {
        $name = '';
        $note = array('text' => '', 'inline' => false, 'source' => $this->id);

        if (array_key_exists('note-name', $field)) {
            if (preg_match('/(?:(?:[[:alpha:]]\w*)?:)*[[:alpha:]]\w*/', $field['note-name']) == 1) {
                list($namespace, $name) = refnotes_parseName($field['note-name']);
                $name = $namespace . $name;
            }

            $note['text'] = $this->renderNoteText($field);
        }

        if (($name != '') && ($note['text'] != '')) {
            if (!in_array($namespace, $this->namespace)) {
                $this->namespace[] = $namespace;
            }

            $this->note[$name] = $note;
        }
    }

    /**
     *
     */
    private function renderNoteText($field) {
        $renderer = '';

        if (array_key_exists('note-text', $field)) {
            $renderer = 'basic';
        }
        elseif (array_key_exists('title', $field)) {
            $renderer = 'harvard';
        }

        if ($renderer != '') {
            $text = $this->database->getNoteRenderer($renderer)->render($field);
        }
        else {
            $text = '';
        }

        return $text;
    }

    /**
     *
     */
    public function getNamespaces() {
        return $this->namespace;
    }

    /**
     *
     */
    public function getNotes() {
        if (count($this->note) == 0) {
            $this->parse();
        }

        return $this->note;
    }
}

class refnotes_basic_note_renderer {

    /**
     *
     */
    public function render($field) {
        $text = $field['note-text'];

            $text = '[[' . $field['note-name'] . '|' . $text . ']]';

        return $text;
    }
}

class refnotes_harvard_note_renderer {

    private $locale;

    /**
     * Constructor
     */
    public function __construct($locale) {
        $this->locale = $locale;
    }

    /**
     *
     */
    public function render($field) {
        // authors, published. //[[url|title.]]// edition. publisher, pages, isbn.
        // authors, published. chapter In //[[url|title.]]// edition. publisher, pages, isbn.
        // authors, published. [[url|title.]] //journal//, volume, publisher, pages, issn.

        $title = $this->renderTitle($field);

        // authors, published. //$title// edition. publisher, pages, isbn.
        // authors, published. chapter In //$title// edition. publisher, pages, isbn.
        // authors, published. $title //journal//, volume, publisher, pages, issn.

        $authors = $this->renderAuthors($field);

        // $authors? //$title// edition. publisher, pages, isbn.
        // $authors? chapter In //$title// edition. publisher, pages, isbn.
        // $authors? $title //journal//, volume, publisher, pages, issn.

        $publication = $this->renderPublication($field, $authors != '');

        if (array_key_exists('journal', $field)) {
            // $authors? $title //journal//, volume, $publication?

            $text = $title . ' ' . $this->renderJournal($field);

            // $authors? $text, $publication?

            $text .= ($publication != '') ? ',' : '.';
        }
        else {
            // $authors? //$title// edition. $publication?
            // $authors? chapter In //$title// edition. $publication?

            $text = $this->renderBook($field, $title);
        }

        // $authors? $text $publication?

        if ($authors != '') {
            $text = $authors . ' ' . $text;
        }

        if ($publication != '') {
            $text .= ' ' . $publication;
        }

        return $text;
    }

    /**
     *
     */
    private function renderTitle($field) {
        $text = $field['title'] . '.';
		if (page_exists($field['note-name'])) {
            $text = '[[' . $field['note-name'] . '|' . $text . ']]';};
        return $text;
    }

    /**
     *
     */
    private function renderAuthors($field) {
        $text = '';

        if (array_key_exists('authors', $field)) {
            $text = $field['authors'];

            if (array_key_exists('published', $field)) {
                $text .= ', ' . $field['published'];
            }

            $text .= '.';
        }

        return $text;
    }

    /**
     *
     */
    private function renderPublication($field, $authors) {
        $part = array();

        if (array_key_exists('publisher', $field)) {
            $part[] = $field['publisher'];
        }

        if (!$authors && array_key_exists('published', $field)) {
            $part[] = $field['published'];
        }

        if (array_key_exists('pages', $field)) {
            $part[] = $field['pages'];
        }

        if (array_key_exists('isbn', $field)) {
            $part[] = 'ISBN ' . $field['isbn'];
        }
        elseif (array_key_exists('issn', $field)) {
            $part[] = 'ISSN ' . $field['issn'];
        }

        $text = implode(', ', $part);

        if ($text != '') {
            $text = rtrim($text, '.') . '.';
        }

        return $text;
    }

    /**
     *
     */
    private function renderJournal($field) {
        $text = '//' . $field['journal'] . '//';

        if (array_key_exists('volume', $field)) {
            $text .= ', ' . $field['volume'];
        }

        return $text;
    }

    /**
     *
     */
    private function renderBook($field, $title) {
        $text = '//' . $title . '//';

        if (array_key_exists('chapter', $field)) {
            $text = $field['chapter'] . '. ' . $this->locale->getLang('txt_in_cap') . ' ' . $text;
        }

        if (array_key_exists('edition', $field)) {
            $text .= ' ' . $field['edition'] . '.';
        }

        return $text;
    }
}

class refnotes_reference_database_cache {

    private $fileName;
    private $cache;
    private $requested;
    private $updated;

    /**
     * Constructor
     */
    public function __construct() {
        $this->fileName = DOKU_PLUGIN . 'refnotes/database.dat';

        $this->load();
    }

    /**
     *
     */
    private function load() {
        $this->cache = array();
        $this->requested = array();

        if (file_exists($this->fileName)) {
            $this->cache = unserialize(io_readFile($this->fileName, false));
        }

        foreach (array_keys($this->cache) as $fileName) {
            $this->requested[$fileName] = false;
        }

        $this->updated = false;
    }

    /**
     *
     */
    public function isCached($fileName) {
        $result = false;

        if (array_key_exists($fileName, $this->cache)) {
            if ($this->cache[$fileName]['time'] == @filemtime($fileName)) {
                $result = true;
            }
        }

        $this->requested[$fileName] = true;

        return $result;
    }

    /**
     *
     */
    public function getNamespaces($fileName) {
        return $this->cache[$fileName]['ns'];
    }

    /**
     *
     */
    public function update($fileName, $namespace) {
        $this->cache[$fileName] = array('ns' => $namespace, 'time' => @filemtime($fileName));
        $this->updated = true;
    }

    /**
     *
     */
    public function save() {
        $this->removeOldPages();

        if ($this->updated) {
            io_saveFile($this->fileName, serialize($this->cache));
        }
    }

    /**
     *
     */
    private function removeOldPages() {
        foreach ($this->requested as $fileName => $requested) {
            if (!$requested && array_key_exists($fileName, $this->cache)) {
                unset($this->cache[$fileName]);

                $this->updated = true;
            }
        }
    }
}
