<?php
/**
 * Plugin enter edit mode with double click
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Håkan Sandell <hakan.sandell@home.se>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC'))
    die();

if (!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once (DOKU_PLUGIN . 'action.php');

class action_plugin_dblclickedit extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo() {
    return array (
            'author' => 'H&aring;kan Sandell',
            'email'  => 'hakan.sandell@home.se',
            'date'   => @file_get_contents(dirname(__FILE__).'/VERSION'),
            'name'   => 'DblClickEdit',
            'desc'   => 'Enter edit mode by double click',
            'url'    => 'http://www.dokuwiki.org/plugin:dblclickedit'
        );
    }

    /**
     * register the eventhandlers
     */
    function register(& $controller) {
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'handle_content_display');
    }

    function handle_content_display(& $event, $param) {
        global $ID;
        global $INFO;

        function html_sectionedit($matches){
            global $ID;

            $editlink = ' ondblclick="window.location=\''.wl($ID,'do=edit&lines='.$matches[5]).'\'"';
            $section = $matches[3];
            if (preg_match('/<h\d>/', $section)) { // multiple headlines/divs in section
                $section = preg_replace('/(<h\d)(>.*?<\/h\d>)/', "$1$editlink$2", $section);
                $section = preg_replace('/(<div class="level\d")(>.*?<\/div>)/s', "$1$editlink$2", $section);
            }
            $section  = $matches[1]. $editlink .$matches[2]. $editlink .$section;
            return $section;
        }

        if (!$INFO['writable'] || $INFO['rev']) return;

        $html = &$event->data;
        if (preg_match('/<!-- SECTION/', $html, $matches)) {
            // section info available -> section edit
            $html = preg_replace_callback('/(<h\d)(>.*?<div class="level\d")(>.*?<\/div>.*?)<!-- SECTION\w* "(.*?)" \[(\d+-\d*)\] -->/s', 'html_sectionedit', $html);

        } elseif (!preg_match('/<div/', $html)) {
            // no header/section in page -> add span with page edit
            $html = '<span ondblclick="window.location=\''.wl($ID,'do=edit').'\'">'.$html.'</span>';

        } else {
            // insert page edit
            $html = preg_replace('/(\<(?:div class="level\d"|pre class="code[^"]*"|h\d))(\>)/', '$1 ondblclick="window.location=\''.wl($ID,'do=edit').'\'"$2', $html);
        }
        return;
    }

}
