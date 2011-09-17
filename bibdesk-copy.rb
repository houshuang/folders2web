# encoding: UTF-8

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
require 'wiki-lib'
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

pbcopy(out.strip)