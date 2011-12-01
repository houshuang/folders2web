<?php
/**
 * DokuWiki Plugin ajaxloader (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Adrian Lang <lang@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_ajaxloader extends DokuWiki_Plugin {

    function getInfo() {
        return confToHash(dirname(__FILE__).'plugin.info.txt');
    }

    public function getLoader($plugin, $data) {
        $form = new Doku_Form(array('class' => 'ajax_loader'));
        $form->addHidden('call', "ajax_loader_$plugin");
        foreach($data as $k => $v) {
            $form->addHidden("ajax_loader_data[$k]", $v);
        }
        return '<div>' . $form->getForm() . '</div>';
    }

    public function isLoader($plugin, $call) {
        return $call === "ajax_loader_$plugin";
    }

    public function handleLoad() {
        return $_REQUEST['ajax_loader_data'];
    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
