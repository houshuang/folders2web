# encoding: UTF-8

# Add a text field 
$:.push(File.dirname($0))
require 'appscript'
include Appscript
require 'utility-functions'

pagetmp = wikipage_selector("Which wikipage do you want to add text to?")
exit if pagetmp["cancel"] == 1
pagename = pagetmp['cb']
pagepath = clean_pagename(pagename)

dt=app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get
curpage = cururl.split("/").last

# format properly if citation
if curpage.index("ref:")
  curpage = "[@#{curpage.split(':').last}]" 
elsif cururl.index("localhost/wiki")
  curpage = "[[#{curpage}|#{pagename}]]"
else
  title = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.title.get
  curpage ="[[#{cururl}|#{title[0..50]}]]"
end

insert = utf8safe(pbpaste)

if File.exists?(filename)
  f = File.read(filename) + "\n\n<html><hr></html>\n\n"
  growltext = "Selected text added to #{page}"
else
  f = "h1. "+ capitalize_word(pagename) + "\n\n"
  growltext = "Selected text added to newly created #{page} #{filename}"
end

filetext = f + insert.gsub("\n","\n\n").gsub("\n\n\n","\n\n") + " " + curpage

dwpage(page, filetext)
growl("Text added", growltext)