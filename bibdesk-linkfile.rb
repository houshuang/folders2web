# encoding: UTF-8
$:.push(File.dirname($0))
require 'rubygems'
require 'appscript'
require 'wiki-lib'
include Appscript

# attaches the last added PDF in the download directory to the currently selected Bibdesk reference

curfile =  File.last_added("#{Downloads_path}/*.pdf")

dt = app('BibDesk')
d = dt.document.selection.get[0]
f = MacTypes::FileURL.path(curfile)
d[0].linked_files.add(f,{:to =>d[0]})
d[0].auto_file

growl("PDF added", "File added successfully to #{d[0].cite_key.get}")