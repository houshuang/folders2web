<?php

/**
 * Plugin RefNotes: Notes collection
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

/* Must be run within Dokuwiki */
if (!defined('DOKU_INC') || !defined('DOKU_PLUGIN')) die();

require_once(DOKU_INC . 'inc/plugin.php');
require_once(DOKU_PLUGIN . 'refnotes/info.php');
require_once(DOKU_PLUGIN . 'refnotes/config.php');
require_once(DOKU_PLUGIN . 'refnotes/namespace.php');

class helper_plugin_refnotes extends DokuWiki_Plugin {

    private $namespaceStyle;
    private $namespace;

    /**
     * Constructor
     */
    public function __construct() {
        $this->namespaceStyle = refnotes_configuration::load('namespaces');
        $this->namespace = array();
    }

    /**
     * Return some info
     */
    public function getInfo() {
        return refnotes_getInfo('notes collection');
    }

    /**
     * Don't publish any methods (it's not a public helper)
     */
    public function getMethods() {
        return array();
    }

    /**
     * Adds a reference to the notes array. Returns a note
     */
    public function addReference($namespaceName, $noteName, $hidden, $inline) {
        $namespace = $this->findNamespace($namespaceName, true);

        return $namespace->addReference($noteName, $hidden, $inline);
    }

    /**
     *
     */
    public function styleNamespace($namespaceName, $style) {
        $namespace = $this->findNamespace($namespaceName, true);

        if (array_key_exists('inherit', $style)) {
            $source = $this->findNamespace($style['inherit'], true);
            $namespace->inheritStyle($source);
        }

        $namespace->style($style);
    }

    /**
     *
     */
    public function renderNotes($namespaceName, $limit = '') {
        $html = '';
        if ($namespaceName == '*') {
            foreach ($this->namespace as $namespace) {
                $html .= $namespace->renderNotes();
            }
        }
        else {
            $namespace = $this->findNamespace($namespaceName);
            if ($namespace != NULL) {
                $html = $namespace->renderNotes($limit);
            }
        }

        return $html;
    }

    /**
     * Finds a namespace given it's name
     */
    private function findNamespace($name, $create = false) {
        $result = NULL;
        if (array_key_exists($name, $this->namespace)) {
            $result = $this->namespace[$name];
        }

        if (($result == NULL) && $create) {
            if ($name != ':') {
                $parentName = refnotes_getParentNamespace($name);
                $parent = $this->findNamespace($parentName, true);
                $this->namespace[$name] = new refnotes_namespace($name, $parent);
            }
            else {
                $this->namespace[$name] = new refnotes_namespace($name);
            }

            if (array_key_exists($name, $this->namespaceStyle)) {
                $this->namespace[$name]->style($this->namespaceStyle[$name]);
            }

            $result = $this->namespace[$name];
        }

        return $result;
    }
}

class refnotes_namespace {

    private $name;
    private $style;
    private $scope;
    private $newScope;

    /**
     * Constructor
     */
    public function __construct($name, $parent = NULL) {
        $this->name = $name;
        $this->style = array();
        $this->scope = array();
        $this->newScope = true;

        if ($parent != NULL) {
            $this->style = $parent->style;
        }
    }

    /**
     *
     */
    public function getName() {
        return $this->name;
    }

    /**
     *
     */
    public function style($style) {
        foreach ($style as $property => $value) {
            $this->style[$property] = $value;
        }
    }

    /**
     *
     */
    public function inheritStyle($source) {
        $this->style = $source->style;
    }

    /**
     *
     */
    public function getStyle($property) {
        $result = '';

        if (array_key_exists($property, $this->style)) {
            $result = $this->style[$property];
        }

        return $result;
    }

    /**
     * Adds a reference to the current scope. Returns a note
     */
    public function addReference($name, $hidden, $inline) {
        if ($this->newScope) {
            $id = count($this->scope) + 1;
            $this->scope[] = new refnotes_scope($this, $id);
            $this->newScope = false;
        }

        $scope = end($this->scope);

        return $scope->addReference($name, $hidden, $inline);
    }

    /**
     *
     */
    public function renderNotes($limit = '') {
        $this->resetScope();
        $html = '';
        if (count($this->scope) > 0) {
            $scope = end($this->scope);
            $limit = $this->getRenderLimit($limit, $scope);
            $html = $scope->renderNotes($limit);
        }

        return $html;
    }

    /**
     *
     */
    private function resetScope() {
        switch ($this->getStyle('scoping')) {
            case 'single':
                break;

            default:
                $this->newScope = true;
                break;
        }
    }

    /**
     *
     */
    private function getRenderLimit($limit, $scope) {
        if (preg_match('/(\/?)(\d+)/', $limit, $match) == 1) {
            if ($match[1] != '') {
                $devider = intval($match[2]);
                $result = ceil($scope->getRenderableCount() / $devider);
            }
            else {
                $result = intval($match[2]);
            }
        }
        else {
            $result = 0;
        }

        return $result;
    }
}

