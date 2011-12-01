<?php
/**
 * Helper functions for table editing
 *
 * @author     Adrian Lang <lang@cosmocode.de>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

require_once DOKU_PLUGIN."/edittable/inverse.php";

class Doku_Renderer_xhtml_table_edit extends Doku_Renderer_wiki {
    function table_open($maxcols = null, $numrows = null){
        parent::block();
        // initialize the row counter used for classes
        $this->_counter['row_counter'] = 0;
        $this->_counter['table_begin_pos'] = strlen($this->doc);
        $this->doc .= '<table class="inline edit">'.DOKU_LF;
    }

    function table_close() {
        parent::block();
        $this->doc .= '</table>'.DOKU_LF;
    }

    function tableheader_open($colspan = 1, $align = null, $rowspan = 1){
        $this->_tablefield_open('th', $colspan, $align, $rowspan);
    }

    function tableheader_close(){
        $this->_tablefield_close('th');
    }

    function tablecell_open($colspan = 1, $align = null, $rowspan = 1){
        $this->_tablefield_open('td', $colspan, $align, $rowspan);
    }

    function tablecell_close(){
        $this->_tablefield_close('td');
    }

    function tablerow_open(){
        parent::block();
        // initialize the cell counter used for classes
        $this->_counter['cell_counter'] = 0;
        $class = 'row' . $this->_counter['row_counter']++;
        $this->doc .= DOKU_TAB . '<tr class="'.$class.'">' . DOKU_LF . DOKU_TAB . DOKU_TAB;
    }

    function tablerow_close() {
        parent::block();
        $this->doc .= '</tr>';
    }

    function _tablefield_open($tag, $colspan, $align, $rowspan) {
        parent::block();
        $basename = 'table[' . $this->_counter['row_counter'] . '][' . $this->_counter['cell_counter'] . ']';
        $class = 'class="col' . $this->_counter['cell_counter']++;
        $class .= '"';
        $this->doc .= "<$tag $class";
        if ( $colspan > 1 ) {
            $this->_counter['cell_counter'] += $colspan-1;
            $this->doc .= ' colspan="'.$colspan.'"';
        }
        if ( $rowspan > 1 ) {
            $this->doc .= ' rowspan="'.$rowspan.'"';
        }
        $this->doc .= '>';
        foreach(compact('rowspan', 'colspan', 'align', 'tag') as $name => $val) {
            $this->doc .= '<input ' . html_attbuild(array('type'  => 'hidden',
                                                          'name'  => "{$basename}[{$name}]",
                                                          'value' => $val)) . ' />';
        }
        $this->doc .='<input name="' . $basename . '[text]" class="' . $align .
                     'align"';
        $this->doc .= 'value="';
        $this->pre_table_doc = $this->doc;
        $this->doc = '';
    }

    function _tablefield_close($tag) {
        parent::block();
        $this->doc = $this->pre_table_doc . hsc($this->doc) . '" /></' . $tag . '>';
    }

    function cdata($text) {
        $this->doc .= $this->_xmlEntities(parent::cdata($text));
    }

    function _xmlEntities($string) {
        return htmlspecialchars($string,ENT_QUOTES,'UTF-8');
    }

}
