<?php
require_once("ZoteroEntry.php");
require_once("ZoteroReader.php");
require_once("IZoteroWebsiteReader.php");
require_once("ZoteroWebsiteReader.php");
require_once("Config.php");

class ZoteroParser
{
	const FileZoteroEntriesHeader = "|**Zotero ID**|**Short Name**|**Title**|**Author**|**Date**|\n";
	
	private $zoteroUser = "";
    private $zoteroPassword = null;
    private $zoteroKey = null;
    private $websiteReader = null;
    private $fileZoteroEntries = "";
    private $zoteroReader = null;
    
	public function __construct($user, IZoteroWebsiteReader $websiteReader = null, IZoteroReader $zoteroReader = null)
    {
    	$this->zoteroUser = $user;
    	if (null == $websiteReader)
    	{
    		$websiteReader = new ZoteroWebsiteReader($this->zoteroUser);
    	}
    	$this->websiteReader = $websiteReader;
    	if (null == $zoteroReader)
    	{
    		$zoteroReader = new ZoteroReader();
    	}
    	$this->zoteroReader = $zoteroReader;
    	
    	$this->fileZoteroEntries = Config::getInstance()->getCachePage();
    }

    public function setZoteroPassword($password)
    {
    	$this->zoteroPassword = $password;
    }

    public function setZoteroKey($key)
    {
    	$this->zoteroKey = $key;
    }
    
    public function readZoteroIdsFromWebsite()
	{
        $lastPage = $this->getLastEntriesPage();

        $ids = array();
        
   	    for ($p = 1; $p <= $lastPage; $p++)
   	    {
   	        echo "Parsing page " . $p . "/" . $lastPage . "\n";
   	    	$html = preg_replace("/\n|\r/", "", $this->websiteReader->getSourceOfEntriesPage($p));
   	        $matches = array();
   	        $pattern = '/<table id="field-table">(.*)<\/table>/';
            if (!preg_match($pattern, $html, $matches))
            {
                throw new Exception("Entries could not be parsed from entries page " . $p . ":\n" . htmlentities($html));
            }
   	        $table = $matches[0];
   	        $dom = new DomDocument();
   	        $dom->loadXml($table);
   	
   	        $xpath = new DomXPath($dom);
   	        $rows = $xpath->query('tbody/tr');
   	    
   	        foreach ($rows as $row)
   	        {
   	            $id = $xpath->query("@id", $row)->item(0)->nodeValue;
   	            $ids[] = $id;
   	        }
   	    }

   	    echo count($ids) . " source entry IDs read from Zotero website.\n";
   	    return $ids;
	}

    public function getLastEntriesPage()
    {
    	$html = $this->websiteReader->getSourceOfEntriesPage(1);
    	
    	$matches = array();
        $pattern = '/<a href="\?itemPage=([0-9]*)" class="paginator-last">/';
        if (!preg_match($pattern, $html, $matches))
        {
            throw new Exception("Number of last entries page could not be parsed.");
        }
         
        return $matches[1];
    }
	
	public function saveIdsToFile($newIds, $filename = "")
	{
		if (count($newIds) == 0)
		{
			return;
		}
		
		if ("" == $filename)
		{
			$filename = Config::FileZoteroIds;
		}
		
		$knownIds = $this->zoteroReader->getZoteroIds(true);
		
		$ids = array();
		foreach ($knownIds as $i)
		{
			$ids[$i] = $i;
		}
		
		foreach ($newIds as $i)
		{
			$ids[$i] = $i;
		}

		file_put_contents($filename, $this->serializeIds($ids));
		echo count($newIds) . " entry IDs saved to file " . $filename . "\n";
	}
	
    public function readEntries(array $zoteroIds)
    {
    	$entries = array();
    	foreach ($zoteroIds as $id)
    	{
    		$xml = $this->websiteReader->readEntry($id);
    		$entry = $this->parseEntry($xml);
    		$entries[] = $entry; 
    	}
    	return $entries;
    }

