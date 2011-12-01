<?php
/**
 * PHP Markdown Extra plugin for DokuWiki.
 *
 * @license GPL 3 (http://www.gnu.org/licenses/gpl.html) - NOTE: PHP Markdown
 * Extra is licensed under the BSD license. See License.text for details.
 * @version 1.02 - 6.11.2010 - PHP Markdown Extra 1.2.4 included.
 * @author Joonas Pulakka <joonas.pulakka@iki.fi>
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'syntax.php');
require_once (DOKU_PLUGIN . 'markdownextra/markdown.php');

class syntax_plugin_markdownextra extends DokuWiki_Syntax_Plugin {

    function getType() {
        return 'protected';
    }

    function getPType() {
        return 'block';
    }

    function getSort() {
        return 69;
    }

    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<markdown>(?=.*</markdown>)', $mode, 'plugin_markdownextra');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</markdown>', 'plugin_markdownextra');
    }

    function handle($match, $state, $pos, &$handler) {
        return array($match);
    }

    function render($mode, &$renderer, $data) {
        if ($mode == 'xhtml' && $data[0] != '<markdown>' && $data[0] != '</markdown>') {
            $renderer->doc .= Markdown($data[0]);
            return true;
        } else {
            return false;
        }
    }

}