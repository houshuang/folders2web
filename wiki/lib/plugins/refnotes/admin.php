<?php

/**
 * Plugin RefNotes: Configuration interface
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

/* Must be run within Dokuwiki */
if (!defined('DOKU_INC') || !defined('DOKU_PLUGIN')) die();

require_once(DOKU_PLUGIN . 'admin.php');
require_once(DOKU_PLUGIN . 'refnotes/info.php');
require_once(DOKU_PLUGIN . 'refnotes/locale.php');

class admin_plugin_refnotes extends DokuWiki_Admin_Plugin {

    private $html;
    private $locale;

    /**
     * Constructor
     */
    public function __construct() {
        $this->html = new refnotes_html_sink();
        $this->locale = new refnotes_localization($this);
    }

    /**
     * Return some info
     */
    public function getInfo() {
        return refnotes_getInfo('configuration interface');
    }

    /**
     * Handle user request
     */
    public function handle() {
        /* All handling is done using AJAX */
    }

    /**
     * Output appropriate html
     */
    public function html() {
        print($this->locale_xhtml('intro'));

        $this->html->ptln('<!-- refnotes -->');

        $this->printLanguageStrings();

        $this->html->ptln('<div id="refnotes-config"><div id="config__manager">');
        $this->html->ptln('<noscript><div class="error">' . $this->locale->getLang('noscript') . '</div></noscript>');
        $this->html->ptln('<div id="server-status" class="info" style="display: none;">&nbsp;</div>');
        $this->html->ptln('<form action="" method="post">');
        $this->html->indent();

        $this->printGeneral();
        $this->printNamespaces();
        $this->printNotes();

        $this->html->ptln($this->getButton('save'));

        $this->html->unindent();
        $this->html->ptln('</form></div></div>');
        $this->html->ptln('<!-- /refnotes -->');
    }

    /**
     * Built-in JS localization stores all language strings in the common script (produced by js.php).
     * The strings used by administration plugin seem to be unnecessary in that script. Instead we print
     * them as part of the page and then load them into the LANG array on the client side.
     */
    private function printLanguageStrings() {
        $lang = $this->locale->getByPrefix('js');

        $this->html->ptln('<div id="refnotes-lang" style="display: none;">');

        foreach ($lang as $key => $value) {
            ptln($key . ' : ' . $value . ':eos:');
        }

        $this->html->ptln('</div>');
    }

    /**
     *
     */
    private function printGeneral() {
        $section = new refnotes_config_general();
        $section->printHtml($this->html, $this->locale);
    }

    /**
     *
     */
    private function printNamespaces() {
        $section = new refnotes_config_namespaces();
        $section->printHtml($this->html, $this->locale);
    }

    /**
     *
     */
    private function printNotes() {
        $section = new refnotes_config_notes();
        $section->printHtml($this->html, $this->locale);
    }

    /**
     *
     */
    private function getButton($action) {
        $html = '<input type="button" class="button"';
        $id = $action . '-config';
        $html .= ' id="' . $id . '"';
        $html .= ' name="' . $id . '"';
        $html .= ' value="' . $this->locale->getLang('btn_' . $action) . '"';
        $html .= ' />';

        return $html;
    }
}

class refnotes_config_section {

    protected $html;
    protected $locale;
    protected $id;
    protected $title;

    /**
     * Constructor
     */
    public function __construct($id) {
        $this->html = NULL;
        $this->locale = NULL;
        $this->id = $id;
        $this->title = 'sec_' . $id;
    }

    /**
     *
     */
    public function printHtml($html, $locale) {
        $this->html = $html;
        $this->locale = $locale;
        $this->open();
        $this->printFields();
        $this->close();
    }

    /**
     *
     */
    protected function open() {
        $this->html->ptln('<fieldset id="' . $this->id . '">');
        $this->html->ptln('<legend>' . $this->locale->getLang($this->title) . '</legend>');
        $this->html->ptln('<table class="inline" cols="3">');
        $this->html->indent();
    }

