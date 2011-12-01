<?php
/**
 * Options for the amazon plugin
 */

$meta['publickey']  = array('string');
$meta['privatekey'] = array('string');

$meta['maxlen']    = array('numeric');
$meta['imgw']      = array('numeric');
$meta['imgh']      = array('numeric');
$meta['showprice'] = array('onoff');

$meta['partner_us']     = array('string');
$meta['partner_de']     = array('string');
$meta['partner_jp']     = array('string');
$meta['partner_uk']     = array('string');
$meta['partner_fr']     = array('string');
$meta['partner_ca']     = array('string');

$meta['showpurchased'] = array('onoff');
$meta['sort']          = array('multichoice', _choices => array('Priority', 'Price', 'DateAdded'));
