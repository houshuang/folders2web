# encoding: UTF-8
# researchr scripts relevant to BibDesk (the right one is executed from the bottom of the file)

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'

# get date month year

def bname(ary)
  ary.map {|f| File.basename(f).remove(".txt") }
end

bib = json_bib

clips = bname(`find /wiki/data/pages/clip/*.txt -mtime -1`.split("\n"))
notes = bname(`find /wiki/data/pages/notes/*.txt -mtime -1`.split("\n"))

pages = `find #{Wiki_path}/data/pages -mtime -1 -type f`.split("\n")
directories = %r[/(ref|notes|skimg|kindle|clip|kbib|abib|jbib|idealog|sandbox|test|bib)/]
pages.reject! {|f| f.match(directories) && f[-4..-1] == ".txt"}
pages.map! {|f| capitalize_word(f.remove("#{Wiki_path}/data/pages").gsubs(["/",":"], [".txt", " "], ["_", " "])) }

out = "h1. Idea log #{Time.now.strftime("%A %B %d, %Y")}\n\nh2. Notes\n\n"
out << "h2. Notes modified last 24 hours\n\n"
notes.each { |x| out << "  * [@#{x}]\n" }

out << "\nh2. Clips added without notes last 24 hours\n\n"
(clips - notes).each  { |x| out << "  * [@#{x}]\n" }

out << "\nh2. Pages modified within last 24 hours\n\n"
pages.each {|x| out << "  * [[#{x}]]\n"}

puts out