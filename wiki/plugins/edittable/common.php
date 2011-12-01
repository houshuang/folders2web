<?php
/**
 * Helper for table to wikitext conversion
 *
 * @author Adrian Lang <lang@cosmocode.de>
 */

function table_to_wikitext($_table){
    // Preprocess table for rowspan, make table 0-based.
    $table = array();
    $start = array_pop(array_keys($_table));
    foreach($_table as $i => $row) {
        $inorm = $i - $start;
        if (!isset($table[$inorm])) $table[$inorm] = array();
        $nextkey = 0;
        foreach ($row as $cell) {
            while (isset($table[$inorm][$nextkey])) {$nextkey++;}
            $nextkey += $cell['colspan'] - 1;
            $table[$inorm][$nextkey] = $cell;
            $rowspan = $cell['rowspan'];
            $i2 = $inorm + 1;
            while ($rowspan-- > 1) {
                if (!isset($table[$i2])) $table[$i2] = array();
                $nu_cell = $cell;
                $nu_cell['text'] = ':::';
                $nu_cell['rowspan'] = 1;
                $table[$i2++][$nextkey] = $nu_cell;
            }
        }
        ksort($table[$inorm]);
    }

    // Get the max width for every column to do table prettyprinting.
    $m_width = array();
    foreach($table as $row) {
        foreach($row as $n => $cell) {
            // Calculate cell width.
            $diff = (utf8_strlen($cell['text']) + $cell['colspan'] +
                    ($cell['align'] === 'center' ? 3 : 2));

            // Calculate current max width.
            $span = $cell['colspan'];
            while (--$span >= 0) {
                if (isset($m_width[$n - $span])) {
                    $diff -= $m_width[$n - $span];
                }
            }

            if ($diff > 0) {
                // Just add the difference to all cols.
                while(++$span < $cell['colspan']) {
                    $m_width[$n - $span] = (isset($m_width[$n - $span]) ? $m_width[$n - $span] : 0) + ceil($diff / $cell['colspan']);
                }
            }
        }
    }

    // Write the table.
    $types = array('th' => '^', 'td' => '|');
    $str = '';
    foreach ($table as $row) {
        $pos = 0;
        foreach ($row as $n => $cell) {
            $pos += utf8_strlen($cell['text']) + 1;
            $span = $cell['colspan'];
            $target = 0;
            while (--$span >= 0) {
                if (isset($m_width[$n - $span])) {
                    $target += $m_width[$n - $span];
                }
            }
            $pad = $target - utf8_strlen($cell['text']);
            $pos += $pad + ($cell['colspan'] - 1);
            switch ($cell['align']) {
            case 'right':
                $lpad = $pad - 1;
                break;
            case 'left': case '':
                $lpad = 1;
                break;
            case 'center':
                $lpad = floor($pad / 2);
                break;
            }
            $str .= $types[$cell['tag']] . str_repeat(' ', $lpad) .
                    $cell['text'] . str_repeat(' ', $pad - $lpad) .
                    str_repeat($types[$cell['tag']], $cell['colspan'] - 1);
        }
        $str .= $types[$cell['tag']] . DOKU_LF;
    }
    return $str;
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
