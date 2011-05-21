# encoding: UTF-8
require 'pp'
require 'appscript'
include Appscript

dt = app('BibDesk')
d = dt.document.selection.get[0]
citekey = d[0].cite_key.get
file = "/Volumes/Home/stian/Documents/Bibdesk/"+ citekey + ".pdf"
if File.exists?(file)
  `qlmanage -p '#{file}'`
else
  `/usr/local/bin/growlnotify -t 'No file available' -m 'No file available for #{citekey}'`
end