# encoding: UTF-8
require 'pp'
require 'appscript'
include Appscript

# grabs the name of the currently open BibDesk file, and puts on clipboard formatted as a DokuWiki reference
dt = app('BibDesk')
d = dt.document.selection.get[0]
f = MacTypes::FileURL.path('/Volumes/Home/stian//src/folders2web/pdftmp.pdf')
`killall -9 qlmanage`
d[0].linked_files.add(f,{:to =>d[0]})
d[0].auto_file