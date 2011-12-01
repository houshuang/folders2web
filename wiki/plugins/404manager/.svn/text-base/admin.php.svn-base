<?php
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');
require_once(DOKU_INC . 'inc/parser/xhtml.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_404manager extends DokuWiki_Admin_Plugin {
	
	// Variable var and not public/private because php4 can't handle this kind of variable
	
	// To handle the redirection data
	var $Vc_DataFileLocation = '';
	var $Vc_RedirectData = array();
	
	// Use to pass parameter between the handle and the html function to keep the form data 
	var $Vc_SourcePage = '';
    var $Vc_TargetPage = '';
    var $Vc_CurrentDate = '';
    var $Vc_IsValidate = '';
    var $Vc_TargetPageType = 'Default';
	
	function admin_plugin_404manager(){
		
		//Set the redirection data
		$this->Vc_DataFileLocation = dirname(__FILE__).'/404managerRedirect.conf';
		if(@file_exists($this->Vc_DataFileLocation)) {
				$this->Vc_RedirectData = unserialize(io_readFile($this->Vc_DataFileLocation, false));	
		}
	 	
	 	// enable direct access to language strings
	 	// of use of $this_>getLang
        $this->setupLocale();
        $this->InfoPlugIn = $this->getInfo();
        $this->Vc_CurrentDate = date("d/m/Y");
	}
	
    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
    }

    /**
     * Access for managers allowed
     */
    function forAdminOnly(){
        return false;
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 140;
    }

    /**
     * return prompt for admin menu
     */
    function getMenuText($language) {
        $Vl_MenuText = $this->lang['AdminPageName'];
		if ( $Vl_MenuText == '' ) {
			$Vl_MenuText = $this->InfoPlugIn['name'];
		}
		return $Vl_MenuText;
    }

    /**
     * handle user request
     */
    function handle() {
        if($_POST['Add']){
        	
        	$this->Vc_SourcePage = $_POST['SourcePage'];
        	$this->Vc_TargetPage = $_POST['TargetPage'];       	
        	
        	if ( $this->Vc_SourcePage == $this->Vc_TargetPage ) {
        		msg($this->lang('SameSourceAndTargetAndPage').': '.$this->Vc_SourcePage.'',-1);
        		return;
        	}
        	
	        if ( !page_exists($this->Vc_TargetPage) ) {
	        	if ($this->IsValidURL($this->Vc_TargetPage)) {
	        		$this->Vc_TargetPageType = 'Url';
	        	} else {
	        		msg($this->lang('NotInternalOrUrlPage').': '.$this->Vc_TargetPage.'',-1);
	        		return;	
	        	}
	        } else {
	        	$this->Vc_TargetPageType = 'Internal Page'; 
	        }
	        
	        if ( page_exists($this->Vc_SourcePage) ) {
	        	$title = false;
	        	if ($conf['useheading']) {
	        		$title = p_get_first_heading($this->Vc_SourcePage);	
	        	}
      			if(!$title) $title = $this->Vc_SourcePage;
	        	msg($this->lang('SourcePageExist').' : <a href="'.wl($this->Vc_SourcePage).'">'.hsc($title).'</a>',-1);
	        	return;
	        } 
	        
	        $this->SetRedirection($this->Vc_SourcePage,$this->Vc_TargetPage);
	        msg($this->lang['Saved'],1);
	        
        }
      	if($_POST['Delete']){
        	$Vl_SourcePage = $_POST['SourcePage'];
        	$this->DeleteRedirection($Vl_SourcePage);
            msg($this->lang['Deleted'],1);
        }
    	if($_POST['Validate']){
        	$Vl_SourcePage = $_POST['SourcePage'];
        	$this->ValidateRedirection($Vl_SourcePage);
            msg($this->lang['Validated'],1);
        }
    }

    /**
     * output appropriate html
     */
    function html() {
    	
    	global $conf;
    	    	   	
        echo $this->locale_xhtml('intro');       
     	
     	
//      List of redirection
        ptln( '<h2><a name="list_redirection" id="list_redirection">'.$this->lang['ListOfRedirection'].'</a></h2>');
	 	ptln( '<div class="level2">');
	 	 	
        ptln( '<table class="inline">' );
    	ptln( '	<thead>' );
      	ptln( '		<tr>' );
        ptln( '			<th>&nbsp;</th>' );
        ptln( '			<th>'.$this->lang['SourcePage'].'</th>' );
        ptln( '			<th>'.$this->lang['TargetPage'].'</th>' );
        ptln( '			<th>'.$this->lang['TargetPageType'].'</th>' );
        ptln( '			<th>'.$this->lang['Valid'].'</th>' );
        ptln( '			<th>'.$this->lang['CreationDate'].'</th>' );
        ptln( '			<th>'.$this->lang['LastRedirectionDate'].'</th>' );
        ptln( '			<th>'.$this->lang['LastReferrer'].'</th>' );
        ptln( '			<th>'.$this->lang['CountOfRedirection'].'</th>' );
      	ptln( '	    </tr>' );
      	ptln( '	</thead>' );
      	
      	ptln( '	<tbody>' );		
    	foreach ($this->Vc_RedirectData as $Vl_SourcePage => $Vl_Attributes) {
    			
    			$title = false;
    			if ($conf['useheading']) {
	        		$title = p_get_first_heading($Vl_Attributes['TargetPage']);	
	        	}
      			if(!$title) $title = $Vl_Attributes['TargetPage'];
      			
    			
	    		ptln( '	  <tr class="redirect_info">');
	    		ptln( '		<td>');
	    		ptln( '			<form action="" method="post">');
	    		ptln( '				<input type="image" src="'.DOKU_BASE.'lib/plugins/404manager/images/delete.jpg" name="Delete" title="Delete" alt="Delete" value="Submit" />');
	    		ptln('				<input type="hidden" name="Delete"  value="Yes" />');
	    		ptln( '				<input type="hidden" name="SourcePage"  value="'.$Vl_SourcePage.'" />');
	    		ptln( '			</form>');
	        				
	        	ptln( '		</td>');
	        	print( '	<td>');
	        				tpl_link(wl($Vl_SourcePage),$Vl_SourcePage,'title="'.$Vl_SourcePage.'" class="wikilink2" rel="nofollow"');
	        	ptln( '		</td>');
	        	print '		<td>';
	        				tpl_link(wl($Vl_Attributes['TargetPage']),$Vl_Attributes['TargetPage'],'title="'.hsc($title).'"');
	        	ptln( '		</td>');	
	        	ptln( '		<td>'.$Vl_Attributes['TargetPageType'].'</td>');        				
	        				if ( $Vl_Attributes['IsValidate'] == 'N' ) {
	        	ptln( '		<td><form action="" method="post">');
	    		ptln( '				<input type="image" src="'.DOKU_BASE.'lib/plugins/404manager/images/validate.jpg" name="validate" title="'.$this->lang['ValidateToSuppressMessage'].'" alt="Validate" />');
	    		ptln('				<input type="hidden" name="Validate"  value="Yes" />');
	    		ptln( '				<input type="hidden" name="SourcePage"  value="'.$Vl_SourcePage.'" />');
	    		ptln( '		</form></td>');		
	        				} else {
	        	ptln( '		<td>Yes</td>');
	        				}
	        	ptln( '		<td>'.$Vl_Attributes['CreationDate'].'</td>');
	        	ptln( '		<td>'.$Vl_Attributes['LastRedirectionDate'].'</td>');
	        	if ( $this->IsValidUrl($Vl_Attributes['LastReferrer'])) {
	        		print( '	<td>');
	        				tpl_link($Vl_Attributes['LastReferrer'],$Vl_Attributes['LastReferrer'],'title="'.$Vl_Attributes['LastReferrer'].'" class="urlextern" rel="nofollow"');
	        		print( '	</td>');
	        	} else {
	        		ptln( '		<td>'.$Vl_Attributes['LastReferrer'].'</td>');	
	        	}
	        	ptln( '		<td>'.$Vl_Attributes['CountOfRedirection'].'</td>');
				ptln( '    </tr>');
    	}
  		ptln( '  </tbody>');
  		ptln( '</table>');
  		ptln( '<div class="fn">'.$this->lang['ExplicationValidateRedirection'].'</div>');
     	ptln( '</div>');	
     	
     	// Add a redirection
  	 	ptln( '<h2><a name="add_redirection" id="add_redirection">'.$this->lang['AddModifyRedirection'].'</a></h2>');
	 	ptln( '<div class="level2">');
	 	ptln( '<form action="" method="post">');
	 	ptln( '<table class="inline">');
        	
			ptln( '<thead>');
          	ptln( '		<tr><th>'.$this->lang['Field'].'</th><th>'.$this->lang['Value'].'</th></tr>');
        	ptln( '</thead>');
        	
        	ptln( '<tbody>');
			ptln( '		<tr><td><label for="add_sourcepage" >'.$this->lang['source_page'].'.: </label></td><td><input type="text" id="add_sourcepage" name="SourcePage" value="'.$this->Vc_SourcePage.'" class="edit" /></td></tr>');
          	ptln( '		<tr><td><label for="add_targetpage" >'.$this->lang['target_page'].': </label></td><td><input type="text" id="add_targetpage" name="TargetPage" value="'.$this->Vc_TargetPage.'" class="edit" /></td></tr>');
          	ptln( '		<tr><td><label for="add_valid" >'.$this->lang['redirection_valid'].': </label></td><td>'.$this->lang['yes'].'</td></tr>');
            ptln( '</tbody>');
        	
            ptln( '<tbody>');
          	ptln( '		<tr>');
            ptln( '			<td colspan="2">');
            ptln( '				<input type="hidden" name="do"    value="admin" />');
            ptln( '				<input type="hidden" name="page"  value="404manager" />');
            ptln( '				<input type="submit" name="Add" class="button" value="'.$this->lang['btn_addmodify'].'" />');
            ptln( '			</td>');
	        ptln( '		</tr>');
        	ptln( '</tbody>');
     	ptln( '</table>');
    
     	ptln( '</form>');
     	echo $this->locale_xhtml('add');
     	ptln( '</div>');
     
    		        
    }
    
    /**
	* Delete Redirection
	* @param    string      SourcePageName Wiki Page
	*/
    function DeleteRedirection($Vl_Sequence) {
    	unset($this->Vc_RedirectData[$Vl_Sequence]);
    	$this->Save();
    }
    
    /**
	* Is Redirection Present
	* @param    string      SourcePageName Wiki Page
	*/
    function IsRedirectionPresent($Vl_Sequence) {
    	if (isset($this->Vc_RedirectData[$Vl_Sequence])) {
	        return 1;	
	    } else {
	    	return 0;
	    }
    }
    
    /**
	* Add Redirection 
	* @Vl_SourcePage    string      SourcePageName Wiki Page
	* @Vl_TargetPage    string      TargetPageName Wiki Page
	* @Vl_Validate		string      Y or N to validate the redirection
	*/
    function SetRedirection($Vl_SourcePage, $Vl_TargetPage ) {
    	
    	if (!isset($this->Vc_RedirectData[$Vl_SourcePage])) {
	        	
    			$this->Vc_RedirectData[$Vl_SourcePage]['CreationDate']   = $this->Vc_CurrentDate;
	    		
		    	// If the call come from the admin page and not from the process function
		        if ( substr_count($_SERVER['HTTP_REFERER'],'admin.php' ) ) {
		        	$this->Vc_RedirectData[$Vl_SourcePage]['IsValidate'] = 'Y';
		        	$this->Vc_RedirectData[$Vl_SourcePage]['CountOfRedirection']   = 0;
		        	$this->Vc_RedirectData[$Vl_SourcePage]['LastRedirectionDate']   = $this->lang['Never'];
		        	$this->Vc_RedirectData[$Vl_SourcePage]['LastReferrer']   = 'Never';
		        } else {
		        	$this->Vc_RedirectData[$Vl_SourcePage]['IsValidate'] = 'N';	
		        	$this->Vc_RedirectData[$Vl_SourcePage]['CountOfRedirection']   = 1;
		        	$this->Vc_RedirectData[$Vl_SourcePage]['LastRedirectionDate']   = $this->Vc_CurrentDate;
		        	if ( $_SERVER['HTTP_REFERER'] <> '' ) {
		        		$this->Vc_RedirectData[$Vl_SourcePage]['LastReferrer'] = $_SERVER['HTTP_REFERER'];
		        	} else {
		        		$this->Vc_RedirectData[$Vl_SourcePage]['LastReferrer'] = $this->lang['Direct Access']; 	
		        	}
		        }
		        
        }
        
        if ( !$this->IsValidURL($Vl_TargetPage) ) {
        	$this->Vc_RedirectData[$Vl_SourcePage]['TargetPageType'] = 'Internal Page';	
        } else {
        	$this->Vc_RedirectData[$Vl_SourcePage]['TargetPageType'] = 'Url';
        }
        $this->Vc_RedirectData[$Vl_SourcePage]['TargetPage']     = $Vl_TargetPage;
    	$this->Save();
    }
    
    /**
	* Validate Redirection
	* @param    string      SourcePageName Wiki Page
	*/
    function ValidateRedirection($Vl_Sequence) {
    	$this->Vc_RedirectData[$Vl_Sequence]['IsValidate'] = 'Y';
    	$this->Save();
    }
    
    /**
	* Get IsValidate Redirection
	* @param    string      SourcePageName Wiki Page
	*/
    function GetIsValidate($Vl_Sequence) {
    	return $this->Vc_RedirectData[$Vl_Sequence]['IsValidate'];
    }	
    
    /**
	* Get TargetPageType 
	* @param    string      SourcePageName Wiki Page
	*/
    function GetTargetPageType($Vl_Sequence) {
    	return $this->Vc_RedirectData[$Vl_Sequence]['TargetPageType'];
    }
    
    /**
	* Get TargetPage 
	* @param    string      SourcePageName Wiki Page
	*/
    function GetTargetPage($Vl_Sequence) {
    	return $this->Vc_RedirectData[$Vl_Sequence]['TargetPage'];
    }
    
    /**
	* Set Redirection Action Data as Referrer, Count Of Redirection, Redirection Date
	* @param    string      SourcePageName Wiki Page
	*/
    function SetRedirectionActionData($Vl_Sequence) {
    	$this->Vc_RedirectData[$Vl_Sequence]['LastRedirectionDate']   = $this->Vc_CurrentDate;
    	$this->Vc_RedirectData[$Vl_Sequence]['LastReferrer']  = $_SERVER['HTTP_REFERER'];
    	$this->Vc_RedirectData[$Vl_Sequence]['CountOfRedirection']  = $this->Vc_RedirectData[$Vl_Sequence]['CountOfRedirection'] + 1;
    	$this->Save();
    }
    
    /**
     * Serialize and save the redirection data file
     */
    function Save() {
    	io_saveFile($this->Vc_DataFileLocation, serialize($this->Vc_RedirectData));
    }
    
    /**
	* Validate URL
	* Allows for port, path and query string validations
	* @param    string      $url	   string containing url user input
	* @return   boolean     Returns TRUE/FALSE
	*/
	function IsValidURL($url)
	{
		return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url); 
	}
       

}
//Setup VIM: ex: et ts=4 enc=utf-8 :
