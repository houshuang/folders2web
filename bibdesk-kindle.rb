# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"
require 'rubygems'
require 'appscript'
require curpath + 'mail-lib'
include Appscript

# grabs the name of the currently open Skim file, uses skimnotes to extract notes and highlights to a text file,
# and inserts this as a page, using the filename as a page name, in DokuWiki. intended to be turned into a service.


dt = app('BibDesk')
d = dt.document.selection.get[0]
d.each do |dd|
  docu = dd.cite_key.get
  mail_file("/Volumes/Home/stian/Documents/Bibdesk/#{docu}.pdf")
end
puts "Files sent"