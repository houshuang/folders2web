<?php
require_once('ZoteroReader.php');

class Config
{
	const FileZoteroIds = "ZoteroIds.txt";
	const FileZoteroEntries = "ZoteroEntries.txt";
	
	private static $c = null;
	private $config = array();
	
	private function __construct()
	{	
		$this->config = parse_ini_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . "zotero.ini", true);
		
		if (!$this->usernameIsValid())
		{
			throw new Exception("Invalid Zotero username in config file.");
		}

		if ($this->autoupdateIsActivated() && !$this->keyIsValid())
		{
			throw new Exception("Invalid Zotero key in config file.");
		}

		if (!isset($this->config['WikiOutput']['parentheses']) || $this->config['WikiOutput']['parentheses'] == "")
		{
			$this->config['WikiOutput']['parentheses'] = ",";
		}
	}
	
	private function usernameIsValid()
	{
		return isset($this->config['ZoteroAccess']['username']) && $this->config['ZoteroAccess']['username'] != "" && $this->config['ZoteroAccess']['username'] != "YOURUSERNAME";
	}

	private function keyIsValid()
	{
		return isset($this->config['ZoteroAccess']['key']) && $this->config['ZoteroAccess']['key'] != "" && $this->config['ZoteroAccess']['key'] != "YOURZOTEROKEY";
	}

	private function autoupdateIsActivated()
	{
		return isset($this->config['ZoteroAccess']['autoupdate']) && $this->config['ZoteroAccess']['autoupdate'] == true;
	}

	public static function getInstance()
	{
		if (self::$c === null)
		{
			self::$c = new Config();
		}		
		return self::$c;
	}
	
	public function getConfig()
	{
		return $this->config;
	}
	
	public function getCachePage()
	{
		$cachePage = $this->config['SourceEntries']['cachePage'];
		if ($cachePage != "")
		{
			$wikiPagesDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "pages" . DIRECTORY_SEPARATOR;
			if (strstr($cachePage, ":"))
			{
				$parts = explode(":", $cachePage);
				$cachePage = $parts[0] . DIRECTORY_SEPARATOR . $parts[1];
			}
			$cachePage = realpath($wikiPagesDir) . DIRECTORY_SEPARATOR . $cachePage . ".txt";
		}
		else
		{
			$cachePage = self::FileZoteroEntries;
		}
		return $cachePage;
	}
}
?>