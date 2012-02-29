# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'

# grabs currently selected text, and transforms [@scardamalia2006knowledge] citations to APA citations. with
# onlylist, it only generates a bibliography, without it it creates in-text citations (Scardamalia, 2006) and
# a bibliography

onlylist = (ARGV[0] == 'onlylist')
bib = json_bib
citations = Hash.new
doc = utf8safe(pbpaste)
doc.scan( /(\[?\@[a-zA-Z0-9\-\_]+\]?)/ ).each do |hit|
  hit = hit[0]
  hitnobraces = hit.gsub(/[\@\[\]]/,"")
  if bib[hitnobraces]
    citations[hitnobraces] = bib[hitnobraces]
    if onlylist
      doc.gsub!(hit, "")
    else
      doc.gsub!(hit, citations[hitnobraces][0] + ", " + citations[hitnobraces][1])
    end
  end
end
doc << "\n\n\References\n" unless onlylist
citations.sort.each do |item|
  doc << item[1][2].gsub(/[\{\}]/,'') + "\n\n"
end

puts doc.strip
