# encoding: UTF-8
$:.push(File.dirname($0))
require 'wiki-lib'
require 'appscript'
include Appscript

# opens selected citations from BibDesk in Chrome, creating metadata pages if they don't already exist

dt = app('BibDesk')
d = dt.document.selection.get[0]
dt.document.save
ary = Array.new
d.each do |dd|
  docu = dd.cite_key.get
  ary << docu unless File.exists?("#{Wikipages_path}/ref/#{docu}.txt")
  ensure_refpage(docu)
end
`open http://localhost/wiki/ref:#{d[0].cite_key.get}`
