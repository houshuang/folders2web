# encoding: UTF-8
$:.push(File.dirname($0))
require 'appscript'
include Appscript
require 'utility-functions'

# asks for a page name, and appends selected text on current page to that wiki page, with proper citation

pagetmp = wikipage_selector("Which wikipage do you want to add text to?")

exit if pagetmp["cancel"] == 1
pagename = pagetmp['cb'].strip

pagepath = Wikipages_path + "/" + clean_pagename(pagename) + ".txt"
pagepath.gsub!(":","/")

dt=app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get
curpage = cururl.split("/").last

# format properly if citation
if curpage.index("ref:")
  curpage = "[@#{curpage.split(':').last}]" 
elsif cururl.index("localhost/wiki")
  curpage = "[[#{capitalize_word(curpage.gsub("_", " "))}]]"
else
  title = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.title.get
  curpage ="[[#{cururl}|#{title[0..50]}]]"
end

insert = utf8safe(pbpaste)

if File.exists?(pagepath)
  f = File.read(pagepath) + "\n\n<html><hr></html>\n\n"
  growltext = "Selected text added to #{pagename}"
else
  f = "h1. "+ capitalize_word(pagename) + "\n\n"
  growltext = "Selected text added to newly created #{pagename}"
end

filetext = f + insert.gsub("\n","\n\n").gsub("\n\n\n","\n\n") + " " + curpage

dwpage(pagename, filetext)
growl("Text added", growltext)