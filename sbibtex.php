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
		$json =file_get_contents("/home/houshuan/public_html/wiki/lib/plugins/dokuresearchr/json.tmp");
		$t = json_decode($json,true);

		foreach($hits[1] as $hit) {
			$entry = $t[$hit];
			$oa = $entry[4];

			if($entry[4]) {
			$oa = "<a href='" . $entry[4] . "'><img src=/wiki/_media/wiki:pdficon_small.png></a> ";
			} else { $oa = '';}

			if($entry[1] == ""){
				$text = str_replace('[@' . $hit . ']',"<a href=http://wiki/ref:".$hit.">".$hit."</a>",$text);
			} else {
				$text = str_replace('[@' . $hit . ']',"<span class='tooltip_winlike'><a href='/wiki/ref:" . $hit . "'>".$entry[0].", ".$entry[1]."</u></a><span class=\"tip\">".$entry[2]."</span></span></span>".$oa,$text);
				}


		}
		return $text;
	}
}

    function add_my_stylesheet() {
		wp_enqueue_style( 'sbibtex', WP_PLUGIN_URL .'/sbibtex/sbibtex.css');
	}
add_filter ( 'the_content', 'filter_bibtex');
add_action('wp_print_styles', 'add_my_stylesheet');

?>