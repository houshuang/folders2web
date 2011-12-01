<?php
if (strpos($call, 'plugin_') === 0) {
    $name = explode('_', substr($call, 7), 3);
    $fname = DOKU_INC."lib/plugins/{$name[0]}/ajax" .
             (count($name) === 2 ? "/{$name[1]}" : "") . '.php';
    if (is_file($fname)) {
        require $fname;
        if (isset($AJAX_JSON)) {
            $json = new JSON;
            echo '(' . $json->encode($AJAX_JSON) . ')';
        }
        exit;
    }
}

//Setup VIM: ex: et ts=2 enc=utf-8 :
