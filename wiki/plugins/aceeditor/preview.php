<?php

if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');

require_once DOKU_INC . 'inc/init.php';
require_once DOKU_INC . 'inc/parser/xhtml.php';

header('Content-Type', 'text/plain');

$renderer = new Doku_Renderer_xhtml();
$xhtml = $renderer->render($_GET['text']);

if (preg_match('/<img src="(.*?\?media=(.*?))"/', $xhtml, $matches)) {
    $url = $matches[1];
    $path = mediaFN($matches[2]);
    $size = getimagesize($path);
    $data = array(
        'url' => $url,
        'width' => $size[0],
        'height' => $size[1],
    );
    echo json_encode($data);
}
