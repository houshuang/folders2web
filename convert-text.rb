# encoding: UTF-8
$:.push(File.dirname($0))
require 'pp'
require 'utility-functions'

bib = json_bib
citations = Hash.new
doc = utf8safe(pbpaste)
doc.scan( /\@([a-zA-Z]+[0-9]+[a-zA-Z]+)/).each do |hit|
  hit = hit[0]
  if bib[hit]
    citations[hit] = bib[hit] 
    doc.gsub!("@#{hit}", citations[hit][0] + ", " + citations[hit][1])
  end
end
doc << "\n\n\References\n"
citations.sort.each do |item|
  doc << item[1][2] + "\n"
end

puts doc