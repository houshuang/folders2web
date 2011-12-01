<?php
interface IZoteroReader
{
	function getZoteroIds($omitCache = false);
	function setFileZoteroEntries($filename = "");
	function getZoteroEntries($omitCache = false);
}
?>
