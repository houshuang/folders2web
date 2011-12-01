<?php
require_once('IZoteroWebsiteReader.php');
class ZoteroWebsiteReader implements IZoteroWebsiteReader
{
	const URLEntriesPage = "http://www.zotero.org/[USER]/items?itemPage=[PAGENR]";
	const URLUserAPI = "http://www.zotero.org/api/users/[USER]";
	const URLItemAPI = "https://api.zotero.org/users/[USERID]/items/[ITEMID]";
	
	private $username = "";
	private $userid = 0;
	
	public function __construct($username)
	{
		$this->username = $username;
	}
	
	public function getSourceOfEntriesPage($nr)
    {
        //TODO: if password is set, use authentication to retrieve website
    	$subst = array("PAGENR" => $nr);
    	$url = $this->substitutePlaceholders(self::URLEntriesPage, $subst);
   		return $this->sendHTTPRequest($url);
    }
	
    public function readEntry($zoteroId)
    {
    	$subst = array("ITEMID" => $zoteroId);
    	$url = $this->substitutePlaceholders(self::URLItemAPI, $subst);
    	$url = $this->addZoteroKeyToUrl($url);
    	
   		$xml = $this->sendHTTPRequest($url);
    	if (!$xml)
    	{
    		throw new Exception("Invalid XML returned for item " . $zoteroId);
    	}
    	return $xml;
    }

    private function addZoteroKeyToUrl($url)
    {
       	$config = Config::getInstance()->getConfig();
    	if (isset($config['ZoteroAccess']['key']) && $config['ZoteroAccess']['key'] != "")
    	{
    		$url .= "?key=" . urlencode($config['ZoteroAccess']['key']);
    	}
    	return $url;
    }
    
    public function getFeed()
    {
    	$userId = $this->getUserId();
		$subst = array("ITEMID" => "");
    	$url = $this->substitutePlaceholders(self::URLItemAPI, $subst);
    	$url = $this->addZoteroKeyToUrl($url);
        $xml = $this->sendHTTPRequest($url);
    	if (!$xml)
    	{
    		throw new Exception("Invalid XML returned for items feed from " . $url);
    	}
    	return $xml;
    }
    
    private function substitutePlaceholders($text, $subst = array())
    {
    	$subst['USER'] = $this->username;
        if (strstr($text, "[USERID]"))
        {
        	$subst['USERID'] = $this->getUserId();
        }
    	foreach ($subst as $k=>$v)
        {
        	$placeholder = "[" . strtoupper($k) . "]";
        	$text = str_replace($placeholder, $v, $text);
        }
        return $text;
    }
    
    private function getUserId()
    {
    	if (0 == $this->userid)
    	{
    		$config = Config::getInstance()->getConfig();
    		if (isset($config['ZoteroAccess']['userid']) && $config['ZoteroAccess']['userid'] != "" && $config['ZoteroAccess']['userid'] != 0 && $config['ZoteroAccess']['userid'] != "YOURUSERID")
    		{
    			$this->userid = $config['ZoteroAccess']['userid'];
    		}
    		else
    		{
    			$this->userid = $this->readUserId();
    		}
    	}
    	return $this->userid;
    }

    private function readUserId()
    {
		$url = $this->substitutePlaceholders(self::URLUserAPI);
        $xml = $this->sendHTTPRequest($url);
    
        if ($xml) 
        {
            $response = new DOMDocument();
            $response->loadXML($xml);

            return $this->parseId($response->getElementsByTagName('id')->item(0)->nodeValue);
        }
        else
        {
        	throw new Exception("User ID could not be read from " . $url);
        } 
    }

    public function parseId($string)
    {
        $matches = array();
        $pattern = "/.*\/([0-9]+)/";
        if (!preg_match($pattern, $string, $matches))
        {
            throw new Exception("No ID found in string " . $string);
        }
        $id = $matches[1];
        if ($id == 0)
        {
            throw new Exception("No ID found in string " . $string);
		}
		return $id;
    }
    
	private function sendHTTPRequest($url)
	{
	    if (!function_exists('curl_init'))
	    {
	    	throw new Exception("CURL functions are not available.");
	    }
		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
	    $xml = curl_exec($ch);
	    curl_close($ch);
	    return $xml;
	}
    
}
?>