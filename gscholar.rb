# encoding: UTF-8
$:.push(File.dirname($0))
require 'open-uri'
require 'cgi'
require 'appscript'
include Appscript
require 'utility-functions'

# looks up currently selected bibdesk publication on google scholar and presents a menu to choose from
# idea: rewrite using pashua

growl("Starting lookup on Google Scholar")

dt = app('BibDesk')
d = dt.document.selection.get[0]
title = d[0].title.get.gsub(" ","%20").gsub(/[\{|\}]/,'')
author =  d[0].author.get[0].name.get.gsub(" ","%20")

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
  f = "#{d[0].cite_key.get}\n"
  items.each do |item|
    c += 1
    out << "#{c}: #{item[:title]}\n"
    f << item[:url] << "\n"
  end
  File.write("/tmp/gscholar-tmp",f)
  growl("Possible hits","#{out}")

else
  growl("No hits with PDFs")
end