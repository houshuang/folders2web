require 'rubygems'
require 'appscript'

a = File.open("My Clippings.txt")
include Appscript
annotations = Hash.new

while !a.eof?
  title = a.readline.strip
  title.gsub!("(shaklev@gmail.com)", "")
  meta = a.readline.strip
  loc, added = meta.split(" | ")

  skip = false
  skip = true unless loc.index("Highlight") 

  loc = loc.gsub("- Highlight", "").strip
  added = added.gsub("Added on ", "").strip
  content = ''
  while 1
    c = a.readline
    break if c == "==========\r\n"
    content << c
  end
  next if skip
  annotations[title] = Array.new unless annotations[title]
  annotations[title] << {:meta => meta, :text => content.strip}
end

dt = app('DevonThink Pro')

annotations.each do |title, book|
  folder = dt.create_location("Kindle/#{title}").get
  book.each do |item|
    dt.create_record_with({:name => item[:text][0...100], :type_ => :txt, :plain_text => item[:text], :comment => item[:meta]},{:in => folder})
  end
end
#dt.create_record_with()




# Kindle User's Guide (Amazon)
# - Highlight Loc. 881  | Added on Wednesday, January 05, 2011, 05:24 PM
# 
# dictionary
# ==========
# Introductions to ijCSCL (Gerry Stahl)
# - Highlight Loc. 493-95  | Added on Wednesday, January 05, 2011, 05:39 PM
# 
# While it may have been feasible to make progress on CSCL problems during the first decade of the field’s existence from exclusively within an educational psychology perspective or using an artificial intelligence approach, it is less likely now.
# ==========
