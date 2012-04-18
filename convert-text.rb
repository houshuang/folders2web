# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'

# grabs currently selected text, and transforms [@scardamalia2006knowledge] citations to APA citations. with
# onlylist, it only generates a bibliography, without it it creates in-text citations (Scardamalia, 2006) and
# a bibliography

onlylist = (ARGV[0] == 'onlylist')
acm = (ARGV[0] == 'acm')
bib = json_bib
citations = Hash.new
if File.exists?(ARGV[1])
  doc = File.read(ARGV[1])
else
  doc = pbpaste
end

doc.scan( /(\[?\@[a-zA-Z0-9\-\_]+\]?)/ ).each do |hit|
  hit = hit[0]
  hitnobraces = hit.remove(/[\@\[\]]/)
  if bib[hitnobraces]
    citations[hitnobraces] = bib[hitnobraces]
    if onlylist
      doc.remove!(hit)
    else
      doc.gsub!(hit, citations[hitnobraces][0] + ", " + citations[hitnobraces][1]) unless acm
    end
  end
end
doc << "\n\n\References (#{citations.size})\n" unless onlylist
citations.sort.each do |item|
  doc << "[@#{item[0]}]: #{item[1][2].remove(/[\{\}]/)}\n\n"
end

puts doc.strip