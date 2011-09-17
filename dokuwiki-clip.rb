# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript

dt=app('Google Chrome')
title = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.title.get

# asks for a page name, and appends selected text on current page to that wiki page, with proper citation
pagetmp = wikipage_selector("Which wikipage do you want to add text to?",true, "
xb.type = checkbox
xb.label = only insert link to this page
xb.tooltip = Otherwise, it will take the currently selected text and insert
fb.type = textbox
fb.default = #{title.strip}
fb.label = Link title\n"
)


exit if pagetmp["cancel"] == 1
onlylink = true if pagetmp['xb'] == "1"
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
  title = (pagetmp['fb'] == "" ? dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.title.get : pagetmp['fb'])
  curpage ="[[#{cururl}|#{title}]]"
end

insert = (onlylink ? "  *" : utf8safe(pbpaste) )

if File.exists?(pagepath)
  f = File.read(pagepath) 
  growltext = "Selected text added to #{pagename}"
  if f.index("id=clippings")
    hr = "\n"
  else
    hr = "\n\n----\n\n"
  end
else
  f = "h1. "+ capitalize_word(pagename) + "\n\n"
  hr = ""
  growltext = "Selected text added to newly created #{pagename}"
end
filetext = f + hr + insert.gsub("\n","\n\n").gsub("\n\n\n","\n\n") + " " + curpage

dwpage(pagename, filetext)
growl("Text added", growltext)