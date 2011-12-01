<?php
/**
 * Table editor
 *
 * @author     Adrian Lang <lang@cosmocode.de>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once DOKU_PLUGIN.'edittable/common.php';

class action_plugin_edittable extends DokuWiki_Action_Plugin {

    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'html_secedit_button');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_table_post');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_newtable');
        $controller->register_hook('HTML_EDIT_FORMSELECTION', 'BEFORE', $this, 'html_table_editform');
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'toolbar');
    }

    function getLang($id) {
        $r = parent::getLang($id);
        if ($r !== '') return $r;

        $js = parent::getLang('js');
        return $js[$id];
    }

    function toolbar(&$event) {
        $menu = array(
                    array('title'  => $this->getLang('toggle_header'),
                          'key'    => 'H',
                          'icon'   => 'text_heading.png',
                          'type'   => 'toggletag'),

                    array('title'  => $this->getLang('val_align_left'),
                          'key'    => 'N',
                          'icon'   => 'a_left.png',
                          'type'   => 'val',
                          'prop'   => 'align',
                          'class'  => 'separator',
                          'val'    => 'left'),

                    array('title'  => $this->getLang('val_align_center'),
                          'key'    => 'M',
                          'icon'   => 'a_center.png',
                          'type'   => 'val',
                          'prop'   => 'align',
                          'val'    => 'center'),

                    array('title'  => $this->getLang('val_align_right'),
                          'key'    => 'R',
                          'icon'   => 'a_right.png',
                          'type'   => 'val',
                          'prop'   => 'align',
                          'val'    => 'right'),

                    array('title'  => $this->getLang('span_col_plus'),
                          'icon'   => 'merge_right.png',
                          'type'   => 'span',
                          'class'  => 'separator',
                          'target' => 'col',
                          'ops'    => '+'),

                    array('title'  => $this->getLang('span_col_minus'),
                          'icon'   => 'split_right.png',
                          'type'   => 'span',
                          'target' => 'col',
                          'ops'    => '-'),

                    array('title'  => $this->getLang('span_row_plus'),
                          'icon'   => 'merge_down.png',
                          'type'   => 'span',
                          'class'  => 'separator',
                          'target' => 'row',
                          'ops'    => '+'),

                    array('title'  => $this->getLang('span_row_minus'),
                          'icon'   => 'split_down.png',
                          'type'   => 'span',
                          'target' => 'row',
                          'ops'    => '-'),

                    array('title'  => $this->getLang('struct_row_plus'),
                          'icon'   => 'row_insert.png',
                          'type'   => 'structure',
                          'class'  => 'separator',
                          'target' => 'row',
                          'ops'    => '+'),

                    array('title'  => $this->getLang('struct_row_minus'),
                          'icon'   => 'row_delete.png',
                          'type'   => 'structure',
                          'target' => 'row',
                          'ops'    => '-'),

                    array('title'  => $this->getLang('struct_col_plus'),
                          'icon'   => 'column_add.png',
                          'type'   => 'structure',
                          'class'  => 'separator',
                          'target' => 'col',
                          'ops'    => '+'),

                    array('title'  => $this->getLang('struct_col_minus'),
                          'icon'   => 'column_delete.png',
                          'type'   => 'structure',
                          'target' => 'col',
                          'ops'    => '-'),
        );
        foreach ($menu as &$entry) {
            $entry['icon'] = '../../plugins/edittable/images/' . $entry['icon'];
        }

        // use JSON to build the JavaScript array
        $json = new JSON();
        echo 'var table_toolbar = '.$json->encode($menu).';'.DOKU_LF;

        $event->data[] = array('title'  => $this->getLang('add_table'),
                               'type'   => 'insertTable',
                               'icon'   => '../../plugins/edittable/images/add_table.png');
    }

    /**
     * Handles a POST from the table editor
     *
     * This function preprocesses a POST from the table editor. It converts the
     * table array to plain wiki markup text and stores it in the global $TEXT.
     *
     * @author Adrian Lang <lang@cosmocode.de>
     */
    function handle_table_post($event) {
        if (!isset($_POST['table'])) {
            return;
        }
        global $TEXT;
        $TEXT = table_to_wikitext($_POST['table']);
    }

    function handle_newtable($event) {
        if (!isset($_POST['edittable__new'])) {
            return;
        }
        foreach($_POST['edittable__new'] as &$v) {
            // Form performs a formText
            $v = cleanText($v);
        }

        global $TEXT;
        if (isset($_POST['do']['edit'])) {
            $_REQUEST['target'] = 'table';
            $TEXT = "^  ^  ^\n";
            foreach (explode("\n", $_POST['edittable__new']['text']) as $line) {
                $TEXT .= "| $line |  |\n";
            }
        } elseif (isset($_POST['do']['draftdel'])) {
            $TEXT = $_POST['edittable__new']['pre'] .
                    $_POST['edittable__new']['text'] .
                    $_POST['edittable__new']['suf'];
            global $ACT;
            $ACT = 'edit';
            $_REQUEST['target'] = 'section';
        } elseif (isset($_POST['do']['save'])) {
            $TEXT = $_POST['edittable__new']['pre'] .
                    $TEXT .
                    $_POST['edittable__new']['suf'];
            global $ACT;
            $ACT = 'edit';
            $_REQUEST['target'] = 'section';
        }
    }

    function html_secedit_button(&$event) {
        if ($event->data['target'] !== 'table') {
            return;
        }
        $event->data['name'] = $this->getLang('secedit_name');
    }

    function html_table_editform($event) {
        global $TEXT;
        if ($event->data['target'] !== 'table') {
            // Not a table edit
            return;
        }

        $event->stopPropagation();
        $event->preventDefault();

        require_once 'renderer_table_edit.php';
        $Renderer = new Doku_Renderer_xhtml_table_edit();
        $instructions = p_get_instructions($TEXT);

        // Loop through the instructions
        foreach ( $instructions as $instruction ) {
            // Execute the callback against the Renderer
            call_user_func_array(array(&$Renderer, $instruction[0]),$instruction[1]);
        }

        $event->data['form']->addElement('<div id="edit__wrap">' .$Renderer->doc .'</div>');

        if (isset($_POST['edittable__new'])) {
            foreach($_POST['edittable__new'] as $k => $v) {
                $event->data['form']->addHidden("edittable__new[$k]", $v);
            }
        }
    }
}
