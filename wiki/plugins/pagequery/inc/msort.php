<?php
// keep key associations
define('MSORT_KEEP_ASSOC', 'msort01');

// additional sorting type
define('MSORT_NUMERIC', 'msort02');
define('MSORT_REGULAR', 'msort03');
define('MSORT_STRING', 'msort04');
define('MSORT_STRING_CASE', 'msort05'); // case insensitive
define('MSORT_NAT', 'msort06');         // natural sorting
define('MSORT_NAT_CASE', 'msort07');    // natural sorting, case insensitive

define('MSORT_ASC', 'msort08');
define('MSORT_DESC', 'msort09');

define('MSORT_DEFAULT_DIRECTION', MSORT_ASC);
define('MSORT_DEFAULT_TYPE', MSORT_STRING);

/**
 * A replacement for array_mulitsort which permits natural and caseless sorting
 * This function will sort an 'array of rows' only (not array of 'columns')
 *
 * @param array $sort_array  : multi-dimensional array of arrays, where the first index refers to the row number
 *                             and the second to the column number (e.g. $array[row_number][column_number])
 *                             i.e. = array(
 *                                          array('name1', 'job1', 'start_date1', 'rank1'),
 *                                          array('name2', 'job2', 'start_date2', 'rank2'),
 *                                          ...
 *                                          );
 *
 * @param mixed $sort_opts   : options for how the array should be sorted
 *                   : AS ARGS
 *                             $key, $type, $direction [,$key2, $type2, $direction2, ...etc], $assoc
 *                             $key      = key/column to sort by
 *                             $type     = sorting type, one of following:
 *                                           MSORT_NUMERIC
 *                                           MSORT_REGULAR
 *                                           MSORT_STRING
 *                                           MSORT_STRING_CASE  : caseless sorting
 *                                           MSORT_NAT          : natural sorting
 *                                           MSORT_NAT_CASE
 *                             $direction = sorting direction:
 *                                           MSORT_ASC
 *                                           MSORT_DESC
 *                             $assoc     = keep associative array keys (uasort)
 *                                           MSORT_KEEP_ASSOC
 *                    :AS ARRAY
 *                             $sort_opts['key'][<column>] = 'key'
 *                             $sort_opts['type'][<column>] = 'type'
 *                             $sort_opts['dir'][<column>] = 'dir'
 *                             $sort_opts['assoc'][<column>] = MSORT_KEEP_ASSOC | true
 * @return boolean
 */

function msort(&$sort_array, $sort_opts) {

    // if a full sort_opts array was passed
    if (is_array($sort_opts)) {
        if (isset($sort_opts['assoc'])) {
            $keep_assoc = true;
        }
    // else separate the options from the function args
    } else {
        $args = func_get_args();

        if (end($args) == MSORT_KEEP_ASSOC) {
            $keep_assoc = true;
            array_pop($args);
        }

        // make sure there is something to sort
        if (empty($args)) return true;

        $type_enums = array(
                           MSORT_NUMERIC,
                           MSORT_REGULAR,
                           MSORT_STRING,
                           MSORT_STRING_CASE,
                           MSORT_NAT,
                           MSORT_NAT_CASE
                           );
        $direction_enums = array(
                                MSORT_ASC,
                                MSORT_DESC
                                );

        // work through the args list (SORT_KEY, SORT_TYPE, SORT_DIRECTION)
        $order = -1;
        foreach ($args as $arg) {
            // is it a sort direction?
            if (in_array($arg, $direction_enums)) {
                $sort_opts['dir'][$order] = $arg;
            // is it a sort type?
            } elseif (in_array($arg, $type_enums)) {
                $sort_opts['type'][$order] = $arg;
            // is it a sort array?
            } elseif (is_numeric($arg)) {
                $order++;
                $sort_opts['key'][$order] = $arg;
                $sort_opts['type'][$order] = MSORT_DEFAULT_TYPE;
                $sort_opts['dir'][$order] = MSORT_DEFAULT_DIRECTION;
            }
        }
    }

    // Determine which u..sort function (with or without associations).
    $sort_func = ($keep_assoc) ? 'uasort' : 'usort';

    // Sort the data and get the result.
    $result = $sort_func (
        $sort_array,
        function(array &$left, array &$right) use($sort_opts) {

            // Assume that the entries are the same.
            $cmp = 0;

            // Work through each sort column
            foreach($sort_opts['key'] as $idx => $key) {

                // Handle the different sort types.
                switch ($sort_opts['type'][$idx]) {
                    case MSORT_NUMERIC:
                        $key_cmp = ((intval($left[$key]) == intval($right[$key])) ? 0 :
                                   ((intval($left[$key]) < intval($right[$key])) ? -1 : 1 ) );
                        break;

                    case MSORT_STRING:
                        $key_cmp = strcmp((string)$left[$key], (string)$right[$key]);
                        break;

                    case MSORT_STRING_CASE: //case-insensitive
                        $key_cmp = strcasecmp((string)$left[$key], (string)$right[$key]);
                        break;

                    case MSORT_NAT:
                        $key_cmp = strnatcmp((string)$left[$key], (string)$right[$key]);
                        break;

                    case MSORT_NAT_CASE:    //case-insensitive
                        $key_cmp = strnatcasecmp((string)$left[$key], (string)$right[$key]);
                        break;

                    case MSORT_REGULAR:
                    default :
                        $key_cmp = (($left[$key] == $right[$key]) ? 0 :
                                   (($left[$key] < $right[$key]) ? -1 : 1 ) );
                    break;
                }

                // Is the column in the two arrays the same?
                if ($key_cmp == 0) {
                    continue;
                }

                // Are we sorting descending?
                $cmp = $key_cmp * (($sort_opts['dir'][$idx] == MSORT_DESC) ? -1 : 1);

                // no need for remaining keys as there was a difference
                break;
            }
            return $cmp;
        }
    );
	return $result;
}



