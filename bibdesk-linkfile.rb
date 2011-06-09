# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"
require 'pp'
require 'rubygems'
require 'appscript'
require curpath + 'wiki-lib'
include Appscript

# grabs the name of the currently open BibDesk file, and puts on clipboard formatted as a DokuWiki reference

curfile =  Dir["/Volumes/Home/stian/Downloads/*.pdf"].select {|f| test ?f, f}.sort_by {|f|  File.mtime f}.pop 

dt = app('BibDesk')
d = dt.document.selection.get[0]
f = MacTypes::FileURL.path(curfile)
d[0].linked_files.add(f,{:to =>d[0]})
d[0].auto_file
`/usr/local/bin/growlnotify -t "PDF added" -m "File added successfully to #{d[0].cite_key.get}"`