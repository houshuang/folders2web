<?php

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_404manager extends DokuWiki_Action_Plugin {
  
	var $Vc_Is404Fired = 'N';
	var $Vc_Message 	= '';
	var $Vc_TypeMessage = 'Classic';
	var $Vc_OldId = '';
	
	function action_plugin_404manager () {
		$this->Vc_Is404Fired = 'N';
		// enable direct access to language strings
		$this->setupLocale();
	}
	
	function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
  	}

  	function register(&$controller){
        $controller->register_hook('ACTION_ACT_PREPROCESS',
                                   'AFTER',
                                   $this,
                                   '_404managerProcess',
                                   array());
        $controller->register_hook(
                'TPL_ACT_RENDER',
                'BEFORE',
                $this,
                '_404managerMessage',
                array()
                );
  	}
  	 
    function _404managerProcess(&$e, $param){
    
	  	global $ID;
	  	global $QUERY;
	  	global $conf;
	  	global $lang;
	    
	  	$INFO = $this->getInfo();    
		
		$Vl_PageName = noNS($ID);
		$Vl_PageNameSpace = getNS($ID);

		// If the page does not exist and the action is show
		if ( !page_exists($ID) && $e->data == 'show' ) {
			
			$this->Vc_Is404Fired = 'Y';
			
			// Redirect if a redirection is set
			require_once(dirname(__FILE__).'/admin.php');
			$RedirectManager = new admin_plugin_404manager();
			if ($RedirectManager->IsRedirectionPresent($ID)) {
				
				$TargetPage = $RedirectManager->GetTargetPage($ID);
				
				// The Target page can be moved
				if (page_exists($TargetPage)) {
					$RedirectManager->SetRedirectionActionData($ID);
					if ( $RedirectManager->IsValidURL($TargetPage) ) {
						header('Location: '.$TargetPage);
						exit;
					} else {
						if ( $RedirectManager->GetIsValidate($ID) == 'N') {
							$this->Vc_Message = $this->lang['message_redirected_by_redirect'];
							$this->Vc_TypeMessage = 'Warning';
						}
						$this->RedirectToId($TargetPage);
					}
					return;
				}
			}
			
			//Search same page name
			require_once(DOKU_INC.'inc/fulltext.php');
			$Vl_SamePageName = array();
			$Vl_SamePageNamesId = ft_pageLookup($Vl_PageName);
			
			//If the user have right to edit
			if($_SERVER['REMOTE_USER']) {
		    	$perm = auth_quickaclcheck($ID);
		    } else {
		    	$perm = auth_aclcheck($ID,'',null);
		    }
		    
		    // Action for a writer user
		    if ( $perm >= AUTH_EDIT && $this->getConf('GoToEditMode') == 1 ) {
		    	
		    	$e->data = 'edit';
		    	
		    	if ( $this->getConf('ShowMessageClassic') == 1 ) {
		    		$this->Vc_Message = $this->lang['message_redirected_to_edit_mode'];
		    		$this->Vc_TypeMessage = 'Classic';
		    	}
		    	
		    	// If Param show page name unique and it's not a start page
		    	if ( $this->getConf('ShowPageNameIsNotUnique') == 1 && $Vl_PageName <> $conf['start'] ) {
			    	if( count($Vl_SamePageNamesId)>0 ) {
			    		$this->Vc_TypeMessage = 'Warning';
			    		if ( $this->Vc_Message <> '' ) {
			    			$this->Vc_Message = $this->Vc_Message.'<br/><br/>';
			    		}
			    		$this->Vc_Message = $this->Vc_Message.$this->lang['message_pagename_exist_one'];
				    	$this->Vc_Message = $this->Vc_Message.'<ul>';
			    		foreach($Vl_SamePageNamesId as $PageId){
					    	if ($conf['useheading']) {
				        		$title = p_get_first_heading($PageId);	
				        	}
			      			if(!$title) $title = $PageId;
				    	    $this->Vc_Message = $this->Vc_Message.'<li class="level1"><div class="li"><a href="'.wl($PageId).'" rel="nofollow" class="wikilink1" title="'.wl($PageId).'">'.$title.'</a></div></li>';
					 	}
					 	$this->Vc_Message = $this->Vc_Message.'</ul>';
					 	$this->Vc_Message = $this->Vc_Message.$this->lang['message_pagename_exist_two'].curNS($ID).'_'.$Vl_PageName.' ).';
			    	}	
		    	}
				return;
		    }
	    
		    // User not allowed to edit the page (public of other)
		    if ( $perm < AUTH_EDIT && $this->getConf('ActionReaderFirst') <> 'Nothing' ) {
		    	
		    	$Vl_ActionToPerform = array();
		    	$Vl_ActionToPerform[0] = $this->getConf('ActionReaderFirst');
		    	$Vl_ActionToPerform[1] = $this->getConf('ActionReaderSecond');
		    	$Vl_ActionToPerform[2] = $this->getConf('ActionReaderThird');
		    	
		    	$i = 0;
		    	while ( isset($Vl_ActionToPerform[$i]) ) {
		    		
		    		switch ($Vl_ActionToPerform[$i]) {
					    
		    			case 'Nothing':
					        	return;
					        break;
					        
					    case 'GoToNsStartPage':
					        
					    	$Vl_IdStartPage = getNS($ID).':'.$conf['start'];
					        if ( page_exists($Vl_IdStartPage) ) {
	    						$this->Vc_Message = $this->lang['message_redirected_to_startpage'];
					    		$this->Vc_TypeMessage = 'Warning';
					    		$this->RedirectToId($Vl_IdStartPage);
						    	return;
					        }
					        $Vl_IdStartPage = getNS($ID).':'.curNS($ID);
		    				if ( page_exists($Vl_IdStartPage) ) {
					        	$this->Vc_Message = $this->lang['message_redirected_to_startpage'];
						    	$this->Vc_TypeMessage = 'Warning';
						    	$this->RedirectToId($Vl_IdStartPage);
						    	return;
					        }
					        break;
					        
					    case 'GoToBestPageName':
					    	
					    	$Vl_ScorePageName = 0;
					    	$Vl_BestPageId = '';
					    	
					    	//Get Score from a page
					        if( count($Vl_SamePageNamesId) > 0 ) {
					        	
					        	// Search same namespace in the page found than in the Id page asked.
					        	$Vl_BestNbWordFound = 0;
					        	$Vl_BestPageId = '';
					        	$Vl_IdToExplode = str_replace('_',':',$ID);
					        	$Vl_WordsInId = explode(':',$Vl_IdToExplode );
								foreach  ( $Vl_SamePageNamesId as $Vl_PageId ) {
									$Vl_NbWordFound = 0;
							 		foreach($Vl_WordsInId as $Vl_Word){
							 			$Vl_NbWordFound = $Vl_NbWordFound + substr_count($Vl_PageId, $Vl_Word);
							 		}
							 		
							 		if ( $Vl_NbWordFound >= $Vl_BestNbWordFound ) {
							 			$Vl_BestNbWordFound = $Vl_NbWordFound;
							 			$Vl_BestPageId = $Vl_PageId;
							 		}
					        	}
					        	$Vl_ScorePageName = $this->getConf('WeightFactorForSamePageName') + $Vl_BestNbWordFound*$this->getConf('WeightFactorForSameNamespace');
							}
							
//							Get Score from a Namespace
							$Vl_ScoreNamespace = 0;
				        	$Vl_BestNamespaceId = '';
				        	list($Vl_BestNamespaceId, $Vl_ScoreNamespace) = explode(" ",$this->getBestNamespace($ID));
				        	
//				        	Compare the two score
				        	if ( $Vl_ScorePageName > 0 or $Vl_ScoreNamespace > 0 ) {
				        		if ( $Vl_ScorePageName > $Vl_ScoreNamespace ) {
				        			$this->RedirectToId($Vl_BestPageId);	
				        		} else {
				        			$this->RedirectToId($Vl_BestNamespaceId);	
				        		}
				        		$this->Vc_Message = $this->lang['message_redirected_to_bestpagename'];
					    		$this->Vc_TypeMessage = 'Warning';
						    	return;
				        	}
							break;
						
						case 'GoToBestNamespace':
							
							$Vl_Score = 0;
				        	$Vl_BestNamespaceId = '';
				        	list($Vl_BestNamespaceId, $Vl_Score) = explode(" ",$this->getBestNamespace($ID));
				        	
				        	if ( $Vl_Score > 0 ) {
    							$this->Vc_Message = $this->lang['message_redirected_to_bestnamespace'];
				    			$this->Vc_TypeMessage = 'Warning';
				    			$this->RedirectToId($Vl_BestNamespaceId);
					    		return;	 
				        	}
				        	break;
						
						case 'GoToSearchEngine':
						    //do fulltext search
							$this->Vc_Message = $this->lang['message_redirected_to_searchengine'];
				    		$this->Vc_TypeMessage = 'Warning';
				    		$QUERY = str_replace(':','_',$ID);
						    $e->data = 'search';
					        break;
					       
					// End Switch Action    
					}
					
					$i++;
				// End While Action
		    	}
		    // End if not connected
		    }
		// End if page exist
		}
	//End Fonction
    }
	
	/**
     * Main function; dispatches the visual comment actions
     */
    function _404managerMessage(&$event, $param) {
        
       global $ID;
    	
		if ( $this->Vc_Is404Fired == 'Y' && $this->Vc_Message <> '' ) {
			$INFO = $this->getInfo();
			if ($this->Vc_TypeMessage == 'Classic') {
				ptln('<div class="noteclassic">');
			} else {
				ptln('<div class="notewarning">');
			}
			If ( $this->OldId <> '' ) {
				$Vl_Message = str_replace('$ID',$this->OldId,$this->Vc_Message);	
			} else {
				$Vl_Message = str_replace('$ID',$ID,$this->Vc_Message);
			}
			
			print $Vl_Message;
			print '<div class="managerreference">'.$this->lang['message_come_from'].' <a href="'.$INFO['url'].'" class="urlextern" title="'.$INFO['desc'].'"  rel="nofollow">'.$INFO['name'].'</a>.</div>' ;
		    ptln('</div>');	
		}        
    }
    
  /**
     * Handle the Redirection to an id or the search engine
     */
    function RedirectToId($Vl_Id) {
    	
    	global $ID;
    	
    	//If the user have right to see the page
		if($_SERVER['REMOTE_USER']) {
		   	$perm = auth_quickaclcheck($Vl_Id);
		} else {
		   	$perm = auth_aclcheck($Vl_Id,'',null);
		}
		require_once(dirname(__FILE__).'/admin.php');
		$RedirectManager = new admin_plugin_404manager();
		$RedirectManager->SetRedirection($ID,$Vl_Id);	
		if ( $perm > AUTH_NONE) {
			$this->OldId = $ID;
			$ID = $Vl_Id;
		}
    	
    }
  	
    /**
     * getBestNamespace
     * Return a list with 'BestNamespaceId Score' 
     */
	function getBestNamespace($Vl_ID){
		
 		global $conf;
			  	
		$Vl_ListNamespaceId = array();
		$Vl_ListNamespaceId = ft_pageLookup($conf['start']);
		
		$Vl_BestNbWordFound = 0;
        $Vl_BestNamespaceId = '';
        
        $Vl_IdToExplode = str_replace('_',':',$Vl_ID);
        $Vl_WordsInId = explode(':',$Vl_IdToExplode );
        
		foreach  ( $Vl_ListNamespaceId as $Vl_NamespaceId ) {
			$Vl_NbWordFound = 0;
	 		foreach($Vl_WordsInId as $Vl_Word){
	 			if (strlen($Vl_Word)>2) {
	 				$Vl_NbWordFound = $Vl_NbWordFound + substr_count($Vl_NamespaceId, $Vl_Word);
	 			}
	 		}
	 		if ( $Vl_NbWordFound > $Vl_BestNbWordFound ) {
	 			//Take only the smallest namespace				 			
	 			if ( strlen($Vl_NamespaceId) < strlen($Vl_BestNamespaceId) or $Vl_NbWordFound > $Vl_BestNbWordFound ) {
	 				$Vl_BestNbWordFound = $Vl_NbWordFound;
	 				$Vl_BestNamespaceId = $Vl_NamespaceId;
	 			}
	 		}
		}
		$Vl_WfForStartPage = $this->getConf('WeightFactorForStartPage');
		$Vl_WfForSameNamespace = $this->getConf('WeightFactorForSameNamespace');
		if ( $Vl_BestNbWordFound > 0 ) {
			$Vl_Score = $Vl_BestNbWordFound*$Vl_WfForSameNamespace + $Vl_WfForStartPage;	
		} else {
			$Vl_Score = 0;
		}
		return $Vl_BestNamespaceId." ".$Vl_Score;
  	}
    
}
