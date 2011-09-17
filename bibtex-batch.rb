# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'bibtex'
require 'citeproc'
require 'find'
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

b = BibTeX.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
b.parse_names

out1 = ''
out2 = ''
out3 = ''
out4 = ''
authors = Hash.new
json = Hash.new
keywords = Hash.new

counter = Hash.new
counter[:hasref] = 0
counter[:noref] = 0
counter[:notes] = 0
counter[:clippings] = 0
counter[:images] = 0

b.each do |item|
  ax = []
  if item.respond_to? :author
    item.author.each do |a|
      authors[nice_name(a)] = Array.new unless authors[nice_name(a)]
      ax << a.last.gsub(/[\{\}]/,"")
      authors[nice_name(a)] << item.key
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
  hasfiles = Array.new
  hasfiles[4]=""
  if File.exists?("/wiki/data/pages/ref/#{item.key}.txt")
    counter[:hasref] += 1
    if File.exists?("/wiki/data/pages/clip/#{item.key}.txt") || File.exists?("/wiki/data/pages/kindle/#{item.key}.txt")
      counter[:clippings] += 1
      hasfiles[1] = "C"
    end
    if File.exists?("/wiki/data/pages/skimg/#{item.key}.txt") 
      counter[:images] += 1
      hasfiles[2] = "I"
    end
    if File.exists?("/wiki/data/pages/notes/#{item.key}.txt")
      counter[:notes] += 1 
      hasfiles[0] = "N"
      out1 << "<tr><td><a href = '/wiki/ref:#{item.key}'>#{item.key}</a></td><td>#{hasfiles.join("</td><td>&nbsp;")}</td><td>#{cit}</td></tr>\n"
    elsif hasfiles[1] == "C"
      out2 << "<tr><td><a href = '/wiki/ref:#{item.key}'>#{item.key}</a></td><td>#{hasfiles.join("</td><td>&nbsp;")}</td><td>#{cit}</td></tr>\n"
    else
      out3 << "<tr><td><a href = '/wiki/ref:#{item.key}'>#{item.key}</a></td><td>#{hasfiles.join("</td><td>&nbsp;")}</td><td>#{cit}</td></tr>\n"

      
    end
    
  else
    counter[:noref] += 1
    out4 << "<tr><td>#{item.key}</td><td>#{hasfiles.join("</td><td>&nbsp;")}</td><td>#{cit}</td></tr>\n"
  end
  
  # mark as read if notes exist
  # if File.exists?("/wiki/data/pages/clip/#{item.key}.txt") || File.exists?("/wiki/data/pages/kindle/#{item.key}.txt")
  #   dt.document.search({:for =>item.key.to_s})[0].fields["Read"].value.set("1")
  # end

end
out = "h1. Bibliography\n\nDownload [[http://dl.dropbox.com/u/1341682/Bibliography.bib|entire BibTeX file]]. Also see bibliography by [[abib:start|author]] or by [[kbib:start|keyword]].\n\nPublications that have their own pages are listed on top, and hyperlinked. Most of these also have clippings and many have key ideas.\n\nStatistics: Totally **#{counter[:hasref] + counter[:noref]}** publications, and **#{counter[:hasref]}** publications have their own wikipages. Of these, **#{counter[:images]}** with notes (key ideas) **(N)**, **#{counter[:clippings]}** with highlights (imported from Kindle or Skim) **(C)**, and **#{counter[:images]}** with images (imported from Skim) **(I)** and.<html><table>"

dt.document.save

File.open(JSON_path,"w"){|f| f << JSON.fast_generate(json)}

out << out1 << out2 << out3 << out4 << "</table></html>"
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
    f << "| [[#{ax[0]}|#{ax[1]}]] | #{apage} |#{ax[2]}|\n"
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