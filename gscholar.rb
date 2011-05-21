# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"
require 'pp'
require 'open-uri'
require 'appscript'
include Appscript

# grabs the name of the currently open BibDesk file, and puts on clipboard formatted as a DokuWiki reference
f = File.open(curpath + "log","a")

def download full_url, to_here
    require 'open-uri'
    writeOut = open(to_here, "wb")
    writeOut.write(open(full_url).read)
    writeOut.close
end


dt = app('BibDesk')
d = dt.document.selection.get[0]
title = d[0].title.get.gsub(" ","%20").gsub(/[\{|\}]/,'')
author =  d[0].author.get[0].name.get.gsub(" ","%20")
f << "#{title}, #{author}\n"

url = "http://scholar.google.com/scholar?&as_q=#{title}&as_sauthors=#{author}"
page = open(url).read
puts page
a = page.scan(/\<p\>(.+?)\<\/a\>(.+?)a href\=\"(.+?)\"(.*)\[PDF\]/)
pp a
exit
File.open("gscholar-tmp","w") do |f|
  a.each do |item|
    f << item[1] + "," + item[3] + "\n"
  end
end
f.close

# if a 
#   pp a
#   download(a[0], 'pdftmp.pdf')
#   `qlmanage -p pdftmp.pdf`
# end