// grouping types
define ('MGROUP_NONE', 'mgrp00');
define ('MGROUP_HEADING', 'mgrp01');
define ('MGROUP_NAMESPACE', 'mgrp02');

/**
 * group a multi-dimensional array by each level heading
 * @param array $sort_array : array to be grouped (result of 'msort' function)
 *                             last column should contain real dates if you need dates in words
 * @param array $keys       : which keys (columns) should be returned in results array? (index position)
 * @param mixed $group_opts :  AS ARRAY:
 *                             $group_opts['key'][<order>] = column key to group by
 *                             $group_opts['type'][<order>] = grouping type [MGROUP...]
 *                             $group_opts['dformat'][<order>] = date formatting string
 *
 * @return array $results   : array of arrays: (level, display_name, page_id), e.g. array(1, 'Main Title')
 *                              array(0, '...') =>  0 = normal row item (not heading)
 */
function mgroup(&$sort_array, $keys, $group_opts) {
    $level = count($group_opts['key']) - 1;
    $prevs = array();
    $results = array();
    $idx = 0;

    foreach($sort_array as $row) {
        _add_heading($results, $sort_array, $group_opts, $level, $idx, $prevs);
        $result = array(0); // basic item (page link) is level 0
        for ($i = 0; $i < count($keys); $i++) {
            $result[] = $row[$keys[$i]];
        }
        $results[] = $result;
        $idx++;
    }
    return $results;
}
/**
 * as above, but by args:
*                    AS ARGS:
*                              key, type, dformat [,order, type, dformat]... [,real_dates]
*                              key      = group by key/column
*                              type     = one of the MGROUP types
*                              dformat  = date format (if used)
 *
 * @param array $sort_array
 * @param array $cols
 * @param mixed $group_opts
 */
function mgroup_args($sort_array, $get_cols, $group_opts) {
    $args = func_get_args();

    $type_enums = array(
                        MGROUP_HEADING,
                        MGROUP_NAMESPACE,
                        );

    // get all the grouping options
    // order (which key/column), type (grouping type see MGROUP_...), dformat (real date display format),... repeated
    $group_opts = array();
    $order = -1;
    foreach ($args as $arg) {
        if (is_numeric($arg)) {
            $order++;
            $group_opts['key'][$order] = $arg;
            $group_opts['type'] [$order]= MGROUP_HEADING; // defaults
            $group_opts['dformat'] [$order]= '';
        } elseif ($order > -1) {
            if (in_array($arg, $type_enums)) {
                $group_opts['type'][$order] = $arg;
            } else {
                $group_opts['dformat'][$order] = $arg;
            }
        }
    }
    $level = $order;
}

/**
 * private function used by mgroup only!
 */
function _add_heading(&$results, &$sort_array, &$group_opts, $level, $idx, &$prevs) {
    static $end_col = 0;

    // recurse to find all parent headings
    if ($level > 0) {
        _add_heading($results, $sort_array, $group_opts, $level - 1, $idx, $prevs);
    }
    $group_type = $group_opts['type'][$level];

    $prev = (isset($prevs[$level])) ? $prevs[$level] : '';
    $key = $group_opts['key'][$level];
    $cur = $sort_array[$idx][$key];
    if ($cur != $prev) {
        $prevs[$level] = $cur;

        if ($group_type === MGROUP_HEADING) {
            $date_format = $group_opts['dformat'][$level];
            if ( ! empty($date_format)) {
                // the real date is always the last column (or item in array row)
                // static is used to avoid checking array length repeatedly
                if ($end_col == 0) $end_col = count($sort_array[0]) - 1;
                $cur = strftime($date_format, $sort_array[$idx][$end_col]);
            }
            $results[] = array($level + 1, $cur, '');

        } elseif ($group_type === MGROUP_NAMESPACE) {
            $cur_ns = explode(':', $cur);
            $prev_ns = explode(':', $prev);
            // only show namespaces that are different from the previous heading
            for ($i= 0; $i < count($cur_ns); $i++) {
                if ($cur_ns[$i] != $prev_ns[$i]) {
                    $hl = $level + $i + 1;
                    $results[] = array($hl , $cur_ns[$i], '');
                }
            }
        }
    }
}
?>