<?php

/**
 * Plugin RefNotes: Note renderer
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

/* Must be run within Dokuwiki */
if (!defined('DOKU_INC') || !defined('DOKU_PLUGIN')) die();

require_once(DOKU_PLUGIN . 'syntax.php');
require_once(DOKU_PLUGIN . 'refnotes/info.php');
require_once(DOKU_PLUGIN . 'refnotes/namespace.php');

class syntax_plugin_refnotes_notes extends DokuWiki_Syntax_Plugin {

    private $mode;
    private $core;

    /**
     * Constructor
     */
    public function __construct() {
        $this->mode = substr(get_class($this), 7);
        $this->core = NULL;
    }

    /**
     * Return some info
     */
    public function getInfo() {
        return refnotes_getInfo('notes syntax');
    }

    /**
     * What kind of syntax are we?
     */
    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    /**
     * Where to sort in?
     */
    public function getSort() {
        return 150;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~REFNOTES.*?~~', $mode, $this->mode);
        $this->Lexer->addSpecialPattern('<refnotes.*?\/>', $mode, $this->mode);
        $this->Lexer->addSpecialPattern('<refnotes(?:.*?[^/])?>.*?<\/refnotes>', $mode, $this->mode);
    }

    /**
     * Handle the match
     */
    public function handle($match, $state, $pos, $handler) {
        switch ($match{0}) {
            case '~':
                return $this->handleBasic($match);

            case '<':
                return $this->handleExtended($match);
        }

        return false;
    }

    /**
     * Create output
     */
    public function render($mode, $renderer, $data) {
        try {
            if($mode == 'xhtml') {
                switch ($data[0]) {
                    case 'style':
                        $this->styleNamespace($renderer, $data[1], $data[2]);
                        break;

                    case 'render':
                        $this->renderNotes($renderer, $data[1]);
                        break;
                }
                return true;
            }
        }
        catch (Exception $error) {
            msg($error->getMessage(), -1);
        }

        return false;
    }

    /**
     *
     */
    private function handleBasic($syntax) {
        preg_match('/~~REFNOTES(.*?)~~/', $syntax, $match);

        return array('render', $this->parseAttributes($match[1]));
    }

    /**
     *
     */
    private function handleExtended($syntax) {
        preg_match('/<refnotes(.*?)(?:\/>|>(.*?)<\/refnotes>)/s', $syntax, $match);
        $attribute = $this->parseAttributes($match[1]);
        $style = array();

        if ($match[2] != '') {
            $style = $this->parseStyles($match[2]);
        }

        if (count($style) > 0) {
            return array('split', $attribute, $style);
        }
        else {
            return array('render', $attribute);
        }
    }

    /**
     *
     */
    private function parseAttributes($syntax) {
        static $propertyMatch = array(
            'ns' => '/^(:|:*([[:alpha:]]\w*:+)*?[[:alpha:]]\w*:*)$/',
            'limit' => '/^\/?\d+$/'
        );

        $attribute = array('ns' => ':');
        $token = preg_split('/\s+/', $syntax);
        foreach ($token as $t) {
            foreach ($propertyMatch as $name => $pattern) {
                if (preg_match($pattern, $t) == 1) {
                    $attribute[$name] = $t;
                    break;
                }
            }
        }

        /* Ensure that namespaces are in canonic form */
        $attribute['ns'] = refnotes_canonizeNamespace($attribute['ns']);

        return $attribute;
    }

    /**
     *
     */
    private function parseStyles($syntax) {
        $style = array();
        preg_match_all('/([-\w]+)\s*:\s*(.+?)\s*?(:?[\n;]|$)/', $syntax, $match, PREG_SET_ORDER);
        foreach ($match as $m) {
            $style[$m[1]] = $m[2];
        }

        /* Validate direct-to-html styles */
        if (array_key_exists('notes-separator', $style)) {
            if (preg_match('/(?:\d+\.?|\d*\.\d+)(?:%|em|px)|none/', $style['notes-separator'], $match) == 1) {
                $style['notes-separator'] = $match[0];
            }
            else {
                $style['notes-separator'] = '';
            }
        }

        /* Ensure that namespaces are in canonic form */
        if (array_key_exists('inherit', $style)) {
            $style['inherit'] = refnotes_canonizeNamespace($style['inherit']);
        }

        return $style;
    }

    /**
     *
     */
    private function styleNamespace($renderer, $attribute, $style) {
        $this->getCore()->styleNamespace($attribute['ns'], $style);
    }

    /**
     *
     */
    private function renderNotes($renderer, $attribute) {
        $limit = array_key_exists('limit', $attribute) ? $attribute['limit'] : '';
        $html = $this->getCore()->renderNotes($attribute['ns'], $limit);
        if ($html != '') {
            $renderer->doc .= '<div class="refnotes">' . DOKU_LF;
            $renderer->doc .= $html;
            $renderer->doc .= '</div>' . DOKU_LF;
        }
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
}
