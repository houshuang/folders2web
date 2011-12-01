<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the indexmenu plugin
 *
 * @author    Ilya Lebedev <ilya@lebedev.net>
 */
$meta['hide_headpage']     = array('onoff');
$meta['skip_index']        = array('string');
$meta['empty_msg']         = array('string');

$meta['replace_idx']       = array('multichoice','_choices' => array('no','static','ajax'));
$meta['replace_idx_depth'] = array('numeric');
$meta['replace_idx_theme'] = array('dirchoice'
                                 ,'_dir' => DOKU_INC.'lib/plugins/indexmenu/templates/DokuWiki/'
                                 );

//$meta['expand']           = array('onoff');

//Setup VIM: ex: et ts=2 enc=utf-8 :