class refnotes_scope {

    private $namespace;
    private $id;
    private $note;
    private $notes;
    private $inlineNotes;
    private $references;

    /**
     * Constructor
     */
    public function __construct($namespace, $id) {
        $this->namespace = $namespace;
        $this->id = $id;
        $this->note = array();
        $this->notes = 0;
        $this->inlineNotes = 0;
        $this->references = 0;
    }

    /**
     *
     */
    public function getName() {
        return $this->namespace->getName() . $this->id;
    }

    /**
     *
     */
    public function getStyle($property) {
        return $this->namespace->getStyle($property);
    }

    /**
     * Returns the number of renderable notes in the scope
     */
    public function getRenderableCount() {
        $result = 0;
        foreach ($this->note as $note) {
            if ($note->isRenderable()) {
                ++$result;
            }
        }

        return $result;
    }

    /**
     * Adds a reference to the notes array. Returns a note
     */
    public function addReference($name, $hidden, $inline) {
        $note = NULL;
        if (preg_match('/(?:@@FNT|#)(\d+)/', $name, $match) == 1) {
            $id = intval($match[1]);
            if (array_key_exists($id, $this->note)) {
                $note = $this->note[$id];
            }
        }
        else {
            if ($name != '') {
                $note = $this->findNote($name);
            }

            if ($note == NULL) {
                if ($inline) {
                    $id = --$this->inlineNotes;
                }
                else {
                    $id = ++$this->notes;
                }

                $note = new refnotes_note($this, $id, $name, $inline);
                $this->note[$id] = $note;
            }
        }

        if (($note != NULL) && !$hidden && !$note->isInline()) {
            $note->addReference(++$this->references);
        }

        return $note;
    }

    /**
     *
     */
    public function renderNotes($limit) {
        $html = '';
        $count = 0;
        foreach ($this->note as $note) {
            if ($note->isRenderable()) {
                $html .= $note->render();
                if (($limit != 0) && (++$count == $limit)) {
                    break;
                }
            }
        }

        if ($html != '') {
            $open = $this->renderSeparator() . '<div class="notes">' . DOKU_LF;
            $close = '</div>' . DOKU_LF;
            $html = $open . $html . $close;
        }

        return $html;
    }

    /**
     * Finds a note given it's name
     */
    private function findNote($name) {
        $result = NULL;

        foreach ($this->note as $note) {
            if ($note->getName() == $name) {
                $result = $note;
                break;
            }
        }

        return $result;
    }

    /**
     *
     */
    private function renderSeparator() {
        $html = '';
        $style = $this->namespace->getStyle('notes-separator');
        if ($style != 'none') {
            if ($style != '') {
                $style = ' style="width: '. $style . '"';
            }
            $html = '<hr' . $style . '>' . DOKU_LF;
        }

        return $html;
    }
}

class refnotes_note {

    private $scope;
    private $id;
    private $name;
    private $inline;
    private $reference;
    private $references;
    private $text;
    private $rendered;

    /**
     * Constructor
     */
    public function __construct($scope, $id, $name, $inline) {
        $this->scope = $scope;
        $this->id = $id;

        if ($name != '') {
            $this->name = $name;
        }
        else {
            $this->name = '#' . $id;
        }

        $this->inline = $inline;
        $this->reference = array();
        $this->references = 0;
        $this->text = '';
        $this->rendered = false;
    }

    /**
     *
     */
    public function getName() {
        return $this->name;
    }

    /**
     *
     */
    public function addReference($referenceId) {
        $this->reference[++$this->references] = $referenceId;
    }

    /**
     *
     */
    public function setText($text) {
        if (($this->text == '') || !$this->inline) {
            $this->text = $text;
        }
    }

    /**
     *
     */
    public function isInline() {
        return $this->inline;
    }

    /**
     * Checks if the note should be rendered
     */
    public function isRenderable() {
        return !$this->rendered && ($this->references > 0) && ($this->text != '');
    }

    /**
     *
     */
    public function renderReference() {
        if ($this->inline) {
            $html = '<sup>' . $this->text . '</sup>';
        }
        else {
            $noteName = $this->renderAnchorName();
            $referenceName = $this->renderAnchorName($this->references);
            $class = $this->renderReferenceClass();

            list($baseOpen, $baseClose) = $this->renderReferenceBase();
            list($fontOpen, $fontClose) = $this->renderReferenceFont();
            list($formatOpen, $formatClose) = $this->renderReferenceFormat();

            $html = $baseOpen . $fontOpen;
            $html .= '<a href="#' . $noteName . '" name="' . $referenceName . '" class="' . $class . '">';
            $html .= $formatOpen . $this->renderReferenceId() . $formatClose;
            $html .= '</a>';
            $html .= $fontClose . $baseClose;
        }

        return $html;
    }

