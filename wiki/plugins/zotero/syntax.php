<?php
/**
 * Zotero Plugin: Links quotes to Zotero sources
 *
 * Syntax: \cite[Page]{ShortName}
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stefan Macke <me@stefan-macke.de>
 */

if (!defined('DOKU_INC'))
{
	define('DOKU_INC', realpath(dirname(__FILE__) . '/../../') . DIRECTORY_SEPARATOR);
}
if (!defined('DOKU_PLUGIN'))
{
	define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}
require_once(DOKU_PLUGIN . 'syntax.php');

require_once("ZoteroEntry.php");
require_once("ZoteroReader.php");
require_once("ZoteroParser.php");
require_once("Config.php");

class syntax_plugin_zotero extends DokuWiki_Syntax_Plugin
{
	/**
	 * @var ZoteroReader
	 */
	private $zoteroReader = null;
	
	private $config = array();
	
	function getInfo()
	{
		return array(
            'author' => 'Stefan Macke',
            'email'  => 'me@stefan-macke.de',
            'date'   => '2010-06-01',
            'name'   => 'Zotero Plugin',
            'desc'   => 'Links quotes to Zotero sources',
            'url'    => 'http://blog.stefan-macke.de',
		);
	}

	function getType()
	{
		return 'substition';
	}

	function getSort()
	{
		return 50;
	}

	function connectTo($mode)
	{
		$this->Lexer->addSpecialPattern('\\\cite.*?\}', $mode, 'plugin_zotero');
	}

	function handle($match, $state, $pos, &$handler)
	{
		$citeKey = "";
		$pageRef = "";
		$matches = array();
		if (preg_match("/\\\cite(\[([a-zA-Z0-9 \.,\-]*)\])?\{([a-zA-Z0-9\-:]*?)\}/", $match, $matches))
		{
			$pageRef = $matches[2];
			$citeKey = $matches[3];
		}
		else
		{
			return "invalid citation: " . $match;
		}
		
        $output = "";
        try
        {    
			$this->config = Config::getInstance()->getConfig();
	    	$cachePage = Config::getInstance()->getCachePage();
	    	
	    	if ($this->zoteroReader == null)
	    	{
	    		$this->zoteroReader = new ZoteroReader($cachePage);
	    	}
        	$output = $this->createSourceOutput($citeKey, $pageRef);
        }
        catch (Exception $e)
        {
            $output = '<span style="color: red;">ERROR: ' . $e->getMessage() . "</span>"; 
        }
    	return '<span class="ZoteroSource">' . $output . '</span>';
	}

	private function createSourceOutput($citeKey, $pageRef)
	{
    	$parentheses = explode(",", $this->config['WikiOutput']['parentheses']);
    	$titleFormat = isset($this->config['WikiOutput']['titleFormat']) ? $this->config['WikiOutput']['titleFormat'] : "";
    	
        $entry = $this->getZoteroEntry($citeKey);
    
        $output = $parentheses[0] . $this->getZoteroLink($entry);
    	$output = $this->addPageRefToOutput($output, $pageRef);
    	$output .= $parentheses[1];
    	
    	return $output;
	}
	
	private function addPageRefToOutput($output, $pageRef)
	{
    	if ($pageRef != "")
    	{
    		if (preg_match("/^[0-9\-f\.]+$/", $pageRef))
    		{
    			if (isset($this->config['WikiOutput']['pagePrefix']))
    			{
    				$pageRef = $this->config['WikiOutput']['pagePrefix'] . $pageRef;
    			}
    		}
    		$output .= ", " . $pageRef;
    	}
    	return $output;
	}
	
    private function getZoteroEntry($citeKey)
    {
        try
        {
            return $this->zoteroReader->getEntry($citeKey);
        }
        catch (Exception $e)
        {
            if (isset($this->config['ZoteroAccess']['autoupdate']) && $this->config['ZoteroAccess']['autoupdate'] == true)
            {
    		    $cachePage = Config::getInstance()->getCachePage();
                $p = new ZoteroParser($this->config['ZoteroAccess']['username']);
                $p->setZoteroKey($this->config['ZoteroAccess']['key']);
                $p->updateAndSaveZoteroEntries($cachePage);
                $this->zoteroReader->getZoteroEntries(true);
                return $this->zoteroReader->getEntry($citeKey);
            }
            else
            {
                throw new Exception("Zotero source entry with short name <em>" . $citeKey . "</em> not found.");
            }
        }
    }

	private function getZoteroLink(ZoteroEntry $entry, $format = "")
	{
		return '<a href="' . $entry->getLink($this->config['ZoteroAccess']['username']) . '" title="' . htmlentities($entry->getShortInfo($format)) . '">' . htmlentities($entry->getCiteKey()) . "</a>";
	}
	
	function render($mode, &$renderer, $data) 
	{
		if($mode == 'xhtml')
		{
			$renderer->doc .= $data;
			return true;
		}
		return false;
	}
}
?>
