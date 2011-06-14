<?php
/*
Plugin Name: SBibtex
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 0.1
Author: Stian Haklev
Author URI: http://URI_Of_The_Plugin_Author
License: GPL2
*/
function filter_bibtex($text) {

	$pattern = '/\\[\\@(.*)\\]/sU';
	preg_match_all($pattern, $text, $hits);
	if($hits[0][0]) {   // only load bibtex file if there are some citations
		$json =file_get_contents(dirname ( __FILE__ )."/json.tmp");
		$t = json_decode($json,true);

		foreach($hits[1] as $hit) {
			$entry = $t[$hit];
			$text = str_replace('[@' . $hit . ']',"<span class='tooltip_winlike'><a href='/wiki/ref:" . $hit . "'>".$entry[0].", ".$entry[1] . "</a><span class=\"tip\">".$entry[2]."</span></span>",$text);
		}
	}
	return $text;
}

add_filter ( 'the_content', 'filter_bibtex');
?>