    /**
     *
     */
    public function render() {
        $html = '<div class="' . $this->renderNoteClass() . '">' . DOKU_LF;
        $html .= $this->renderBackReferences();
        $html .= '<span id="' . $this->renderAnchorName() . ':text">' . DOKU_LF;
        $html .= $this->text . DOKU_LF;
        $html .= '</span></div>' . DOKU_LF;

        $this->rendered = true;

        return $html;
    }

    /**
     *
     */
    private function renderBackReferences() {
        $nameAttribute = ' name="' . $this->renderAnchorName() .'"';
        $backRefFormat = $this->getStyle('back-ref-format');
        $backRefCaret = '';
        list($formatOpen, $formatClose) = $this->renderNoteIdFormat();

        if (($backRefFormat != 'note') && ($backRefFormat != '')) {
            list($baseOpen, $baseClose) = $this->renderNoteIdBase();
            list($fontOpen, $fontClose) = $this->renderNoteIdFont();

            $html .= $baseOpen . $fontOpen;
            $html .= '<a' . $nameAttribute .' class="nolink">';
            $html .= $formatOpen . $this->renderNoteId() . $formatClose;
            $html .= '</a>';
            $html .= $fontClose . $baseClose . DOKU_LF;

            $nameAttribute = '';
            $formatOpen = '';
            $formatClose = '';
            $backRefCaret = $this->renderBackRefCaret();
        }

        if ($backRefFormat != 'none') {
            $separator = $this->renderBackRefSeparator();
            list($baseOpen, $baseClose) = $this->renderBackRefBase();
            list($fontOpen, $fontClose) = $this->renderBackRefFont();

            $html .= $baseOpen . $backRefCaret;

            for ($r = 1; $r <= $this->references; $r++) {
                $referenceName = $this->renderAnchorName($r);

                $html .= $fontOpen;
                $html .= '<a href="#' . $referenceName . '"' . $nameAttribute .' class="backref">';
                $html .= $formatOpen . $this->renderBackRefId($r, $backRefFormat) . $formatClose;
                $html .= '</a>';
                $html .= $fontClose;

                if ($r < $this->references) {
                    $html .= $separator . DOKU_LF;
                }

                $nameAttribute = '';
            }

            $html .= $baseClose . DOKU_LF;
        }

        return $html;
    }

    /**
     *
     */
    private function renderAnchorName($reference = 0) {
        $result = 'refnotes';
        $result .= $this->scope->getName();
        $result .= ':note' . $this->id;

        if ($reference > 0) {
            $result .= ':ref' . $reference;
        }

        return $result;
    }

    /**
     *
     */
    private function renderReferenceClass() {
        switch ($this->getStyle('note-preview')) {
            case 'tooltip':
                $result = 'refnotes-ref note-tooltip';
                break;

            case 'none':
                $result = 'refnotes-ref';
                break;

            default:
                $result = 'refnotes-ref note-popup';
                break;
        }

        return $result;
    }

    /**
     *
     */
    private function renderReferenceBase() {
        return $this->renderBase($this->getStyle('reference-base'));
    }

    /**
     *
     */
    private function renderReferenceFont() {
        return $this->renderFont('reference-font-weight', 'normal', 'reference-font-style');
    }

    /**
     *
     */
    private function renderReferenceFormat() {
        return $this->renderFormat($this->getStyle('reference-format'));
    }

    /**
     *
     */
    private function renderReferenceId($reference = 0) {
        $idStyle = $this->getStyle('refnote-id');
        if ($idStyle == 'name') {
            $html = $this->name;
        }
        else {
            switch ($this->getStyle('multi-ref-id')) {
                case 'note':
                    $id = $this->id;
                    break;

                default:
                    if ($reference > 0) {
                        $id = $this->reference[$reference];
                    }
                    else {
                        $id = end($this->reference);
                    }
                    break;
            }
            $html = $this->convertToStyle($id, $idStyle);
        }

        return $html;
    }

    /**
     *
     */
    private function renderNoteClass() {
        $result = 'note';

        switch ($this->getStyle('note-font-size')) {
            case 'small':
                $result .= ' small';
                break;
        }

        switch ($this->getStyle('note-text-align')) {
            case 'left':
                $result .= ' left';
                break;

            default:
                $result .= ' justify';
                break;
        }

        return $result;
    }

    /**
     *
     */
    private function renderNoteIdBase() {
        return $this->renderBase($this->getStyle('note-id-base'));
    }

    /**
     *
     */
    private function renderNoteIdFont() {
        return $this->renderFont('note-id-font-weight', 'normal', 'note-id-font-style');
    }

