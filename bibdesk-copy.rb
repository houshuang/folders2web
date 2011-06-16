# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"
require 'pp'
require 'rubygems'
require 'appscript'
require curpath + 'wiki-lib'
include Appscript

# grabs the name of the currently open BibDesk file, and puts on clipboard formatted as a DokuWiki reference

dt = app('BibDesk')
d = dt.document.selection.get[0]
out = ''
d.each do |dd|
  docu = dd.cite_key.get
  docu.strip!
  out << "[@#{docu}] "
end
IO.popen("pbcopy","w+") {|pipe| pipe << out.strip}
