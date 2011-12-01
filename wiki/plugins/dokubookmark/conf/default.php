<?php
/**
 * Options for the dokubookmark Plugin
 */
$conf['namespace']    = 'wiki:weblog:@D@'; // default location for weblog entries
$conf['presets']      = 'Private=weblog:@D@;Public=wiki:weblog:@D@|wiki:tags:new';  // semicolon separated preset name=location[|template-path] pairs for weblog entries
$conf['tagbox']       = 1; // show tag checkboxes
$conf['tagboxtable']  = 1; // arrange tags in a table
$conf['dateformat']   = 'Y:m_d_His'; // format of @D@ datetime.
$conf['enable_save']  = 0; // allow to bypass the preview stage.
$conf['wikitemplate'] = '====== @T@ ======\n[[@U@]]\n----\n@S@\n\n{{tag>Bookmark}}';

//Setup VIM: ex: et ts=2 enc=utf-8 :