    /**
     *
     */
    protected function close() {
        $this->html->unindent();
        $this->html->ptln('</table>');
        $this->html->ptln('</fieldset>');
    }

    /**
     *
     */
    protected function printFields() {
        $field = $this->getFields();
        foreach ($field as $f) {
            $this->printFieldRow($f);
        }
    }

    /**
     *
     */
    protected function getFields() {
        $fieldData = $this->getFieldDefinitions();
        $field = array();

        foreach ($fieldData as $id => $fd) {
            $class = 'refnotes_config_' . $fd['class'];
            $field[] = new $class($id, $fd);
        }

        return $field;
    }

    /**
     *
     */
    protected function printFieldRow($field, $startRow = true) {
        if ($startRow) {
            $this->html->ptln('<tr>');
            $this->html->indent();
        }

        if (get_class($field) != 'refnotes_config_textarea') {
            $settingName = $field->getSettingName();
            if ($settingName != '') {
                $this->html->ptln('<td class="label">');
                $this->html->ptln($settingName);
            }
            else {
                $this->html->ptln('<td class="lean-label">');
            }

            $this->html->ptln($field->getLabel($this->locale));
            $this->html->ptln('</td><td class="value">');
        }
        else {
            $this->html->ptln('<td class="value" colspan="2">');
        }

        $this->html->ptln($field->getControl($this->locale));
        $this->html->ptln('</td>');

        $this->html->unindent();
        $this->html->ptln('</tr>');
    }
}

class refnotes_config_list_section extends refnotes_config_section {

    private $listRows;

    /**
     * Constructor
     */
    public function __construct($id, $listRows) {
        parent::__construct($id);

        $this->listRows = $listRows;
    }

    /**
     *
     */
    protected function close() {
        $this->html->unindent();
        $this->html->ptln('</table>');
        $this->printListControls();
        $this->html->ptln('</fieldset>');
    }

    /**
     *
     */
    private function printListControls() {
        $this->html->ptln('<div class="list-controls">');
        $this->html->indent();

        $this->html->ptln($this->getEdit());
        $this->html->ptln($this->getButton('add'));
        $this->html->ptln($this->getButton('rename'));
        $this->html->ptln($this->getButton('delete'));

        $this->html->unindent();
        $this->html->ptln('</div>');
    }

    /**
     *
     */
    private function getEdit() {
        $html = '<input type="text" class="edit"';
        $id = 'name-' . $this->id;
        $html .= ' id="' . $id . '"';
        $html .= ' name="' . $id . '"';
        $html .= ' value=""';
        $html .= ' />';

        return $html;
    }

    /**
     *
     */
    private function getButton($action) {
        $id = $action . '-' . $this->id;
        $html = '<input type="button" class="button"';
        $html .= ' id="' . $id . '"';
        $html .= ' name="' . $id . '"';
        $html .= ' value="' . $this->locale->getLang('btn_' . $action) . '"';
        $html .= ' />';

        return $html;
    }

    /**
     *
     */
    protected function printFields() {
        $field = $this->getFields();
        $fields = count($field);

        $this->html->ptln('<tr>');
        $this->html->indent();
        $this->html->ptln('<td class="list" rowspan="' . $fields . '">');
        $this->html->ptln('<select class="list" id="select-' . $this->id . '" size="' . $this->listRows . '"></select>');
        $this->html->ptln('</td>');

        $this->printFieldRow($field[0], false);

        for ($f = 1; $f < $fields; $f++) {
            $this->printFieldRow($field[$f]);
        }
    }
}

class refnotes_config_general extends refnotes_config_section {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('general');
    }

    /**
     *
     */
    protected function getFieldDefinitions() {
        static $field = array(
            'replace-footnotes' => array(
                'class' => 'checkbox',
                'lean' => true
            ),
            'reference-db-enable' => array(
                'class' => 'checkbox',
                'lean' => true
            ),
            'reference-db-namespace' => array(
                'class' => 'edit',
                'lean' => true
            )
        );

        return $field;
    }
}