    /**
     *
     */
    private function renderNoteIdFormat() {
        $style = $this->getStyle('note-id-format');
        switch ($style) {
            case '.':
                $result = array('', '.');
                break;

            default:
                $result = $this->renderFormat($style);
                break;
        }

        return $result;
    }

    /**
     *
     */
    private function renderNoteId() {
        $idStyle = $this->getStyle('refnote-id');
        if ($idStyle == 'name') {
            $html = $this->name;
        }
        else {
            $html = $this->convertToStyle($this->id, $idStyle);
        }

        return $html;
    }

    /**
     *
     */
    private function renderBackRefCaret() {
        switch ($this->getStyle('back-ref-caret')) {
            case 'prefix':
                $result = '^ ';
                break;

            case 'merge':
                $result = ($this->references > 1) ? '^ ' : '';
                break;

            default:
                $result = '';
                break;
        }

        return $result;
    }

    /**
     *
     */
    private function renderBackRefBase() {
        return $this->renderBase($this->getStyle('back-ref-base'));
    }

    /**
     *
     */
    private function renderBackRefFont() {
        return $this->renderFont('back-ref-font-weight', 'bold', 'back-ref-font-style');
    }

    /**
     *
     */
    private function renderBackRefSeparator() {
        static $html = array('' => ',', 'none' => '');

        $style = $this->getStyle('back-ref-separator');
        if (!array_key_exists($style, $html)) {
            $style = '';
        }

        return $html[$style];
    }

    /**
     *
     */
    private function renderBackRefId($reference, $style) {
        switch ($style) {
            case 'a':
                $result = $this->convertToLatin($reference, $style);
                break;

            case '1':
                $result = $reference;
                break;

            case 'caret':
                $result = '^';
                break;

            case 'arrow':
                $result = '&uarr;';
                break;

            default:
                $result = $this->renderReferenceId($reference);
                break;
        }

        if (($this->references == 1) && ($this->getStyle('back-ref-caret') == 'merge')) {
            $result = '^';
        }

        return $result;
    }

    /**
     *
     */
    private function renderBase($style) {
        static $html = array(
            '' => array('<sup>', '</sup>'),
            'text' => array('', '')
        );

        if (!array_key_exists($style, $html)) {
            $style = '';
        }

        return $html[$style];
    }

    /**
     *
     */
    private function renderFont($weight, $defaultWeight, $style) {
        list($weightOpen, $weightClose) = $this->renderFontWeight($this->getStyle($weight), $defaultWeight);
        list($styleOpen, $styleClose) = $this->renderFontStyle($this->getStyle($style));

        return array($weightOpen . $styleOpen, $styleClose . $weightClose);
    }

    /**
     *
     */
    private function renderFontWeight($style, $default) {
        static $html = array(
            'normal' => array('', ''),
            'bold' => array('<b>', '</b>')
        );

        if (!array_key_exists($style, $html)) {
            $style = $default;
        }

        return $html[$style];
    }

    /**
     *
     */
    private function renderFontStyle($style) {
        static $html = array(
            '' => array('', ''),
            'italic' => array('<i>', '</i>')
        );

        if (!array_key_exists($style, $html)) {
            $style = '';
        }

        return $html[$style];
    }

    /**
     *
     */
    private function renderFormat($style) {
        static $html = array(
            '' => array('', ')'),
            '()' => array('(', ')'),
            ']' => array('', ']'),
            '[]' => array('[', ']'),
            'none' => array('', '')
        );

        if (!array_key_exists($style, $html)) {
            $style = '';
        }

        return $html[$style];
    }

    /**
     *
     */
    private function getStyle($property) {
        return $this->scope->getStyle($property);
    }

    /**
     *
     */
    private function convertToStyle($id, $style) {
        switch ($style) {
            case 'a':
            case 'A':
                $result = $this->convertToLatin($id, $style);
                break;

            case 'i':
            case 'I':
                $result = $this->convertToRoman($id, $style);
                break;

            case '*':
                $result = str_repeat('*', $id);
                break;

            default:
                $result = $id;
                break;
        }

        return $result;
    }

    /**
     *
     */
    private function convertToLatin($number, $case)
    {
        static $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $result = '';
        while ($number > 0) {
            --$number;
            $digit = $number % 26;
            $result = $alpha{$digit} . $result;
            $number = intval($number / 26);
        }

        if ($case == 'a') {
            $result = strtolower($result);
        }

        return $result;
    }

    /**
     *
     */
    private function convertToRoman($number, $case)
    {
        static $lookup = array(
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        );

        $result = '';
        foreach ($lookup as $roman => $value) {
            $matches = intval($number / $value);
            if ($matches > 0) {
                $result .= str_repeat($roman, $matches);
                $number = $number % $value;
            }
        }

        if ($case == 'i') {
            $result = strtolower($result);
        }

        return $result;
    }
}