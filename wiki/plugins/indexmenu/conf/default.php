<?php
/**
 * Options for the Indexmenu2 plugin
 */
$conf['hide_headpage']    = 1;                                  // print headpage next to namespace
$conf['skip_index'] = "/^(wiki|discussion|playground)\$/i";     // regular expression to skip pages from the index
$conf['empty_msg'] = "No index available for <b>{{ns}}</b> namespace"; // message when no index available

$conf['replace_idx'] = 'no';                                    // replace built-in index
$conf['replace_idx_depth'] = 1;                                 // index tree depth
$conf['replace_idx_theme'] = 'IndexMenu';                       // index tree theme
//$conf['expand'] = 0;                                          // expand menu at the given namespace, useful for placing navigation menu at the sidebar


//Setup VIM: ex: et ts=2 enc=utf-8 :