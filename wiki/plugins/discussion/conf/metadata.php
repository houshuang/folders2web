<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the discussion plugin
 *
 * @author    Esther Brunner <wikidesign@gmail.com>
 */

$meta['automatic']    = array('onoff');
$meta['allowguests']  = array('onoff');
$meta['showguests']   = array('onoff');
$meta['linkemail']    = array('onoff');
$meta['useavatar']    = array('onoff');
$meta['urlfield']     = array('onoff');
$meta['addressfield'] = array('onoff');
$meta['adminimport']  = array('onoff');
$meta['usecocomment'] = array('onoff');
$meta['wikisyntaxok'] = array('onoff');
$meta['subscribe']    = array('onoff');
$meta['newestfirst']  = array('onoff');
$meta['moderate']     = array('onoff');

$meta['usethreading'] = array('onoff');
$meta['userealname']  = array('onoff');

$meta['threads_formposition'] = array(
                                  'multichoice',
                                  '_choices' => array('off', 'top', 'bottom')
                                );

//Setup VIM: ex: et ts=2 enc=utf-8 :
