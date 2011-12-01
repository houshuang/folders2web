<?php
/**
 * dokubookmark plugin helper functions
 * Dokuwiki website tagger - act like a weblog
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Robin Gareus <robin@gareus.org>
 * @based_on   http://wiki.splitbrain.org/wiki:tips:weblog_bookmarklet by riny [at] bk [dot] ru
 */ 

  /**
   *  - TODO this should use the dokuwiki template header.
   */
  function printHeader() {
  global $conf;
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<title>Dokuwiki Website Tagger</title>
<link rel="stylesheet" media="all" type="text/css" href="<?php echo DOKU_BASE?>lib/exe/css.php?t=<?php echo $conf['template']?>" />
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo DOKU_BASE?>lib/exe/css.php?s=all&t=<?php echo $conf['template']?>" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="<?php echo DOKU_TPL?>images/favicon.ico" />
</head>
<body>
<script type="text/javascript">
/* <![CDATA[ */
function clicktag(tagname) {
  var oldtext = document.dwlog.wikitext.value;
  if (!oldtext) 
    oldtext = document.getElementById('i_wiki').value;

  var tagstart = oldtext.indexOf("{{tag>");
  if (tagstart >= 0) {
    // find blog-end
    var tagend = oldtext.substr(tagstart).indexOf("}}");
    if (tagend < 0) {
      alert('found incomlete {{tag}} wiki-tag');
      return;
    }

    // remove current tag 
    while (oldtext.substr(tagstart,tagend).indexOf(" "+tagname) >= 0) {
      var split = tagstart + oldtext.substr(tagstart,tagend).indexOf(" "+tagname); // leading " " 
      var len = tagname.length + 1; // TODO: check if we're not the first tag ! - leading "{"
      // Note: first tag is assumed to be "Bookmark"
      // check for full word with [} ] before and after. 
      var checkend=oldtext.substr(split+len,1);
      if (checkend != ' ' && checkend != '}' ) { alert('invalid {{tag}} wiki tag: '+checkend); return; }

      oldtext = oldtext.substr(0,split)+oldtext.substr(split+len);
      tagend = oldtext.substr(tagstart).indexOf("}}");
    } 

    if (tagend < 0) { alert('incomlete {{tag}} wiki-tag'); return; }

    if (document.getElementById('c_'+tagname).checked) { // insert tag 
      var split = tagstart+tagend;
      var ws ="";
      if (tagend-tagstart != 6) ws=" "; 
      oldtext = oldtext.substr(0,split)+ws+tagname+oldtext.substr(split);
    }

  } else {
    if (document.getElementById('c_'+tagname).checked) {
      oldtext+='\n{{tag>Bookmark '+tagname+'}}';
    }
  }

  document.getElementById('i_wiki').value = oldtext;
}
/* ]]> */
</script>
<div class="dokuwiki" style="background:transparent; border:0px;">
<?php 
  }


  /**
   *
   */
  function printFooter() { ?>
</div>
</body>
</html>
<?php 
  }

  function escapeJSstring ($o) {
    return ( # TODO: use JSON ?!
      str_replace("\n", '\\n', 
      str_replace("\r", '', 
      str_replace('\'', '\\\'', 
      str_replace('\\', '\\\\', 
        $o)))));
  }

  function parseWikiIdTemplate($idx, $data) {
    if (empty($data['name'])) {
			$n='';
			# check for single word selection -> use this
			if (!strstr($data['selection'], ' ')) 
				$n=$data['selection'];
			# check if title is a not empty 
			if (empty($n))
				$n=$data['title'];
			# if still empty.. mmh - use URL or use 'noname'
			if (empty($n)) $n='noname';

			#->  replace ': ' and ASCIIfy
			$n=strtr($n, ': ','__');
			# [^\x20-\x7E] or [^A-Za-z_0-9]
			$n=preg_replace('@[^A-Za-z_0-9]@', '', $n);
			$n=preg_replace('@__*@', '_', $n);
			# trim to 64 chars.
			$data['name']=substr($n,0,64);
		}
    # TODO: replace Placeholders alike ../../../inc/common.php pageTemplate() ?!
	  return str_replace("@D@",$data['timestamp'],
           str_replace("@S@",$data['selection'],
           str_replace("@U@",$data['url'],
           str_replace("@N@",$data['name'],
           str_replace("@F@",$data['foo'],
           str_replace("@T@",$data['title'], $idx))))));
  }


  /**
   *
   */
  function printForm($data, $options, $alltags = NULL) {

    echo '<h3>Dokuwiki - add bookmark / weblog entry</h3>';
    echo '<form name="dwlog" method="post" accept-charset="utf-8" action="'.$data['baseurl'].'">';
    echo '<fieldset style="width:85%; text-align:left;">';
    echo '<p><label>Id:</label><br/><input name="id" id="i_id" size="60" value="'.htmlentities(parseWikiIdTemplate($data['wikiidtpl'], $data), ENT_COMPAT, 'UTF-8').'"/>'; 

    if ($options['preset']) {
      echo '&nbsp;Preset:';
      $i=0;
      foreach ($options['presets'] as $n => $ps)  {
	$id       = parseWikiIdTemplate($ps['id'], $data);
        $wikitext = parseWikiIdTemplate($ps['tpl'], $data);

        if ($i>0) echo ","; else echo '&nbsp;';
        echo '&nbsp;<button type="button" class="button" onclick="document.getElementById(\'i_id\').value=\''.escapeJSstring($id).'\';';
	if (!empty($wikitext))
	  echo 'document.getElementById(\'i_wiki\').value=\''.escapeJSstring($wikitext).'\';';
	echo '">'.$n.'</button>';
	$i++;
      } 
    } ### done Preset Buttons

    echo '</p>'."\n";
    $wikitext = parseWikiIdTemplate($data['wikitpl'], $data);
    echo '<p><label>Wiki-Text:</label><br/><textarea id="i_wiki" name="wikitext" rows="9" cols="70">'.htmlentities($wikitext, ENT_COMPAT, 'UTF-8').'</textarea></p>';

    if ($options['tagbox'] && $alltags) {
      function clipstring($s, $len=22) {
        return substr($s,0,$len).((strlen($s)>$len)?'..':'');
      }
      echo '<div><label>Tags:</label><br/>';
      echo '<div style="overflow:auto; height:4em; margin-bottom:.5em;">';
      if ($options['tagboxtable']) echo '<table><tr>';
      $i=0;
      sort($alltags);
      foreach ($alltags as $t)  {
        if ($t=="bookmark") continue;
        if ($i%5==0 && $i!=0) { 
          if ($options['tagboxtable']) echo "</tr>\n<tr>";
          else echo "<br/>\n";
        }
        $i++;
        if ($options['tagboxtable']) echo '<td>';
        echo '<input type="checkbox" id="c_'.$t.'" value="1" name="'.$t.'" onclick="clicktag(\''.escapeJSstring($t).'\');" /> '.clipstring($t).'&nbsp;';
        echo "\n";
        if ($options['tagboxtable']) echo '</td>';
        # TODO: allow to limit number of tags ?
        #if ($i>100) { echo "&nbsp;..."; break; }
      }
      if ($options['tagboxtable']) echo '</tr></table>';
      echo '</div>';
      #echo '<p></p>';
      echo '</div>';
    } ## printed Tag-box

    echo '<p><label>Edit Summary:</label><br/><input name="summary" size="60" value="created: '.htmlentities($data['title'], ENT_COMPAT, 'UTF-8').'"/></p>';
    echo '<p>';
    echo '<input type="hidden" name="style" value="nomenu"/>';
    if ($options['enable_save']) {
      echo '<input type="hidden" name="sectok" value="'.getSecurityToken().'"/>';
      echo '<input class="button" type="submit" title="Save" value="Save" name="do[dokubookmark]"/>';
    }
    #echo '<input class="button" type="submit" title="Save" value="Save" name="do[save]"/>';
    echo '<input class="button" type="submit" title="Preview" value="Preview" name="do[preview]"/>';
    echo '&nbsp;|&nbsp;<button class="button" onclick="window.close()">Cancel</button>';
    #echo '&nbsp;<a href="javascript:window.close()">Abort</a>';
    echo '</p>';
    echo '</fieldset>';
    echo '</form>';
  }

  /**
   *  - unused javascript redirect/POST - 
   *
   *  - could be made into a non-interactive bookmarklet -
   */
  function printPost($targeturl, $path, $wikiid, $timestamp, $title, $wikitext) {
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<head>
<script type="text/javascript">
/* <![CDATA[ */
function postit() {
  f        = document.createElement('form');              
  f.method = 'post';                                      
  f.action = '<?php echo $targeturl;?>';

  i0       = document.createElement('input');             
  i0.type  = 'hidden';                                      
  i0.name  = 'wikitext';                                      
  i0.value = '<?php echo implode('\n', explode("\n",str_replace("'","\\'",$wikitext)));?>';
                                                          
  i1       = document.createElement('input');
  i1.type  = 'hidden';                                    
  i1.name  = 'do';
  i1.value = 'preview';

  i3       = document.createElement('input');
  i3.type  = 'hidden';                                    
  i3.name  = 'summary';
  i3.value = '<?php echo str_replace("'","\\'",rawurlencode($title));?>';

  i4       = document.createElement('input');
  i4.type  = 'hidden';                                    
  i4.name  = 'sectok';
  i4.value = '<?php echo getSecurityToken();?>';

  f.appendChild(i0);
  f.appendChild(i1);
  f.appendChild(i3);
  f.appendChild(i4);
  b = document.getElementsByTagName('body')[0];
  b.appendChild(f);
  f.submit();
  }
/* ]]> */
</script>
<body onload="postit();">
</body>
</html>
<?php
  }

//Setup VIM: ex: et ts=2 enc=utf-8 :
