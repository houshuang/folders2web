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

def namify(names)
  return names[0] if names.size == 1
  return names[0] + " et al." if names.size > 3
  names[0..-2].join(", ") + " & " + names[-1].to_s
end

b = BibTeX.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
b.parse_names

out = "h1. Bibliography\n\n<html><table>"
out1 = ''
out2 = ''
authors = Hash.new

json = Hash.new
b.each do |item|
#  puts item.key
  ax = []
  item.author.each do |a|
    authors[nice(a)] = Array.new unless authors[nice(a)]
    ax << a.last.gsub(/[\{\}]/,"")
    authors[nice(a)] << item.key
  end
  cit = CiteProc.process item.to_citeproc, :style => :apa
  year = (defined? item.year) ? item.year.to_s : "n.d."
  if year == "n.d." and cit.match(/\((....)\)/) 
    year = $1
  end
  json[item.key.to_s] = [namify(ax), year, cit]

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

File.open("/wiki/lib/plugins/test/json.tmp","w"){|f| f << JSON.fast_generate(json)}

out << out1 << out2 << "</table></html>"
File.open('/tmp/bibtextmp', 'w') {|f| f << out}  
`/wiki/bin/dwpage.php -m 'Automatically generated from BibTeX file' commit /tmp/bibtextmp 'ref:Bibliography'`

out = out1 = out2 =''

authorlisted = Array.new
authors.each do |author, pubs|
  next if pubs.size < 2
  out = "h2. #{author}'s publications\n\n"
  pubs.each do |i|
    item = b[i]
    cit = CiteProc.process item.to_citeproc, :style => :apa
    if File.exists?("/wiki/data/pages/ref/#{item.key}.txt")
      out1 << "| [[..:ref:#{item.key}]] | #{cit}|\n"
    else
      out2 << "| #{item.key} | #{cit}|\n"
    end
  end

  out << out1 << out2
  authorname = author.gsub(" ","_").downcase
  authorlisted << [authorname,author,pubs.size]
  File.open("/wiki/data/pages/abib/#{authorname}.txt", 'w') {|f| f << out}  
  puts author
  out = out1 = out2 = ''
end

File.open("/wiki/data/pages/abib/start.txt","w") do |f|
  f << "h1.List of authors with publications\n\nList of authors with publications. Only includes authors with three or more publications.\n\n"
  authorlisted.each do |ax|
    f << "  * [[#{ax[0]}|#{ax[1]}]] (#{ax[2]})\n"
  end
end
