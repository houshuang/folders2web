<?php
require_once("IZoteroReader.php");
require_once("ZoteroEntry.php");
require_once("Config.php");

class ZoteroReader implements IZoteroReader
{
    private $fileZoteroIds = "";
	private $fileZoteroEntries = "";
    private $zoteroIds = null;
    private $zoteroEntries = null;
	
    public function __construct($fileZoteroEntries = "", $fileZoteroIds = "")
    {
    	$this->setFileZoteroEntries($fileZoteroEntries);
    	$this->fileZoteroIds = $fileZoteroIds;
    	if ($this->fileZoteroIds == "")
    	{
    		$this->fileZoteroIds = Config::FileZoteroIds;
    	}

    	if (!file_exists($this->fileZoteroEntries))
        {
        	$from = dirname(__FILE__) . DIRECTORY_SEPARATOR . Config::FileZoteroEntries;
        	$to = $this->fileZoteroEntries;
        	@copy($from, $to);
        	if (!file_exists($this->fileZoteroEntries))
        	{
        		throw new Exception("File " . $this->fileZoteroEntries . " could not be created. Please create it manually.");
        	}
        }
    }
    
    public function setFileZoteroEntries($filename = "")
    {
    	$this->fileZoteroEntries = $filename;
    	if ("" == $this->fileZoteroEntries)
    	{
    		$this->fileZoteroEntries = Config::FileZoteroEntries;
    	}
    }
    
	private function readZoteroIds()
	{
		if (!file_exists($this->fileZoteroIds))
        {
        	$this->zoteroIds = array();
        }
        else
        {
			$lines = explode("\n", file_get_contents($this->fileZoteroIds));
			$this->zoteroIds = array();
			foreach ($lines as $l)
			{
				if (trim($l) != "")
				{
					$this->zoteroIds[] = trim($l);
				}
			}
        }
        return $this->zoteroIds;
	}
    
	public function getZoteroIds($omitCache = false)
	{
        if ($omitCache || null === $this->zoteroIds)
        {
            $this->readZoteroIds();
        }
        return $this->zoteroIds;
    }

    private function readZoteroEntries()
    {
        $citeKeys = array();
		$this->zoteroEntries = array();
        $lines = explode("\n", utf8_decode(file_get_contents($this->fileZoteroEntries)));
        foreach ($lines as $line)
        {
            $l = explode("|", $line);
            if (count($l) == 7 && $l[1] != "" && substr($l[1], 0, 2) != "**")
            {
	            $e = new ZoteroEntry(trim($l[1]));
	            $citeKey = trim($l[2]);
	            if ($citeKey != "" && in_array($citeKey, $citeKeys))
	            {
					echo 'WARNING: Duplicate cite key: ' . $citeKey . "\n";
				}
				else
				{
					$citeKeys[] = $citeKey;
				}
				$e->setCiteKey($citeKey);
	            $e->setTitle(trim($l[3]));
	            $e->setAuthor(trim($l[4]));
	            $e->setDate(trim($l[5]));
	            $this->zoteroEntries[] = $e;
            }
        }
        return $this->zoteroEntries;
    }
    
    public function getZoteroEntries($omitCache = false)
	{
        if ($omitCache || null === $this->zoteroEntries)
        {
            $this->readZoteroEntries();
        }
        return $this->zoteroEntries;
    }
	
    public function getZoteroIDsWithMissingEntries()
    {
    	$ids = $this->getZoteroIds();
    	$entries = $this->getZoteroEntries();
    	
    	foreach ($entries as $e)
    	{
    		$id = $e->getZoteroId();
    		$index = array_search($id, $ids);
    		if ($index !== false)
    		{
    			unset($ids[$index]);
    		}
    		else
    		{
    			echo "Zotero ID not found for existing entry " . $id . ": " . $e->getCiteKey() . "\n";
    		}
    	}
    	
		echo count($ids) . " entries for IDs missing.\n";
    	return $ids;
    }
    
    public function getEntry($citeKey)
    {
    	$entries = $this->getZoteroEntries();
    	foreach ($entries as $e)
    	{
    		if ($e->getCiteKey() == $citeKey)
    		{
    			return $e;
    		}
    	}
    	throw new Exception("Entry with cite key <em>" . $citeKey . "</em> not found in file <tt>" . $this->fileZoteroEntries . "</tt>.");
    }	
}
?>
