<?php
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
  if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
  require_once(DOKU_PLUGIN.'action.php');

/** Clickable index.
 	Based on Thierry Legras's idea.
    Overrides the index command to provide a clickable index. 
    Based on aq3tree (http://www.kryogenix.org/code/browser/aqlists/)
    License: GPL
    */


class action_plugin_fullindex extends DokuWiki_Action_Plugin {
	//store the namespaces for sorting
	var $sortIndex = array();

	/**
	 * Constructor
	 */
	function action_plugin_fullindex(){
		$this->setupLocale();
	}

	function getInfo() {
		return array('author' => 'Martin Tschofen',
					 'email'  => 'mtbrains@comcast.net',
					 'date'   => '2007-Feb-04',
					 'name'   => 'fullindex',
					 'desc'   => 'Collapsable Index with alternate page names and numbers',
					 'url'    => '');
	}

  function register(&$controller) {
    $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'html_index_clickable');
  }
  
  function html_index_clickable(&$event){
	global $conf;
	global $ID;
    if ($event->data != 'index') return;
		require_once(DOKU_INC.'inc/search.php');
		$dir = $conf['datadir'];
		$ns  = cleanID($ns);
		#fixme use appropriate function
		if(empty($ns)){
			$ns = dirname(str_replace(':','/',$ID));
			if($ns == '.') $ns ='';
		}
		$ns  = utf8_encodeFN(str_replace(':','/',$ns));
		print $this->plugin_locale_xhtml('intro');
		
		$data = array();
		search($data,$conf['datadir'],array(&$this,'search_fullindex'),array('ns' => $ns));
		usort($data, "_strnatSort");
		
		print '<span class="aqLbl">'.$this->getLang('collapse_header').'</span><ul id="aqNav">';
		foreach ($this->getLang('collapse') as $key => $item) {
			print '<li id="aqli'.$key.'"><a href="#" onclick="aq_show('.$key.', this)">'.$item.'</a></li>';
		}
		print '</ul>';
		//if conf is not set to titles
		if($this->getConf('link_names') == 0){
			print $this->html_build_full_list($data,'idx','html_list_index','html_li_index');
		} else { //alternative titles
			print $this->html_build_full_list($data,'idx',array(&$this,'_html_title_index'),'html_li_index');
		}
		
		// prevent Dokuwiki normal processing of $ACT (it would clean the variable and destroy our 'index' value.
		$event->preventDefault();
		// index command belongs to us, there is no need to hold up Dokuwiki letting other plugins see if its for them
		$event->stopPropagation();
  }
  
	/**
	* Build an unordered list
	* Based on inc/search.php - @author Andreas Gohr <andi@splitbrain.org>
	*/
	function html_build_full_list($data,$class,$func,$lifunc='html_li_default'){
		$level = 0;
		$opens = 0;
		$ret   = '';
		
		foreach ($data as $item){
			if( $item['level'] > $level ){
			  //open new list
			  for($i=0; $i<($item['level'] - $level); $i++){
				if ($i) $ret .= "<li class=\"clear\">\n";
				if ($level > 0) 
				  $ret .= "\n<ul class=\"$class\">\n";
				else 
				  $ret .= "\n<ul class=\"aqtree3clickable\">\n";
			  }
			}elseif( $item['level'] < $level ){
			  //close last item
			  $ret .= "</li>\n";
			  for ($i=0; $i<($level - $item['level']); $i++){
				//close higher lists
				$ret .= "</ul>\n</li>\n";
			  }
			}else{
			  //close last item
			  $ret .= "</li>\n";
			}
			
			//remember current level
			$level = $item['level'];
			
			//print item
			if(is_array($lifunc)){
			  $ret .= $lifunc[0]->$lifunc[1]($item); //user object method
			}else{
			  $ret .= $lifunc($item); //user function
			}
	
			if(is_array($func)){
				$ret .= $func[0]->$func[1]($item); //user object method
			} else {
				$ret .= $func($item); //user function
			}
		}
		
		//close remaining items and lists
		for ($i=0; $i < $level; $i++){
			$ret .= "</li></ul>\n";
		}
		
		return $ret;
	}

	/**
	 * find all items and collect necessary information
	 */
	 
	function search_fullindex(&$data,$base,$file,$type,$lvl,$opts){
		global $conf;
		
		$return = true;
		
		$item = array();
		
		if($type == 'd' && !preg_match('#^'.$file.'(/|$)#','/'.$opts['ns'])){
			//always true - only difference to the inc/search.php function
			$return = true; 
		}elseif($type == 'f' && !preg_match('#\.txt$#',$file)){
			//don't add
			return false;
		}
		
		$id = pathID($file);
		
		//check hidden
		if(isHiddenPage($id)){
			return false;
		}
		
		//check ACL
		if($type=='f' && auth_quickaclcheck($id) < AUTH_READ){
			return false;
		}
		
		//don't display any namespace's index file -- displayed as the namespace instead
		if($type == 'f' && preg_match('#\:'.$conf['start'].'$#', $id)) { return false;}
		
		//setup the sort string
		if($type=='d'){
			$num = $this->_getMetaTag($id.":".$conf['start'], 'identifier');
			$title = $this->_getMetaTag($id.":".$conf['start'], 'alternative');
		} else {
			$num = $this->_getMetaTag($id, 'identifier');
			$title = $this->_getMetaTag($id, 'alternative');
		}
		
		
		$data[]=array('id'    => $id,
					 'type'  => $type,
					 'level' => $lvl,
					 'num'   => $num,
					 'title' => $title,
					 'open'  => $return,
					 'sort'  => $this->_setSortIndex($id, $lvl, $type, $num, $title));
		return $return;
	}

	/*
	 * Create sort index for each item
	 */

	function _setSortIndex($id, $lvl, $type, $num, $title) {
		global $conf;
		
		//directories
		if ($type == 'd') {
			//if same add dir to array
			if(count($this->sortIndex) + 1 == $lvl) {
				//add to the sortIndex
				$this->_addToSortIndex($id, $num, $title);				
			} else if (count($this->sortIndex) + 1 > $lvl) {
				$this->_removeFromSortIndex($lvl);
				//and add new index
				$this->_addToSortIndex($id, $num, $title);				
			} else {
				//remove from index
				array_pop($this->sortIndex);
			}
				$sortIndex = "";
		//files
		} else {
			if(count($this->sortIndex) + 1 > $lvl) {
				$this->_removeFromSortIndex($lvl);
			}
			$temp = trim($num.$title);
			if(empty($temp)) {
				$sortIdx = $id;
			} else {
				$sortIdx = $this->_cleanForSort($num)." ".$title; //space required for natsearch fix to work!
			}	
		}
		/*debug
		print_r($type);
		print_r(": count=");
		print_r(count($this->sortIndex) + 1);
		print_r('---lvl='.$lvl.'<br>');
		print_r("id=");
		print_r($id);
		print_r(" <i>");
		print_r($this->sortIndex);
		print_r("</i><br>");
		*/
		return implode("",$this->sortIndex).$sortIdx;
	}
	
	/**
	 * add an namespace to the sortIndex array
	 */
	 function _addToSortIndex($id, $num, $title){
		$newIndex = trim($num.$title);
		if ($newIndex == "") {
			$newIndex = strrchr($id, ":");
			//what if it was a root ns?
			if ($newIndex == "") {
				$newIndex = $id;
			}
		}
		$this->sortIndex[] = $newIndex;		
	}	 
	/**
	 * remove any number of namespaces from the sortIndex array
	 */
	 
	 function _removeFromSortIndex($lvl) {
	 	$diff = count($this->sortIndex) + 1 - $lvl;
		while($diff > 0){
			//backed out of namespace
			array_pop($this->sortIndex);
			$diff = $diff - 1;
		}
	}
	/**
	 * build the individual items
	 */

	function _html_title_index($item){
	  	$ret = '';
		$base = ':'.$item['id'];
		$base = substr($base,strrpos($base,':')+1);
		if($item['type']=='d'){
			$name = $item['num']." ".$item['title'];
			$name = trim($name);
			if($name == ''){
				$name = $item['id'];}
			$ret .= html_wikilink(':'.$item['id'].":", $name);
			
			//remove link if namespace/globalstart page doesn't exist
			if(preg_match('#wikilink2#', $ret)) {
				//should return somekind of meta info for the namespace instead -- only if NS sorting?
				$ret = '<span>'.noNS($item['id']).'</span>';
			}
		} else {
			$name = $item['num']." ".$item['title'];
			$name = trim($name);
			if($name == ''){
				$ret .= html_wikilink(':'.$item['id']);
			} else {
				$ret .= html_wikilink(':'.$item['id'], $name);
			}
		}
		return $ret;
	}
	
	function _getMetaTag($page, $term) {
		/*if(!$page){
			return "";
		}*/
		//return the found meta tag
		$data = p_get_metadata($page);
		if (array_key_exists($term, $data)){
			return $data[$term];
		} else {
			return "";
		}
	}

	/**
	 * fix the natural sort, if a number has characters imbedded
	 */
	 
	function _cleanForSort($num) {
		return preg_replace('/([a-zA-Z])/', '*$1', $num);
	}

} //end of action class

//utilities: natural sort
function _strnatSort($a, $b) { 
	return strnatcasecmp($a['sort'], $b['sort']);
}