<?php
class ZoteroEntry
{
    const EntryLink = "http://www.zotero.org/[USER]/items/[ID]";
	
	private $zoteroId;
    private $citeKey;
    private $title;
    private $author;
    private $date;
    
    public function __construct($zoteroId)
    {
    	$this->zoteroId = $zoteroId;
    }
    
    public function getZoteroId()
    {
    	return $this->zoteroId;
    }
    
    public function setCiteKey($value)
    {
    	$this->citeKey = $value;
    }

    public function getCiteKey()
    {
    	return $this->citeKey;
    }

    public function setTitle($value)
    {
    	$this->title = $value;
    }

    public function getTitle()
    {
    	return $this->title;
    }

    public function setAuthor($value)
    {
    	$this->author = $value;
    }

    public function getAuthor()
    {
    	return $this->author;
    }

    public function setDate($value)
    {
    	$this->date = $value;
    }

    public function getDate()
    {
    	return $this->date;
    }
  
    public function getLink($username)
    {
    	$link = str_replace("[USER]", $username, self::EntryLink);
    	$link = str_replace("[ID]", $this->getZoteroId(), $link);
    	return $link;
    }
    
    public function getShortInfo($format = "")
    {
    	if ($format == "")
    	{
    		return $this->getAuthor() . ": " . $this->getTitle() . " (" . $this->getDate() . ")";
    	}
    	else
    	{
    		$title = str_replace("AUTHOR", $this->getAuthor(), $format);
    		$title = str_replace("TITLE", $this->getTitle(), $title);
    		$title = str_replace("DATE", $this->getDate(), $title);
    		return $title;
    	}
    }
    
    public function __toString()
    {
    	return $this->getShortInfo();
    }
}
?>