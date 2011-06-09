# encoding: UTF-8
require 'rubygems'
require 'bibtex'
require 'citeproc'
require 'pp'
require 'appscript'
include Appscript

dt = app('BibDesk')
dt.document.save
# batch processes entire bibliography file and generates ref:bibliography in wiki, used for refnotes database

def nice(name)
  return "#{name.first} #{name.last}".gsub(/[\{\}]/,"")
end

b = BibTeX.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
b.parse_names

out = "h1. Bibliography\n\n<html><table>"
out1 = ''
out2 = ''
authors = Hash.new
b.each do |item|

  item.author.each do |a|
    authors[nice(a)] = Array.new unless authors[nice(a)]
    authors[nice(a)] << item.key
  end

  cit = CiteProc.process item.to_citeproc, :style => :apa
  if File.exists?("/wiki/data/pages/ref/#{item.key}.txt")
    out1 << "<tr><td><a href = '/wiki/ref:#{item.key}'>#{item.key}</a></td><td>#{cit}</td></tr>\n"
  else
    out2 << "<tr><td>#{item.key}</td><td>#{cit}</td></tr>\n"
  end
  
  # mark as read if notes exist
  # if File.exists?("/wiki/data/pages/clip/#{item.key}.txt") || File.exists?("/wiki/data/pages/kindle/#{item.key}.txt")
  #   dt.document.search({:for =>item.key.to_s})[0].fields["Read"].value.set("1")
  # end

end

dt.document.save

out << out1 << out2 << "</table></html>"
File.open('/tmp/bibtextmp', 'w') {|f| f << out}  
`/wiki/bin/dwpage.php -m 'Automatically generated from BibTeX file' commit /tmp/bibtextmp 'ref:Bibliography'`

out = out1 = out2 =''

# authors.each do |author, pubs|
#   out = "h2. #{author}'s publications\n\n"
#   pubs.each do |i|
#     item = b[i]
#     cit = CiteProc.process item.to_citeproc, :style => :apa
#     if File.exists?("/wiki/data/pages/ref/#{item.key}.txt")
#       out1 << "| [[:ref:#{item.key}|:ref:#{item.key}]] | #{cit}|\n"
#     else
#       out2 << "| :ref:#{item.key} | #{cit}|\n"
#     end
#   end
# 
#   out << out1 << out2
#   File.open('/tmp/bibtextmp', 'w') {|f| f << out}  
#   `/wiki/bin/dwpage.php -m 'Automatically generated from BibTeX file' commit /tmp/bibtextmp 'abib:#{author}'`
#   puts author
#   out = out1 = out2 = ''
# end