class refnotes_config_namespaces extends refnotes_config_list_section {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('namespaces', 43);
    }

    /**
     *
     */
    protected function getFieldDefinitions() {
        static $field = array(
            'refnote-id' => array(
                'class' => 'select',
                'option' => array('numeric', 'latin-lower', 'latin-upper', 'roman-lower', 'roman-upper', 'stars', 'note-name', 'inherit')
            ),
            'reference-base' => array(
                'class' => 'select',
                'option' => array('super', 'normal-text', 'inherit')
            ),
            'reference-font-weight' => array(
                'class' => 'select',
                'option' => array('normal', 'bold', 'inherit')
            ),
            'reference-font-style' => array(
                'class' => 'select',
                'option' => array('normal', 'italic', 'inherit')
            ),
            'reference-format' => array(
                'class' => 'select',
                'option' => array('right-parent', 'parents', 'right-bracket', 'brackets', 'none', 'inherit')
            ),
            'multi-ref-id' => array(
                'class' => 'select',
                'option' => array('ref-counter', 'note-counter', 'inherit')
            ),
            'note-preview' => array(
                'class' => 'select',
                'option' => array('popup', 'tooltip', 'none', 'inherit')
            ),
            'notes-separator' => array(
                'class' => 'edit_inherit'
            ),
            'note-text-align' => array(
                'class' => 'select',
                'option' => array('justify', 'left', 'inherit')
            ),
            'note-font-size' => array(
                'class' => 'select',
                'option' => array('normal', 'small', 'inherit')
            ),
            'note-id-base' => array(
                'class' => 'select',
                'option' => array('super', 'normal-text', 'inherit')
            ),
            'note-id-font-weight' => array(
                'class' => 'select',
                'option' => array('normal', 'bold', 'inherit')
            ),
            'note-id-font-style' => array(
                'class' => 'select',
                'option' => array('normal', 'italic', 'inherit')
            ),
            'note-id-format' => array(
                'class' => 'select',
                'option' => array('right-parent', 'parents', 'right-bracket', 'brackets', 'dot', 'none', 'inherit')
            ),
            'back-ref-caret' => array(
                'class' => 'select',
                'option' => array('prefix', 'merge', 'none', 'inherit')
            ),
            'back-ref-base' => array(
                'class' => 'select',
                'option' => array('super', 'normal-text', 'inherit')
            ),
            'back-ref-font-weight' => array(
                'class' => 'select',
                'option' => array('normal', 'bold', 'inherit')
            ),
            'back-ref-font-style' => array(
                'class' => 'select',
                'option' => array('normal', 'italic', 'inherit')
            ),
            'back-ref-format' => array(
                'class' => 'select',
                'option' => array('note-id', 'latin', 'numeric', 'caret', 'arrow', 'none', 'inherit')
            ),
            'back-ref-separator' => array(
                'class' => 'select',
                'option' => array('comma', 'none', 'inherit')
            ),
            'scoping' => array(
                'class' => 'select',
                'option' => array('reset', 'single')
            )
        );

        return $field;
    }
}

class refnotes_config_notes extends refnotes_config_list_section {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('notes', 7);
    }

    /**
     *
     */
    protected function getFieldDefinitions() {
        static $field = array(
            'note-text' => array(
                'class' => 'textarea',
                'rows' => '4',
                'lean' => true
            ),
            'inline' => array(
                'class' => 'checkbox',
                'lean' => true
            )
        );

        return $field;
    }
}

class refnotes_config_field {

    protected $id;
    protected $settingName;
    protected $label;

    /**
     * Constructor
     */
    public function __construct($id, $data) {
        $this->id = 'field-' . $id;
        $this->label = 'lbl_' . $id;

        if (array_key_exists('lean', $data) && $data['lean']) {
            $this->settingName = '';
        }
        else {
            $this->settingName = $id;
        }
    }

    /**
     *
     */
    public function getSettingName() {
        $html = '';

        if ($this->settingName != '') {
            $html = '<span class="outkey">' . $this->settingName . '</span>';
        }

        return $html;
    }

