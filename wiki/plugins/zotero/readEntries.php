#!/usr/bin/php5
<?php
require_once("ZoteroReader.php");
require_once("ZoteroParser.php");

// downloading and parsing the Zotero website could take quite some time
set_time_limit(600);

try
{
	$config = Config::getInstance()->getConfig();
	$cachePage = Config::getInstance()->getCachePage();
	$username = $config['ZoteroAccess']['username'];

	if (!isset($config['ZoteroAccess']['key']) || $config['ZoteroAccess']['key'] == "")
	{
		throw new Exception("Zotero key not set in zotero.ini.");
	}
	$key = $config['ZoteroAccess']['key'];
}
catch (Exception $e)
{
	echo $e->getMessage() . "\n";
	exit;
}

echo "Parsing " . $username . "'s Zotero website for source entries.\n";

try
{
	$p = new ZoteroParser($username);
	$p->setZoteroKey($key);
	
	$ids = $p->readZoteroIdsFromWebsite();
	$p->saveIdsToFile($ids);
	
	$r = new ZoteroReader($cachePage);
	$updateIds = $r->getZoteroIDsWithMissingEntries();
	$p->readAndSaveEntries($updateIds, $cachePage);
}
catch (Exception $e)
{
	echo $e->getMessage() . "\n";
}
?>
