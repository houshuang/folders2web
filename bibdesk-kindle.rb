# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"
require 'rubygems'
require 'appscript'
include Appscript
require curpath + 'mail-lib'

# grabs the name of the currently open Skim file, uses skimnotes to extract notes and highlights to a text file,
# and inserts this as a page, using the filename as a page name, in DokuWiki. intended to be turned into a service.


dt = app('BibDesk')
d = dt.document.selection.get[0]
d.each do |dd|
  docu = dd.cite_key.get
  title = dd.title.get.gsub(/[\{|\}]/,"")
  authors = dd.author.name.get.join(", ")
  `/usr/local/bin/pdftotext "/Volumes/Home/stian/Documents/Bibdesk/#{docu}.pdf" /tmp/#{docu}.txt`
  `ebook-convert /tmp/#{docu}.txt /tmp/#{docu}.mobi`
  `ebook-meta /tmp/#{docu}.mobi -t "#{title} [#{docu}]" -a "#{authors}" --category="Bibdesk"`
  `cp /tmp/#{docu}.mobi /Volumes/Kindle/documents`
#  mail_file("/tmp/[#{docu}].pdf")
end
`/usr/local/bin/growlnotify -m "#{d.size} file(s) sent"`
