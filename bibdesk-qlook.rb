# encoding: UTF-8
require 'pp'
require 'appscript'
include Appscript

dt = app('BibDesk')
d = dt.document.selection.get[0]
citekey = d[0].cite_key.get
file =  d[0].linked_files.get[0].to_s
if File.exists?(file)
  `qlmanage -p '#{file}'`
else
  `/usr/local/bin/growlnotify -t 'No file available' -m 'No file available for #{citekey}'`
end