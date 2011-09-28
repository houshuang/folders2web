# encoding: UTF-8

# researchr scripts relevant to BibDesk (the right one is executed from the bottom of the file)

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'

#### utility functions ####

def cururl
  Chrome.windows[1].get.tabs[Chrome.windows[1].get.active_tab_index.get].get.URL.get.strip
end
 
def curtitle
 title = Chrome.windows[1].get.tabs[Chrome.windows[1].get.active_tab_index.get].get.title.get.strip
end

#### keyboard commands ####

# adds the currently selected page to RSS feed, adds data to a temp file, will be formatted next time bibtex-batch
# is executed (Ctrl+Alt+Cmd+F)
def add_to_rss
  require 'open-uri'
  fname = Wiki_path + "/rss-temp"
  internalurl = cururl.split("/").last

  # load existing holding file, or start form scratch
  if File.exists?(fname)
    rss_entries = Marshal::load(File.read(fname))
  else
    rss_entries = Array.new
  end

  page_contents = open("http://localhost/wiki/#{internalurl}?vecdo=print").read
  contents = page_contents.scan(/<\!\-\- start rendered wiki content \-\-\>(.+?)\<\!\-\- end rendered wiki content \-\-\>/m)[0][0]

  contents.gsub!(/\<div class\=\"hiddenGlobal(.+?)\<div class\=\"plugin_include_content/m, '<div ') # remove pure bibtex
  contents.gsub!(/\<span class\=\"tip\"\>(.+?)\<\/span\>/, '') # remove citation tooltips
  # remove wiki clippings
  contents.gsub!(/\<div class\=\"plugin\_include\_content\ plugin\_include\_\_clip(.+?)\<\/div\>/m, '')
  contents.gsub!(/\<div class\=\"plugin\_include\_content\ plugin\_include\_\_kindle(.+?)\<\/div\>/m, '')

  # remove title (already given in metadata)
  contents.sub!(/\<h1 class\=\"sectionedit1\"\>(.+?)\<\/a\>\<\/h1\>/, '')

  contents.gsub!(/\<\!\-\- TOC START \-\-\>(.+?)\<\!\-\- TOC END \-\-\>/m, '')


  title = page_contents.scan(/\<h1(.+?)id(.+?)>(.+)\<(.+?)\<\/h1\>/)[0][2]
  rss_entries << {:title => title, :date => Time.now, :link => "#{Internet_path}/#{internalurl}", :description => contents}

  rss_entries = rss_entries.drop(1) if rss_entries.size > 10
  File.write(fname, Marshal::dump(rss_entries))
  growl("Article added to feed", "'#{title}' added to RSS feed")
end

# pops up dialogue box, asking where to send text, takes selected text (or just link, if desired) and inserts at the bottom
# of the selected page, with a context-relevant reference to original source
def do_clip(pagename, titletxt, onlylink = false, onlytext = false)
  pagepath = (Wikipages_path + "/" + clean_pagename(pagename) + ".txt").gsub(":","/")

  curpage = cururl.split("/").last

  # format properly if citation
  unless onlytext 
    if curpage.index("ref:")
      curpage = "[@#{curpage.split(':').last}]" 
    elsif cururl.index("localhost/wiki")
      curpage = "[[:#{capitalize_word(curpage.gsub("_", " "))}]]"
    else
      title = (titletxt == "" ? title : titletxt)
      curpage ="[[#{cururl}|#{title}]]"
    end
  else
    curpage = ''
  end

  insert = (onlylink ? "  *" : utf8safe(pbpaste) )

  if File.exists?(pagepath)
    f = File.read(pagepath) 
    growltext = "Selected text added to #{pagename}"
  else
    f = "h1. "+ capitalize_word(pagename) + "\n\n"
    growltext = "Selected text added to newly created #{pagename}"
  end
  filetext = f + "\n----\n" + insert.gsub("\n","\n\n").gsub("\n\n\n","\n\n") + " " + curpage

  dwpage(pagename, filetext)
  growl("Text added", growltext)
end

def clip
  require 'pashua'
  title = curtitle
  
  # asks for a page name, and appends selected text on current page to that wiki page, with proper citation
  pagetmp = wikipage_selector("Which wikipage do you want to add text to?",true, "
  xb.type = checkbox
  xb.label = only insert link to this page
  xb.tooltip = Otherwise, it will take the currently selected text and insert
  ob.type = checkbox
  ob.label = do not include citation information, only insert pure text
  fb.type = textbox
  fb.default = #{title.strip}
  fb.label = Link title\n"
  )
  
  exit if pagetmp["cancel"] == 1
  onlylink = pagetmp['xb'] == "1" ? true : false
  onlytext = pagetmp['ob'] == "1" ? true : false
  pagename = pagetmp['cb'].strip
  title = pagetmp['fb'].strip
  File.write("/tmp/dokuwiki-clip.tmp","#{pagename}\n#{title}\n#{onlytext.to_s}")
  do_clip(pagename, title, onlylink, onlytext)
end

# uses info stored in temp file to do a clipping from the same page, to the same page
def clip_again
  a = File.read("/tmp/dokuwiki-clip.tmp")
  page, title, onlytext_s = a.split("\n")
  onlytext = (onlytext_s == 'true') ? true : false
  if title.strip == ""
    title = curtitle
  end
  do_clip(page, title, false, onlytext)
end

# cleans up a text into bulleted list, either separated by commas or by line shifts
# there is quite a lot of black magic and guessing in here, a wonder it mostly works
def bulletlist
  b = pbpaste
  a = b.gsub(/^[\t]*\*/,"") # strip off bullet etc from beginning

  if a.scan("\n").size > 1  # determine whether to split on newline, space or comma
    splt = "\n"
  elsif a.scan(";").size > 1
    splt = ";"
  elsif a.scan(".").size > 2
    splt = "."
  elsif a.scan(",").size < 1
    splt = " "
  else
    splt = ","
  end

  splits = a.split(splt)

  if splits.last.index(" and ") # deal with situation where the last two items are delimited with "and"
    x,y = splits.last.split(" and ")
    splits.pop
    splits << x
    splits << y
  end

  out = ''
  splits.each do |item|
    i = item.gsub(", and","").gsub(/[\.\*]/,"").gsub(/^ *and /,"").gsub(/\.$/,"").gsub("•","").strip
    out << "  * #{i}\n" if i.size > 0
  end

  puts out
end

# Present a wiki page selector and open the page selected
def go
  require 'pashua'
  pagetmp = wikipage_selector("Jump to which page?")
  exit unless pagetmp
  Chrome.windows[1].get.tabs[Chrome.windows[1].get.active_tab_index.get].get.URL.set("http://localhost/wiki/#{pagetmp}")
end

# Moves last screenshot to DokuWiki media folder, and inserts a link to that image properly formatted 
def image
  wiki = cururl[22..-1]
  w,dummy = wiki.split("?")
  wikipage = w.gsub(":","_").gsub("%3A","_").gsub("%20","_").downcase
  curfile =  File.last_added("#{Home_path}/Desktop/Screen*.png") # this might be different between different OSX versions

  if curfile == nil
    growl("No screenshots available")
    exit
  end

  newfilename, pagenum = filename_in_series("#{Wikimedia_path}/#{wikipage}",".png")
  if File.exists?(newfilename)
    growl("Error!", "File already exists, aborting!")
    exit
  end
  `mv "#{curfile.strip}" "#{newfilename}"`
  `touch "#{newfilename}"`  # to make sure it comes up as newest next time we run filename_in_series

  puts "{{pages:#{wikipage}#{pagenum}.png}}"
end

# asks for the name of a page, and presents it side-by-side with the existing page, in editing mode if it's a wiki page
def sbs
  page = wikipage_selector("Choose page to view side-by-side with the current page")
  exit unless page

  if cururl.index("localhost/wiki")
    cururl = cururl.to_s + "?do=edit&vecdo=print"
  else
    # uses Instapaper to nicely format the article text, for fitting into a split-screen window
    cururl = "http://www.instapaper.com/text?u=\"+encodeURIComponent(\"#{cururl}\")+\""
  end

  newurl = "http://localhost/wiki/#{page.gsub(" ","_")}"

  js = "var MyFrame=\"<frameset cols=\'*,*\'><frame src=\'#{cururl}\'><frame src=\'#{newurl}?do=edit&vecdo=print\'></frameset>\";with(document) {    write(MyFrame);};return false;"
  Chrome.windows[1].get.tabs[Chrome.windows[1].get.active_tab_index.get].get.execute(:javascript => js)
end

# asks for name, and creates a new author page from a template
def newauthor
  require 'Pashua'
  include Pashua

  config = <<EOS
  *.title = Add a new author page
  cb.type = textfield 
  cb.label = Name of author page to create
  cb.width = 220 
  db.type = cancelbutton
  db.label = Cancel
  db.tooltip = Closes this window without taking action
EOS

  pagetmp = pashua_run config
  exit if pagetmp["cancel"] == 1
  page = pagetmp["cb"]
  pname = "/wiki/data/pages/a/#{clean_pagename(page)}.txt"
  
  File.open(pname,"w") {|f| f<<"h1. #{page}\n\nh2. Research\n\nh2. Links\n  * [[ |Homepage]]
  \n{{page>abib:#{page}}}"}
  
  `chmod a+rw "#{pname}"`
  
  `open "http://localhost/wiki/a:#{page}?do=edit"`
end

#### Running the right function, depending on command line input ####

Chrome = Appscript.app('Google Chrome')
send *ARGV
