# encoding: UTF-8
$:.push(File.dirname($0))
require 'pp'
require 'appscript'
include Appscript

# opens the currently selected Bibdesk file with QuickLook

dt = app('BibDesk')
d = dt.document.selection.get[0]
citekey = d[0].cite_key.get
file =  d[0].linked_files.get[0].to_s
if File.exists?(file)
  `qlmanage -p '#{file}'`
else
  growl('No file available', 'No file available for #{citekey}')
end