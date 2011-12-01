<?php

/**
 * Plugin Columns: Syntax & rendering
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 *             Based on plugin by Michael Arlt <michael.arlt [at] sk-schwanstetten [dot] de>
 */

/* Must be run within Dokuwiki */
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');
require_once(DOKU_PLUGIN . 'columns/info.php');

class syntax_plugin_columns extends DokuWiki_Syntax_Plugin {

    var $mode;
    var $lexerSyntax;
    var $syntax;

    /**
     * Constructor
     */
    function syntax_plugin_columns() {
        $this->mode = substr(get_class($this), 7);

        $columns = $this->_getColumnsTagName();
        $newColumn = $this->_getNewColumnTagName();
        if ($this->getConf('wrapnewcol') == 1) {
            $newColumnLexer = '<' . $newColumn . '(?:>|\s.*?>)';
            $newColumnHandler = '<' . $newColumn . '(.*?)>';
        }
        else {
            $newColumnLexer = $newColumn;
            $newColumnHandler = $newColumn;
        }
        $enterLexer = '<' . $columns . '(?:>|\s.*?>)';
        $enterHandler = '<' . $columns . '(.*?)>';
        $exit = '<\/' . $columns . '>';

        $this->lexerSyntax['enter'] = $enterLexer;
        $this->lexerSyntax['newcol'] = $newColumnLexer;
        $this->lexerSyntax['exit'] = $exit;

        $this->syntax[DOKU_LEXER_ENTER] = '/' . $enterHandler . '/';
        $this->syntax[DOKU_LEXER_MATCHED] = '/' . $newColumnHandler . '/';
        $this->syntax[DOKU_LEXER_EXIT] = '/' . $exit . '/';
    }

