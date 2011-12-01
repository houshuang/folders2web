<?php

/**
 * Plugin Columns: Information
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

function columns_getInfo($component = '') {
    $info = array(
        'author' => 'Mykola Ostrovskyy',
        'email'  => 'spambox03@mail.ru',
        'date'   => '2009-08-30',
        'name'   => 'Columns Plugin',
        'desc'   => 'Arrange information in multiple columns.',
        'url'    => 'http://www.dokuwiki.org/plugin:columns'
    );
    if ($component != '') {
        if (($_REQUEST['do'] == 'admin') && !empty($_REQUEST['page']) && ($_REQUEST['page'] == 'plugin')) {
            $info['name'] .= ' (' . $component . ')';
        }
    }
    return $info;
}
