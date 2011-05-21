# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"
require 'pp'
require 'open-uri'
require 'appscript'
require 'cgi'
include Appscript

# grabs the name of the currently open BibDesk file, and puts on clipboard formatted as a DokuWiki reference
f = File.open(curpath + "log","a")

`/usr/local/bin/growlnotify -m "Starting lookup on Google Scholar"`

dt = app('BibDesk')
d = dt.document.selection.get[0]
title = d[0].title.get.gsub(" ","%20").gsub(/[\{|\}]/,'')
author =  d[0].author.get[0].name.get.gsub(" ","%20")
f << "#{title}, #{author}\n"

url = "http://scholar.google.com/scholar?&as_q=#{title}&as_sauthors=#{author}"
page = open(url).read
a = page.scan(/<h3><a href=(.*?)>(.*?)<\/a>(.*?)a href="(.*?)"(.*?)\[PDF\]/)
unless a == []

  items = Array.new
  a.each do |item|
    title = CGI::unescapeHTML(item[1].gsub(/[\{|\}]/,"").gsub(/\<(\/?)b\>/,""))
    items << {:title => title, :url => item[3]}
  end

  out = ''
  c = 0
  File.open("gscholar-tmp","w") do |f|
    items.each do |item|
      c += 1
      out << "#{c}: #{item[:title]}\n"
      f << item[:url] << "\n"
    end
  end
  `/usr/local/bin/growlnotify -t "Possible hits" -m "#{out}"`

else
  `/usr/local/bin/growlnotify -m "No hits with PDFs"`
end