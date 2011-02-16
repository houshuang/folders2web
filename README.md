# folders2web

**folders2web**  brings together two scripts that are unified more by how I use them, then by their common functionality.
**convert.rb**  reads a My Clippings.txt file from a Kindle, and imports the clippings into a running [DevonThink][1] database
using appscript. **folders.rb**  takes a directory structure of html files, and creates a static web interface with a two 
or three-pane layout.

Note that I wrote these to scratch my own itch, and play with how I can better share my information with others. The code
is very unpolished, and might easily break. Feel free to play with it, and improve on it, but don't expect anything polished,
well-documented, or supported.

I release this code as CC-0, into the public domain.

[1]: http://www.devon-technologies.com/products/devonthink/

## Installation

convert.rb requires [rb-appscript][2]
  
	sudo gem install rb-appscript
[2]: http://appscript.sourceforge.net/rb-appscript/install.html

## convert.rb - import Kindle snippets to DevonThink

Connect your Kindle with the USB-cable, and locate the file ** My Clippings.txt** . Copy this into the folder of this 
script. Make sure that DevonThink.app is running, and that you have selected the database where you want to import
the snippets. Execute the script

	ruby convert.rb
  
and a new folder called *Kindle* will be created in the database active in DevonThink. One folder will be created for each
document that has clippings, and each clipping will be a separate note within that folder. The title of each note will
be the hundred first characters from the note text. The location and time of the clipping will be inserted into the
note's comment, available through the Info button.

Once you have imported your clippings, you can freely manage them in DevonThink, renaming folders, tagging individual 
clippings, creating saved searches that automatically collect notes based either on tags or free-text searches, etc.

## folders.rb - generate web index from directories of HTML files

I wrote this to provide a way to share the snippets or other notes I organize in DevonThink with others over the web (who might
not have DevonThink, or even be on a Mac). DevonThink enables you to choose a folder or set of folders, and export as website,
however, it only recreates the directory structure, and converts each note to HTML, but does not provide any index or other
overview for browsing the files. This script takes a directory or set of directories with HTML files, and creates that structure.

You can also use the script on a hierarchy of notes exported from [Scrivener][3], or any other program that creates a directory
structure with HTML files.

[3]: http://www.literatureandlatte.com/scrivener.php

### Settings

Near the top of folders.rb are a few settings that need to be modified:

	#######################################################################
	searchpath = "MA/"  
	TITLE = "MA thesis"
	OUT = "/Users/stian/src/kindle/out/"
	layout = 2
	#######################################################################

*searchpath* is the relative path to the directories to be processed, ie. where you exported the DevonThink files to. *TITLE* will
just be displayed on the webpage, OUT is the absolute path of where you want the resulting files to be placed, and *layout* is 
either 2, for two-paned layout, or 3 for three-paned layout.

Note that all links in the generated files are relative, which means that after generation, the entire directory (*out*  
in this case) can be copied to another location, for example a web server, and will work just as well. The files link to 
stylesheets and javascript files hosted on the web - if you require viewing the files offline, download the files, and change
the location in the script.

### Layouts

With **two-paned layout**, the script generates a view with one sidebar, containing a tree of all the files and folders. Clicking
on any file will open it in the right area. With **three-paned layout**, all folders are listed in the left pane, and all
files in the current folder are listed in the top-right pane. Clicking on a file opens the file in the bottom-right pane.

I use [Alloy][4], built on [YUI][5] to generate the interactive menus, however the menu is laid-out as a simple unordered
list, so it should display fine even without the javascript (it might also display like this on initial load, before the 
javascript "catches up").

[4]: http://alloy.liferay.com/
[5]: http://developer.yahoo.com/yui/