<?php
require_once('utf8.php');
function cleanID($raw_id){
	$sepchar = "_";
        $sepcharpat = '#\\'.$sepchar.'+#';

    $id = trim((string)$raw_id);
    $id = utf8_strtolower($id);

    //alternative namespace seperator
    $id = strtr($id,';',':');
    if($conf['useslash']){
        $id = strtr($id,'/',':');
    }else{
        $id = strtr($id,'/',$sepchar);
    }

$id = utf8_romanize($id);
$id = utf8_deaccent($id,-1);

    //remove specials
    $id = utf8_stripspecials($id,$sepchar,'\*');

$id = utf8_strip($id);

    $id = preg_replace($sepcharpat,$sepchar,$id);
    $id = preg_replace('#:+#',':',$id);
    $id = preg_replace('#:[:\._\-]+#',':',$id);

    return($id);
}

print cleanID($_SERVER['argv'][1])."\n	";
?>