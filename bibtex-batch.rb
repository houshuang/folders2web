# encoding: UTF-8
require 'bibtex'
require 'citeproc'
$:.push(File.dirname($0))
require 'find'
require 'utility-functions'
require 'appscript'
include Appscript

# batch processes entire bibliography file and generates ref:bibliography in wiki, used for refnotes database

dt = app('BibDesk')
dt.document.save

def pdfpath(citekey)
  if File.exists?("#{PDF_path}/#{citekey.to_s}.pdf")
    return "[[skimx://#{citekey}|PDF]]"
  end
end

def sort_pubs(pubs)
  return pubs.sort {|x,y| x.to_s.scan(/[0-9]+/)[0].to_i <=> y.to_s.scan(/[0-9]+/)[0].to_i}
end

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

out = "h1. Bibliography\n\nDownload [[http://dl.dropbox.com/u/1341682/Bibliography.bib|entire BibTeX file]]. Also see bibliography by [[abib:start|author]] or by [[kbib:start|keyword]].\n\nPublications that have their own pages are listed on top, and hyperlinked. Most of these also have clippings and many have key ideas.<html><table>"
out1 = ''
out2 = ''
authors = Hash.new
json = Hash.new
keywords = Hash.new

b.each do |item|
  ax = []
  if item.respond_to? :author
    item.author.each do |a|
      authors[nice(a)] = Array.new unless authors[nice(a)]
      ax << a.last.gsub(/[\{\}]/,"")
      authors[nice(a)] << item.key
    end
  end
  if item.respond_to? :keywords
    item.keywords.split(";").each do |a|
      a.strip!
      keywords[a] = Array.new unless keywords[a]
      keywords[a] << item.key
    end    
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
File.open('/wiki/data/pages/bib/bibliography.txt', 'w') {|f| f << out}  

###############################################
# generate individual files for each author


authorlisted = Array.new
authors.each do |axx, pubs|
  out ='' 
  out1 = ''
  out2 =''
  author = axx.strip
  next if (author.strip[-1] == "." || author[-2] == " " || author[-2] == author[-2].upcase || author[1] == '.')
  out = "h2. #{author}'s publications\n\n"
  sort_pubs(pubs).each do |i|
    item = b[i]
    cit = CiteProc.process item.to_citeproc, :style => :apa
    if File.exists?("/wiki/data/pages/ref/#{item.key}.txt")
      out1 << "| [[..:ref:#{item.key}]] | #{cit}|#{pdfpath(item.key)}|\n"
    else
      out2 << "| #{item.key} | #{cit}|#{pdfpath(item.key)}|\n"
    end
  end

  out << out1 << out2
  authorname = clean_pagename(author)
  authorlisted << [authorname,author,pubs.size]
  File.open("/wiki/data/pages/abib/#{authorname}.txt", 'w') {|f| f << out}  
  puts author
end

File.open("/wiki/data/pages/abib/start.txt","w") do |f|
  f << "h1.List of authors with publications\n\nList of authors with publications. Only includes authors with three or more publications, with full names.\n\n"
  authorlisted.sort {|x,y| y[2].to_i <=> x[2].to_i}.each do |ax|
    apage = ''
    if File.exists?("#{Wikipages_path}/a/#{ax[0]}.txt")
      apage = "[[:a:#{ax[0]}|author page]]"
    end
    f << "| [[#{ax[0]}|#{ax[1]}]] | #{apage}&nbsp; |#{ax[2]}|\n"
  end
end

###############################################
# generate individual files for each keyword

keywordslisted = Array.new
keywords.each do |keyword, pubs|
  out ='' 
  out1 = ''
  out2 =''
  out = "h2. Publications with keyword \"#{keyword}\"\n\n"
  sort_pubs(pubs).each do |i|
    item = b[i]
    cit = CiteProc.process item.to_citeproc, :style => :apa
    if File.exists?("/wiki/data/pages/ref/#{item.key}.txt")
      out1 << "| [[..:ref:#{item.key}]] | #{cit}| #{pdfpath(item.key)} |\n"
    else
      out2 << "| #{item.key} | #{cit} | #{pdfpath(item.key)}|\n"
    end
  end

  out << out1 << out2
  kwname = keyword.gsub(/[\,\.\/ ]/,"_").downcase
  keywordslisted << [kwname,keyword,pubs.size]
  File.open("/wiki/data/pages/kbib/#{kwname}.txt", 'w') {|f| f << out}  
  puts kwname
end

File.open("/wiki/data/pages/kbib/start.txt","w") do |f|
  f << "h1. List of publication keywords\n\n"
  keywordslisted.sort {|x,y| y[2].to_i <=> x[2].to_i}.each do |ax|
    f << "|[[#{ax[0]}|#{ax[1]}]]|#{ax[2]}|\n"
  end
end

###############################################
# generate file with imported citations missing key ideas

pages = Array.new
out = "h1.Needs key ideas\n\nList of publications with clippings, which do not have key ideas.\n\n"

Find.find("/wiki/data/pages/ref") do |path|
  next unless File.file?(path)
  fn = File.basename(path)
  if (File.exists?("/wiki/data/pages/kindle/#{fn}") || File.exists?("/wiki/data/pages/clip/#{fn}")) && !File.exists?("/wiki/data/pages/notes/#{fn}")
    out << "  * [@#{fn[0..-5]}]\n"
  end
end
File.open("/wiki/data/pages/bib/needs_key_ideas.txt","w") {|f| f << out}