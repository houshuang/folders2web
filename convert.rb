require 'pp'
require 'rubygems'
require 'appscript'

a = File.open("My Clippings.txt")
include Appscript
annotations = Hash.new

while !a.eof?   # until we've gone through the whole file, line by line
  title = a.readline.strip
  title.gsub!("(shaklev@gmail.com)", "")
  meta = a.readline.strip
  loc, added = meta.split(" | ")

  loc = loc.gsub("- Highlight", "").strip
  added = added.gsub("Added on ", "").strip
  content = ''
  while 1
    c = a.readline
    break if c == "==========\r\n"     # end of record
    content << c
  end

  content.strip!

  # now we have title, meta, and content. let's process

  if loc.index("- Note") && annotations[title]

    if content == "t"
      annotations[title][:title] += annotations[title][:clippings].pop[:text]
      next
    elsif content == "a"
      annotations[title][:authors] += annotations[title][:clippings].pop[:text] + " "
      next
    elsif !content.index(" ")
      annotations[title][:clippings].last[:keyword] = content
      next
    end
  end

  
  label = loc.index("- Note") ? 2 : 0  # colors it blue if it is a note, rather than highlight

  annotations[title] = {:clippings => Array.new, :title =>  "", :authors => "", :category => ""} unless annotations[title]

  if loc.index("- Note") && content[0..1] == "c "
    annotations[title][:category] = content[2..-1] + "/"
    next
  end

  loc = loc.gsub("- Note", "").gsub("Loc. ", "")
  annotations[title][:clippings] << {:meta => meta, :text => content, :label => label, :loc => loc, :added => added}
end

pp annotations
# now let's connect to DevonThink, and create the notes

dt = app('DevonThink Pro')

annotations.each do |title, book|
  itemtitle = book[:title] == "" ? title[0..30] : book[:title][0..30]
  folder = dt.create_location("Kindle/#{book[:category]}#{itemtitle}").get

  book[:clippings].each do |item|  
    dt.create_record_with( { :name => item[:text][0...100], :type_ => :html, :rich_text => 
      "<b>Title</b>: #{book[:title]}<br><b>Authors: </b>#{book[:authors]}</b><br><b>Loc:</b> #{item[:loc]}<p>#{item[:text]}", 
      :tags => item[:keyword], :comment => item[:meta], :label => item[:label], :creation_date => item[:added] }, {:in => folder} )
  end
end

  # annotations.each do |id, paper|
  #   puts "Title: #{paper[:title]}"
  #   puts "Authors: #{paper[:authors]}"
  #   puts "*****"
  #   
  #   paper[:clippings].each do |clipping|
  #     puts "Keyword: #{clipping[:keyword]}"
  #     puts "Meta: #{clipping[:meta]}"
  #     puts "\n#{clipping[:text]}"
  #     puts "==================="
  #   end
  #   puts "*" * 50
  # end

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