    public function readAndSaveEntries(array $zoteroIds, $filename = "")
    {
		if ($filename == "")
		{
			$filename = $this->fileZoteroEntries;
		}
    	echo "Saving Zotero entries to file " . $filename . "\n";
		
		$entries = array();
    	$counter = 0;
    	foreach ($zoteroIds as $id)
    	{
    		echo "Loading entry " . $id . " (" . ++$counter . "/" . count($zoteroIds) . ")\n";
    		$xml = $this->websiteReader->readEntry($id);
    		$entry = $this->parseEntry($xml);
    		echo "  -> " . $entry . "\n";
    		$this->saveEntriesToFile(array($entry), $filename);
    	}
    }
    
	public function saveEntriesToFile($newEntries, $filename)
	{
        if (count($newEntries) == 0)
        {
            return;
        }

		$this->zoteroReader->setFileZoteroEntries($filename);
		$knownEntries = $this->zoteroReader->getZoteroEntries(true);

		$entries = array();
		if (count($knownEntries) > 0)
		{
			foreach ($knownEntries as $e)
			{
				$entries[$e->getZoteroId()] = $e;
			}
		}
		
		foreach ($newEntries as $e)
		{
			$entries[$e->getZoteroId()] = $e;
		}
		
		$content = self::FileZoteroEntriesHeader;
		$content .= $this->serializeEntries($entries);
		file_put_contents($filename, $content);
	}

	public function serializeIds(array $ids)
	{
		$str = "";
		foreach ($ids as $i)
		{
			$str .= $i . "\n";
		}
		return $str;
	}
	
	public function serializeEntries(array $entries)
	{
		$str = "";
		foreach ($entries as $e)
		{
			$str .= "|" .
				$e->getZoteroId() . "|" .
				$e->getCiteKey() . ($e->getCiteKey() == "" ? " " : "") . "|" .
				$e->getTitle() . "|" .
				$e->getAuthor() . "|" .
				$e->getDate() . ($e->getDate() == "" ? " " : "") . "|\n";
		}
		return $str;
	}
    
    private function parseEntry($xml)
    {
    	$dom = new DomDocument();
        $dom->loadXml($xml);
        return $this->createEntryFromDOM($dom);
    }

    private function createEntryFromDOM(DomDocument $dom, $contextNode = null)
    {
		$xpath = new DomXPath($dom);
    	$xpath->registerNameSpace('f', 'http://www.w3.org/2005/Atom');
	    $xpath->registerNameSpace('h', 'http://www.w3.org/1999/xhtml');
	    $xpath->registerNameSpace('z', 'http://zotero.org/ns/api');

        if (null === $contextNode)
        {
            $contextNode = $dom->documentElement;
        }

        $zoteroId = $this->websiteReader->parseId($xpath->query("./f:id", $contextNode)->item(0)->nodeValue);
	    $title = html_entity_decode($xpath->query("./f:title", $contextNode)->item(0)->nodeValue);
     	
	    $shortTitle = $creator = $date = "";
	    
	    foreach (array('shortTitle', 'creator', 'date') as $f)
        {
            $r = $xpath->query('./f:content/h:div/h:table/h:tr[@class="' . $f . '"]/h:td', $contextNode);
            if ($r->length > 0)
            {
                $$f = html_entity_decode($r->item(0)->nodeValue);
            }
        }
        
    	$e = new ZoteroEntry($zoteroId);
    	$e->setTitle($title);
    	$e->setCiteKey($shortTitle);
    	$e->setAuthor($creator);
    	$e->setDate($date);
    	return $e;
    }
    
    public function updateAndSaveZoteroEntries($filename)
    {
    	$xml = $this->websiteReader->getFeed();
    	$dom = new DomDocument();
    	$dom->loadXml($xml);
    	
		$xpath = new DomXPath($dom);
    	$xpath->registerNameSpace('f', 'http://www.w3.org/2005/Atom');
	    $xpath->registerNameSpace('h', 'http://www.w3.org/1999/xhtml');
	    $xpath->registerNameSpace('z', 'http://zotero.org/ns/api');

        $entries = array();
        $r = $xpath->query('//f:feed/f:entry');
        foreach ($r as $entry)
        {
            $e = $this->createEntryFromDOM($dom, $entry);
            if ($e->getZoteroId() != "" && $e->getAuthor() != "" && $e->getTitle() != "")
            {
                $entries[$e->getZoteroId()] = $e;
            }
        }
        $this->saveEntriesToFile($entries, $filename);
    }
}
?>
