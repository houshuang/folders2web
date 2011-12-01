<?php
/**
 * Options for the discussion plugin
 */

$conf['automatic']    = 0;   // discussion section on every page by default
$conf['allowguests']  = 1;   // should unregistred users be able to comment?
$conf['showguests']   = 1;   // show/hide comments from guests
$conf['linkemail']    = 0;   // link usernames with e-mail addresses
$conf['useavatar']    = 1;   // use Avatar Plugin to display user images in comments
$conf['urlfield']     = 0;   // allow entering an URL
$conf['addressfield'] = 0;   // allow entering an address
$conf['adminimport']  = 0;   // allow admins to set all the fields for import
$conf['usecocomment'] = 0;   // use coComment comment tracking
$conf['wikisyntaxok'] = 1;   // allow wiki syntax in comments
$conf['subscribe']    = 1;   // enable/disable subscribe to comments
$conf['newestfirst']  = 0;   // list newest comments first
$conf['moderate']     = 0;   // moderate newly added comments

$conf['usethreading'] = 1;
$conf['userealname']  = 1;

$conf['threads_formposition'] = 'bottom'; // position of new thread form

//Setup VIM: ex: et ts=2 enc=utf-8 :