    /**
     * Return some info
     */
    function getInfo() {
        return columns_getInfo('syntax & rendering');
    }

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    function getPType() {
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 65;
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->lexerSyntax['enter'], $mode, $this->mode);
        $this->Lexer->addSpecialPattern($this->lexerSyntax['newcol'], $mode, $this->mode);
        $this->Lexer->addSpecialPattern($this->lexerSyntax['exit'], $mode, $this->mode);
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        foreach ($this->syntax as $state => $pattern) {
            if (preg_match($pattern, $match, $data) == 1) {
                break;
            }
        }
        switch ($state) {
            case DOKU_LEXER_ENTER:
            case DOKU_LEXER_MATCHED:
                return array($state, preg_split('/\s+/', $data[1], -1, PREG_SPLIT_NO_EMPTY));

            case DOKU_LEXER_EXIT:
                return array($state);
        }
        return false;
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if ($mode == 'xhtml') {
            switch ($data[0]) {
                case DOKU_LEXER_ENTER:
                    $renderer->doc .= $this->_renderTable($data[1]) . DOKU_LF;
                    $renderer->doc .= '<tr>' . $this->_renderTd($data[1]) . DOKU_LF;
                    break;

                case DOKU_LEXER_MATCHED:
                    $renderer->doc .= '</td>' . $this->_renderTd($data[1]) . DOKU_LF;
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= '</td></tr></table>' . DOKU_LF;
                    break;
            }
            return true;
        }
        else if ($mode == 'odt') {
            switch ($data[0]) {
                case DOKU_LEXER_ENTER:
                    $this->_addOdtTableStyle($renderer, $data[1]);
                    $this->_addOdtColumnStyles($renderer, $data[1]);
                    $this->_renderOdtTableEnter($renderer, $data[1]);
                    $this->_renderOdtColumnEnter($renderer, $data[1]);
                    break;

                case DOKU_LEXER_MATCHED:
                    $this->_addOdtColumnStyles($renderer, $data[1]);
                    $this->_renderOdtColumnExit($renderer);
                    $this->_renderOdtColumnEnter($renderer, $data[1]);
                    break;

                case DOKU_LEXER_EXIT:
                    $this->_renderOdtColumnExit($renderer);
                    $this->_renderOdtTableExit($renderer);
                    break;
            }
            return true;
        }
        return false;
    }

    /**
     * Returns columns tag
     */
    function _getColumnsTagName() {
        $tag = $this->getConf('kwcolumns');
        if ($tag == '') {
            $tag = $this->getLang('kwcolumns');
        }
        return $tag;
    }

    /**
     * Returns new column tag
     */
    function _getNewColumnTagName() {
        $tag = $this->getConf('kwnewcol');
        if ($tag == '') {
            $tag = $this->getLang('kwnewcol');
        }
        return $tag;
    }

    /**
     *
     */
    function _renderTable($attribute) {
        $width = $this->_getAttribute($attribute, 'table-width');
        if ($width != '') {
            return '<table class="columns-plugin" style="width:' . $width . '">';
        }
        else {
            return '<table class="columns-plugin">';
        }
    }

    /**
     *
     */
    function _renderTd($attribute) {
        $class = $this->_getAttribute($attribute, 'class');
        $textAlign = $this->_getAttribute($attribute, 'text-align');
        if ($textAlign != '') {
            if ($class != '') {
                $class .= ' ';
            }
            $class .= $textAlign;
        }
        if ($class == '') {
            $html = '<td';
        }
        else {
            $html = '<td class="' . $class . '"';
        }
        $style = $this->_getStyle($attribute, 'column-width', 'width');
        $style .= $this->_getStyle($attribute, 'vertical-align');
        if ($style != '') {
            $html .= ' style="' . $style . '"';
        }
        return $html . '>';
    }

    /**
     *
     */
    function _getStyle($attribute, $attributeName, $styleName = '') {
        $result = $this->_getAttribute($attribute, $attributeName);
        if ($result != '') {
            if ($styleName == '') {
                $styleName = $attributeName;
            }
            $result = $styleName . ':' . $result . ';';
        }
        return $result;
    }

    /**
     *
     */
    function _getAttribute($attribute, $name) {
        $result = '';
        if (array_key_exists($name, $attribute)) {
            $result = $attribute[$name];
        }
        return $result;
    }

    /**
     *
     */
    function _renderOdtTableEnter(&$renderer, $attribute) {
        $columns = $this->_getAttribute($attribute, 'columns');
        $blockId = $this->_getAttribute($attribute, 'block-id');
        $styleName = $this->_getOdtTableStyleName($blockId);

        $renderer->doc .= '<table:table table:style-name="' . $styleName . '">';
        for ($c = 0; $c < $columns; $c++) {
            $styleName = $this->_getOdtTableStyleName($blockId, $c + 1);
            $renderer->doc .= '<table:table-column table:style-name="' . $styleName . '" />';
        }
        $renderer->doc .= '<table:table-row>';
    }

    /**
     *
     */
    function _renderOdtColumnEnter(&$renderer, $attribute) {
        $blockId = $this->_getAttribute($attribute, 'block-id');
        $columnId = $this->_getAttribute($attribute, 'column-id');
        $styleName = $this->_getOdtTableStyleName($blockId, $columnId, 1);
        $renderer->doc .= '<table:table-cell table:style-name="' . $styleName . '" office:value-type="string">';
    }

    /**
     *
     */
    function _renderOdtColumnExit(&$renderer) {
        $renderer->doc .= '</table:table-cell>';
    }

    /**
     *
     */
    function _renderOdtTableExit(&$renderer) {
        $renderer->doc .= '</table:table-row>';
        $renderer->doc .= '</table:table>';
    }

    /**
     *
     */
    function _addOdtTableStyle(&$renderer, $attribute) {
        $styleName = $this->_getOdtTableStyleName($this->_getAttribute($attribute, 'block-id'));
        $style = '<style:style style:name="' . $styleName . '" style:family="table">';
        $style .= '<style:table-properties';
        $width = $this->_getAttribute($attribute, 'table-width');

        if (($width != '') && ($width != '100%')) {
            $metrics = $this->_getOdtMetrics($renderer->autostyles);
            $style .= ' style:width="' . $this->_getOdtAbsoluteWidth($metrics, $width) . '"';
        }
        $align = ($width == '100%') ? 'margins' : 'left';
        $style .= ' table:align="' . $align . '"/>';
        $style .= '</style:style>';

        $renderer->autostyles[$styleName] = $style;
    }

    /**
     *
     */
    function _addOdtColumnStyles(&$renderer, $attribute) {
        $blockId = $this->_getAttribute($attribute, 'block-id');
        $columnId = $this->_getAttribute($attribute, 'column-id');
        $styleName = $this->_getOdtTableStyleName($blockId, $columnId);

        $style = '<style:style style:name="' . $styleName . '" style:family="table-column">';
        $style .= '<style:table-column-properties';
        $width = $this->_getAttribute($attribute, 'column-width');

        if ($width != '') {
            $metrics = $this->_getOdtMetrics($renderer->autostyles);
            $style .= ' style:column-width="' . $this->_getOdtAbsoluteWidth($metrics, $width) . '"';
        }
        $style .= '/>';
        $style .= '</style:style>';

        $renderer->autostyles[$styleName] = $style;

        $styleName = $this->_getOdtTableStyleName($blockId, $columnId, 1);

        $style = '<style:style style:name="' . $styleName . '" style:family="table-cell">';
        $style .= '<style:table-cell-properties';
        $style .= ' fo:border="none"';
        $style .= ' fo:padding-top="0cm"';
        $style .= ' fo:padding-bottom="0cm"';

        switch ($this->_getAttribute($attribute, 'class')) {
            case 'first':
                $style .= ' fo:padding-left="0cm"';
                $style .= ' fo:padding-right="0.4cm"';
                break;

            case 'last':
                $style .= ' fo:padding-left="0.4cm"';
                $style .= ' fo:padding-right="0cm"';
                break;
        }

        /* There seems to be no easy way to control horizontal alignment of text within
           the column as fo:text-align aplies to individual paragraphs. */
        //TODO: $this->_getAttribute($attribute, 'text-align');

        $align = $this->_getAttribute($attribute, 'vertical-align');
        if ($align != '') {
            $style .= ' style:vertical-align="' . $align . '"';
        }
        else {
            $style .= ' style:vertical-align="top"';
        }

        $style .= '/>';
        $style .= '</style:style>';

        $renderer->autostyles[$styleName] = $style;
    }

    /**
     *
     */
    function _getOdtMetrics($autoStyle) {
        $result = array();
        if (array_key_exists('pm1', $autoStyle)) {
            $style = $autoStyle['pm1'];
            if (preg_match('/fo:page-width="([\d\.]+)(.+?)"/', $style, $match) == 1) {
                $result['page-width'] = floatval($match[1]);
                $result['page-width-units'] = $match[2];
                $units = $match[2];

                if (preg_match('/fo:margin-left="([\d\.]+)(.+?)"/', $style, $match) == 1) {
                    // TODO: Unit conversion
                    if ($match[2] == $units) {
                        $result['page-width'] -= floatval($match[1]);
                    }
                }
                if (preg_match('/fo:margin-right="([\d\.]+)(.+?)"/', $style, $match) == 1) {
                    if ($match[2] == $units) {
                        $result['page-width'] -= floatval($match[1]);
                    }
                }
            }
        }
        if (!array_key_exists('page-width', $result)) {
            $result['page-width'] = 17;
            $result['page-width-units'] = 'cm';
        }

        /* There seems to be no easy way to get default font size apart from loading styles.xml. */
        $styles = io_readFile(DOKU_PLUGIN . 'odt/styles.xml');
        if (preg_match('/<style:default-style style:family="paragraph">(.+?)<\/style:default-style>/s', $styles, $match) == 1) {
            if (preg_match('/<style:text-properties(.+?)>/', $match[1], $match) == 1) {
                if (preg_match('/fo:font-size="([\d\.]+)(.+?)"/', $match[1], $match) == 1) {
                    $result['font-size'] = floatval($match[1]);
                    $result['font-size-units'] = $match[2];
                }
            }
        }
        if (!array_key_exists('font-size', $result)) {
            $result['font-size'] = 12;
            $result['font-size-units'] = 'pt';
        }
        return $result;
    }

    /**
     *
     */
    function _getOdtTableStyleName($blockId, $columnId = 0, $cell = 0) {
        $result = 'ColumnsBlock' . $blockId;
        if ($columnId != 0) {
            if ($columnId <= 26) {
                $result .= '.' . chr(ord('A') + $columnId - 1);
            }
            else {
                /* To unlikey to handle it properly */
                $result .= '.a';
            }
            if ($cell != 0) {
                $result .= $cell;
            }
        }
        return $result;
    }

    /**
     * Convert relative units to absolute
     */
    function _getOdtAbsoluteWidth($metrics, $width) {
        if (preg_match('/([\d\.]+)(.+)/', $width, $match) == 1) {
            switch ($match[2]) {
                case '%':
                    /* Won't work for nested column blocks */
                    $width = ($match[1] / 100 * $metrics['page-width']) . $metrics['page-width-units'];
                    break;
                case 'em':
                    /* Rough estimate */
                    $width = ($match[1] * 0.8 * $metrics['font-size']) . $metrics['font-size-units'];
                    break;
            }
        }
        return $width;
    }
}