    /**
     *
     */
    public function getLabel($locale) {
        return '<label for="' . $this->id . '">' . $locale->getLang($this->label) . '</label>';
    }
}

class refnotes_config_checkbox extends refnotes_config_field {

    /**
     * Constructor
     */
    public function __construct($id, $data) {
        parent::__construct($id, $data);
    }

    /**
     *
     */
    public function getControl($locale) {
        $html = '<div class="input">';
        $html .= '<input type="checkbox" class="checkbox"';
        $html .= ' id="' . $this->id . '"';
        $html .= ' name="' . $this->id . '" value="1"';
        $html .= '/></div>';

        return $html;
    }
}

class refnotes_config_select extends refnotes_config_field {

    private $option;

    /**
     * Constructor
     */
    public function __construct($id, $data) {
        parent::__construct($id, $data);

        $this->option = $data['option'];
    }

    /**
     *
     */
    public function getControl($locale) {
        $html = '<div class="input">';

        $html .= '<select class="edit"';
        $html .= ' id="' . $this->id . '"';
        $html .= ' name="' . $this->id . '">' . DOKU_LF;

        foreach ($this->option as $option) {
            $html .= '<option value="' . $option . '">' . $locale->getLang('opt_' . $option) . '</option>' . DOKU_LF;
        }

        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }
}

class refnotes_config_edit extends refnotes_config_field {

    /**
     * Constructor
     */
    public function __construct($id, $data) {
        parent::__construct($id, $data);
    }

    /**
     *
     */
    public function getControl($locale) {
        $html = '<div class="input">';

        $html .= '<input type="text" class="edit"';
        $html .= ' id="' . $this->id . '"';
        $html .= ' name="' . $this->id . '" />' . DOKU_LF;

        $html .= '</div>';

        return $html;
    }
}

class refnotes_config_edit_inherit extends refnotes_config_field {

    /**
     * Constructor
     */
    public function __construct($id, $data) {
        parent::__construct($id, $data);
    }

    /**
     *
     */
    public function getControl($locale) {
        $html = '<div class="input">';

        $html .= '<input type="text" class="edit"';
        $html .= ' id="' . $this->id . '"';
        $html .= ' name="' . $this->id . '" />' . DOKU_LF;

        $html .= '<input type="button" class="button"';
        $html .= ' id="' . $this->id . '-inherit"';
        $html .= ' name="' . $this->id . '-inherit"';
        $html .= ' value="' . $locale->getLang('opt_inherit') . '"';
        $html .= ' />';

        $html .= '</div>';

        return $html;
    }
}

class refnotes_config_textarea extends refnotes_config_field {

    private $rows;

    /**
     * Constructor
     */
    public function __construct($id, $data) {
        parent::__construct($id, $data);

        $this->rows = $data['rows'];
    }

    /**
     *
     */
    public function getControl($locale) {
        $html = '<div class="input">';
        $html .= '<textarea class="edit"';
        $html .= ' id="' . $this->id . '"';
        $html .= ' name="' . $this->id . '"';
        $html .= ' cols="40" rows="' . $this->rows . '">';
        $html .= '</textarea></div>';

        return $html;
    }
}

class refnotes_html_sink {

    private $indentIncrement;
    private $indent;

    /**
     * Constructor
     */
    public function __construct() {
        $this->indentIncrement = 2;
        $this->indent = 0;
    }

    /**
     *
     */
    public function indent() {
        $this->indent += $this->indentIncrement;
    }

    /**
     *
     */
    public function unindent() {
        if ($this->indent >= $this->indentIncrement) {
            $this->indent -= $this->indentIncrement;
        }
    }

    /**
     *
     */
    public function ptln($string, $indentDelta = 0) {
        if ($indentDelta < 0) {
            $this->indent += $this->indentIncrement * $indentDelta;
        }

        $text = explode(DOKU_LF, $string);
        foreach ($text as $string) {
            ptln($string, $this->indent);
        }

        if ($indentDelta > 0) {
            $this->indent += $this->indentIncrement * $indentDelta;
        }
    }
}
