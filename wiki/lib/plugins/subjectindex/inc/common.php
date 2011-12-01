<?php
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

define('SUBJ_IDX_CHECK_TIME', DOKU_PLUGIN . 'subjectindex/conf/last_cleanup');

define('SUBJ_IDX_INDEX_NAME', 'subject');
define('SUBJ_IDX_DEFAULT_DIR', DOKU_INC . 'data/index/');
define('SUBJ_IDX_DEFAULT_PAGE', ':subjectindex');

define('SUBJ_IDX_TAG_RGX', '(?<=\s|^)#[^0-9]\w+?#');
define('SUBJ_IDX_ENTRY_RGX', '\{\{entry>.+?\}\}');
define('SUBJ_IDX_INDEXER_RGX', '\{\{entry>(.+?)\}\}');

/**
 * Returns the subject index file name
 *
 * @param string $data_dir  where subject index file is located
 * @return string
 */
function get_subj_index($data_dir) {
    $filename = SUBJ_IDX_INDEX_NAME . '.idx';
    if (is_dir($data_dir)) {
        $index_file = $data_dir . '/' . $filename;
    } else {
        $index_file = SUBJ_IDX_DEFAULT_DIR . $filename;
    }
    // create if missing
    if ( ! is_file($index_file)) {
        fclose(fopen($index_file, 'w'));
    }
    return $index_file;
}
/**
 * Gets the correct subject index wiki page name based on an index number
 * Defaults to first in list, or ':subjectindex' if missing
 *
 * @param string $index_pages list of wiki index pages delimited by ';'
 * @param integer $index which page are you looking for
 * @return string page name
 */
function get_index_page($index_pages, $index = 0) {
    $pages = explode(";", $index_pages);
    if (isset($pages[$index])) {
        $page = $pages[$index];
    } elseif ( ! empty($pages[0])) {
        $page = $pages[0];
    } else {
        $page = SUBJ_IDX_DEFAULT_PAGE;
    }
    return $page;
}
/**
 * Removes invalid chars from any string to make it suitable for use as a HTML id attribute
 * @param string $text Any text string
 * @return string A string suitable for a HTML id attribute
 */
function clean_id($text) {
    $text = strtolower($text);
    $text = str_replace('/', '-', $text);
    $text = preg_replace('/[^0-9a-zA-Z-_]/', '', $text);
    return $text;
}
/**
 * Does this page: exist, is it visible, and does user have rights to see it?
 */
function valid_page($id) {
    $id = trim($id);
    if(page_exists($id) && isVisiblePage($id) && ! (auth_quickaclcheck($id) < AUTH_READ)) {
        return true;
    } else {
        return false;
    }
}