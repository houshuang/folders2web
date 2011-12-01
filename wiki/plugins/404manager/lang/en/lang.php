<?php
/**
 * English language file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD <gerardnico@gmail.com>
 */
 
// ##################################
// ############ Admin Page ##########
// ##################################

$lang['AdminPageName'] = '404Manager Plugin';

//Error Message
$lang['SameSourceAndTargetAndPage'] = 'The target page and the source page are the same.';
$lang['NotInternalOrUrlPage'] = 'The target page don\' exist and is not a valid URL.';
$lang['SourcePageExist'] = 'The Source Page exist.';

//FeedBack Message
$lang['Saved']	= 'Saved';
$lang['Deleted'] = 'Deleted';
$lang['Validated'] = 'Validated';

//Array Header of the Admin Page
$lang['SourcePage'] = 'Source Page';
$lang['TargetPage'] = 'Target Page';
$lang['Valid'] = 'Valid';
$lang['CreationDate'] = 'Creation Date';
$lang['LastRedirectionDate'] = 'Last Redirection Date';
$lang['LastReferrer'] = 'Last Referrer';
$lang['Never'] = 'Never';
$lang['Direct Access'] = 'Direct Access';
$lang['TargetPageType'] = 'Target Page Type';
$lang['CountOfRedirection'] = 'Count Of Redirection';

// Head Titles
$lang['AddModifyRedirection'] = "Add/Modify Redirection";
$lang['ListOfRedirection'] = 'List of Redirections';

//Explication Message
$lang['ExplicationValidateRedirection'] = 'A validate redirection don\'t show any warning message. A unvalidate redirection is a proposition which come from an action "Go to best page".';
$lang['ValidateToSuppressMessage'] = "You must approve (validate) the redirection to suppress the message of redirection.";

// Forms Add/Modify Value
$lang['source_page'] = 'Source Page';
$lang['target_page'] = 'Target Page';
$lang['redirection_valid'] = 'Redirection Valid';
$lang['yes'] = 'Yes';
$lang['Field'] = 'Field' ;
$lang['Value'] = 'Value';
$lang['btn_addmodify'] = 'Add/Modify';

// ##################################
// ######### Action Message #########
// ##################################

$lang['message_redirected_by_redirect'] = 'The page ($ID) doesn\'t exist. You have been redirected automatically to the redirect page.';
$lang['message_redirected_to_edit_mode'] = 'This page doesn\'t exist. You have been redirected automatically in the edit mode.';
$lang['message_pagename_exist_one'] = 'The page name exist already in other namespace(s) with this page(s) :';
$lang['message_pagename_exist_two'] = 'You must change it to become unique. In this way, if you move it later, it can be easily retrieved. Please, change it by adding for instance the current namespace as prefix ( Example: ';
$lang['message_redirected_to_startpage'] = 'The page ($ID) doesn\'t exist. You have been redirected automatically to the start page of the namespace.';
$lang['message_redirected_to_bestpagename'] = 'The page ($ID) doesn\'t exist. You have been redirected automatically to the best page.';
$lang['message_redirected_to_bestnamespace'] = 'The page ($ID) doesn\'t exist. You have been redirected automatically to the best namespace.';
$lang['message_redirected_to_searchengine'] = 'The page ($ID) doesn\'t exist. You have been redirected automatically to the search engine.';
$lang['message_come_from'] = 'This message was fired by ';

?>