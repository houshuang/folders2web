# encoding: UTF-8

# performing various cleanup functions

$:.push(File.dirname($0))
require 'utility-functions'

def bname(ary)
  ary.map {|f| File.basename(f)}
end

refs = bname(Dir[Wiki_path + "/data/pages/ref/*.txt"])
skimg = bname(Dir[Wiki_path + "/data/pages/skimg/*.txt"])
clips = bname(Dir[Wiki_path + "/data/pages/clip/*.txt"])
notes = bname(Dir[Wiki_path + "/data/pages/notes/*.txt"])
kindle = bname(Dir[Wiki_path + "/data/pages/kindle/*.txt"])

puts "<html><head>Researchr cleanup script report</title></head>"
puts "<h1>Researchr cleanup script report</h1>"

puts "<h2>Notes pages without ref page</h2>"
(notes - refs).each {|a| puts "<li><a href='#{Internet_path}/notes:#{a.remove(".txt")}'>#{a.remove(".txt")}</a></li>"}

puts "<h2>Clipping pages without ref page</h2>"
(clips - refs).each {|a| puts "<li><a href='#{Internet_path}/clip:#{a.remove(".txt")}'>#{a.remove(".txt")}</a></li>"}

puts "<h2>Image pages without ref page</h2>"
(skimg - refs).each {|a| puts "<li><a href='#{Internet_path}/skimg:#{a.remove(".txt")}'>#{a.remove(".txt")}</a></li>"}

puts "<h2>Kindle pages without ref page</h2>"
(kindle - refs).each {|a| puts "<li><a href='#{Internet_path}/kindle:#{a.remove(".txt")}'>#{a.remove(".txt")}</a></li>"}

puts "<h2>Ref pages with no sub-pages</h2>"
(refs - (skimg + clips + notes + kindle)).each {|a| puts "<li><a href='#{Internet_path}/ref:#{a.remove(".txt")}'>#{a.remove(".txt")}</a></li>"}


exit(0)
puts "<h2>Kindle pages that also has clipping page</h2>"
().each {|a| puts "<li><a href='#{Internet_path}/kindle:#{a.remove(".txt")}'>#{a.remove(".txt")}</a></li>"}
