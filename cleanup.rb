# encoding: UTF-8

# performing various cleanup functions

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript

def bname(ary)
  ary.map {|f| File.basename(f).remove(".txt")}
end

refs = bname(Dir[Wiki_path + "/data/pages/ref/*.txt"])
skimg = bname(Dir[Wiki_path + "/data/pages/skimg/*.txt"])
clips = bname(Dir[Wiki_path + "/data/pages/clip/*.txt"])
notes = bname(Dir[Wiki_path + "/data/pages/notes/*.txt"])
kindle = bname(Dir[Wiki_path + "/data/pages/kindle/*.txt"])

refs_week = bname(`find /wiki/data/pages/ref/*.txt -mtime -7`.split("\n"))
notes_week = bname(`find /wiki/data/pages/notes/*.txt -mtime -7`.split("\n"))

notes_short_week = []
notes_week.each {|f| notes_short_week << f unless File.size("#{Wiki_path}/data/pages/notes/#{f}.txt") > 500}

# check off all the notes in BibDesk, takes a few seconds
# notes.each do |n|
#   bibdesk_publication = try { app("BibDesk").document.search({:for =>n})[0] }
#   bibdesk_publication.fields["Notes"].value.set("1") if bibdesk_publication
# end

puts "<html><head><title>Researchr cleanup script report</title></head><body>"
puts "<h1>Researchr cleanup script report</h1>"

this = notes_week - notes_short_week
puts "<h2>New publications added last 7 days with decent-sized notes (#{this.size})</h2>"
this.each {|a| puts "<li><a href='#{Internet_path}/ref:#{a}'>#{a}</a></li>"}

puts "<h2>New publications added last 7 days with brief notes (#{notes_short_week.size})</h2>"
(notes_short_week).each {|a| puts "<li><a href='#{Internet_path}/ref:#{a}'>#{a}</a></li>"}

this = refs_week - notes_week
puts "<h2>New publications added last 7 days without notes (#{this.size})</h2>"
this.each {|a| puts "<li><a href='#{Internet_path}/ref:#{a}'>#{a}</a></li>"}

puts "<hr><h2>Notes pages without ref page</h2>"
(notes - refs).each {|a| puts "<li><a href='#{Internet_path}/notes:#{a}'>#{a}</a></li>"}

puts "<h2>Clipping pages without ref page</h2>"
(clips - refs).each {|a| puts "<li><a href='#{Internet_path}/clip:#{a}'>#{a}</a></li>"}

puts "<h2>Image pages without ref page</h2>"
(skimg - refs).each {|a| puts "<li><a href='#{Internet_path}/skimg:#{a}'>#{a}</a></li>"}

puts "<h2>Kindle pages without ref page</h2>"
(kindle - refs).each {|a| puts "<li><a href='#{Internet_path}/kindle:#{a}'>#{a}</a></li>"}

puts "<h2>Ref pages with no sub-pages</h2>"
(refs - (skimg + clips + notes + kindle)).each {|a| puts "<li><a href='#{Internet_path}/ref:#{a}'>#{a}</a></li>"}

puts "<h2>Kindle pages that also has clipping page</h2>"
(kindle & clips).each {|a| puts "<li><a href='#{Internet_path}/kindle:#{a}'>#{a}</a></li>"}
