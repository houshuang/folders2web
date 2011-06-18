# encoding: UTF-8
require 'pp'
require 'rubygems'
require 'appscript'
include Appscript

a = File.open("My Clippings.txt")
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
      annotations[title][:title] += annotations[title][:clippings].pop[:text] + " "
      next
    elsif content == "a"
      annotations[title][:authors] += annotations[title][:clippings].pop[:text] + " "
      next
    elsif content == "r"
      annotations[title][:references] += annotations[title][:clippings].pop[:text] + "<p>"
      next
    elsif content == "x"
      annotations[title][:abstract] += annotations[title][:clippings].pop[:text] + "<p>"
      next
    elsif !content.index(" ")
      annotations[title][:clippings].last[:keyword] = content
      next
    end
  end


  label = loc.index("- Note") ? 2 : 0  # colors it blue if it is a note, rather than highlight

  annotations[title] = {:clippings => Array.new, :title =>  "", :authors => "", :category => "", :abstract => "",
    :references => ""} unless annotations[title]

    if loc.index("- Note") && content[0..1] == "c "
      annotations[title][:category] = content[2..-1] + "/"
      next
    end

    loc = loc.gsub("- Note", "").gsub("Loc. ", "")
    annotations[title][:clippings] << {:meta => meta, :text => content, :label => label, :loc => loc, :added => added}
  end

  # now let's connect to DevonThink, and create the notes

  dt = app('DevonThink Pro')

  annotations.each do |title, book|
    itemtitle = book[:title] == "" ? title[0..30] : book[:title][0..30]
    folder = dt.create_location("Kindle/#{book[:category]}#{itemtitle}").get

    # preload the clippings with info from abstract/references, and a scaffolded "own notes" note. these will be inserted
    # just like any other clipping further below
    if book[:references] != ""
      book[:clippings] << {:meta => "", :text => "References" + " " * 40 + book[:references], :label => 3, :loc => 0, :added => Time.now}
    end 
    if book[:abstract] != ""
      book[:clippings] << {:meta => "", :text => "Abstract" + " " * 40 + book[:abstract], :label => 4, :loc => 0, :added => Time.now}
    end 
    book[:clippings] << {:meta => "", :text => "Own notes" + " " * 40 , :label => 5, :loc => 0, :added => Time.now}


    # insert all the clippings (included the ones preloaded above) into DevonThink. Thank you AppleScript!
    book[:clippings].sort! {|y, x| y[:loc].to_i <=> x[:loc].to_i }
    cliptext = "Title: #{book[:title]}\nAuthors: #{book[:authors]}"
    book[:clippings].each do |item|  

      dt.create_record_with( { :name => item[:text][0...40], :type_ => :text, :rich_text => "Title: #{book[:title]}\nAuthors: #{book[:authors]}\nLoc: #{item[:loc]}\n\n#{item[:text]}", :tags => item[:keyword], :comment => item[:meta], :label => item[:label], :creation_date => item[:added] }, {:in => folder} )
      cliptext << "Loc: #{item[:loc]}\n\n#{item[:text]}\n----\n\n" 

    end
    dt.create_record_with( { :name => "All clippings", :type_ => :text, :rich_text => 
      cliptext, :label => 3 }, {:in => folder} )

    end


    # for testing purposes: 

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
