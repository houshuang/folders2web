<?php
interface IZoteroWebsiteReader
{
	function getSourceOfEntriesPage($nr);
    function readEntry($zoteroId);
	function parseId($string);
	function getFeed();
}