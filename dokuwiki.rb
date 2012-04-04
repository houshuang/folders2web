# encoding: UTF-8
# researchr scripts relevant to BibDesk (the right one is executed from the bottom of the file)

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'

#### utility functions ####

def has_selection
  pbcopy('')
  @chrome.windows[1].active_tab.copy_selection
  sel = pbpaste
  return (sel.size > 0) ? sel : nil
end

def cururl
  @chrome.windows[1].active_tab.get.URL.get.strip
end

def curtitle
 title = @chrome.windows[1].get.tabs[@chrome.windows[1].get.active_tab_index.get].get.title.get.strip
end

# cleans bibtex string on clipboard
def clean_bibtex
  pbcopy(cleanup_bibtex_string(pbpaste))
end

# gets the bibtex from the current page, whether it's researchr or scrobblr, and cleans it up
def get_bibtex_from_page
  # returns the content of the BibTeX hidden div
  if cururl.index("/ref:") || cururl.index("herokuapp")
    query = cururl.index("herokuapp") ? "getElementById('bibtex')" : "querySelectorAll('.code')[0]"
    js = "document.#{query}.innerHTML;"
    bibtex = @chrome.windows[1].get.tabs[@chrome.windows[1].get.active_tab_index.get].get.execute(:javascript => js)
    bibtex = bibtex.gsubs(
      [/keywords.+?\}\,\n/i,    ''],                      # no keywords, we want to assign our own
      ["<b>Bibtex:</b>",        ''],                      # not part of bibtex string
      ["&amp;",                 '&'],
      [/bdsk\-file.+?\}/mi,     '}'],                     # the local bibdesk file reference is useless
      ["read = {1}",            "read = {0}"]             # other's might have read it, we haven't yet
      ).strip

    bibtex << "}" unless bibtex.scan("{").size == bibtex.scan("}").size  # ensure right number of closing brackets
  else
    require 'open-uri'
    url = "http://scraper.bibsonomy.org/service?url=#{cururl}&format=bibtex"
    bibtex = try { open(url).read }
    bibtex.force_encoding("UTF-8")  # erroneously returns as ASCII-8bit
  end

  # final sanity check
  bibtex = cleanup_bibtex_string(bibtex)
  raise unless bibtex.index("author")

  return bibtex
end

def get_pdf_from_refpage
  bibtex = try {get_bibtex_from_page}
  fail "Could not read BibTeX metadata from page" unless bibtex

  url = try { bibtex.scan(/url = \{(.+?)\}/)[0][0] }
  fail "Citation does not have a linked Open Access PDF" unless url
  dlpath = "/tmp/pdftmp.pdf"
  growl "Attempting to automatically download the linked PDF"

  dl_file(url, "/tmp/pdftmp.pdf", PDF_content_types)
  return dlpath
end

# opens a given reference as passed by skimx:// URL in Skim
# launched by skimx:// url in Chrome (skimx.app must be registered first)
def url(argv)
  require 'uri'

  arg = argv[8..-1]
  arg.gsub!("#","%23")
  pdf, page = arg.split("%23")

  # check if this is my page, or someone else's
  if My_domains.index( URI.parse(cururl).host )
    fname = "#{PDF_path}/#{pdf}.pdf"
  else
    fname = try { get_pdf_from_refpage }
    fail "Not able to automatically download PDF" unless fname
  end

  if File.exists?(fname)
    skim = Appscript.app('skim')
    dd = skim.open(fname)
    dd.go({:to => dd.pages.get[page.to_i-1]}) unless page == nil
    skim.activate
  else
    growl("File not found", "Cannot find PDF #{fname}")
  end
end

#### keyboard commands ####

# if Ctrl+Cmd+Alt+G is invoked, and current tab is not Google Scholar, assume that it is a foreign wiki, and try to
# import citation to BibDesk
def import_bibtex
  #fail "This page is not a Researchr wiki, cannot import citation" unless cururl.downcase.index("/ref:") || cururl.downcase.index("herokuapp")

  bibtex_final = try {get_bibtex_from_page}
  fail "Could not extract BibTeX citation from this page" unless bibtex_final

  bibdesk = Appscript.app("BibDesk")
  bibdesk.activate
  document = bibdesk.document.get[0].import({:from => bibtex_final})
  citekey = document[0].cite_key.get

  if bibtex_final.scan(/url \= \{(.+?)\}/)
    fname = $~[1]
    exit unless fname.index("http")
    growl "Attempting to automatically download and link PDF..."
    `rm "/tmp/pdftmp.pdf"`

    try { dl_file(fname, "/tmp/pdftmp.pdf", PDF_content_types) }
    # unless File.size?("/tmp/pdftmp.pdf") && Proxy_url != ''
    #   fname.gsub!(/^(.+?)\:\/\/(.+?)\/(.+?)$/,"\1://\2.#{Proxy_url}/\3")
    #   try { dl_file(fname, "/tmp/pdftmp.pdf", "application/pdf") }
    # end
    unless File.size?("/tmp/pdftmp.pdf")
      fail "Not able to download file from #{fname}"
    end

    d = bibdesk.search({:for=>citekey})
    f = MacTypes::FileURL.path('/tmp/pdftmp.pdf')
    d[0].linked_files.add(f,{:to =>d[0]})
    d[0].auto_file

    growl("PDF added", "File added successfully to #{citekey}")
  end
end

# adds the currently selected page to RSS feed, adds data to a temp file, will be formatted next time bibtex-batch
# is executed (Ctrl+Alt+Cmd+F)
def add_to_rss
  require 'open-uri'
  require 'cgi'

  fname = Wiki_path + "/rss-temp"

  internalurl = cururl.split("/").last
  url = "#{Internet_path}/#{internalurl}"

  # load existing holding file, or start form scratch
  if File.exists?(fname)
    rss_entries = Marshal::load(File.read(fname))
  else
    rss_entries = Array.new
  end

  page_contents = open("http://localhost/wiki/#{internalurl}?vecdo=print").read
  contents = page_contents.scan(/<\!\-\- start rendered wiki content \-\-\>(.+?)\<\!\-\- end rendered wiki content \-\-\>/m)[0][0]

  contents.gsub!(/\<div class\=\"hiddenGlobal(.+?)\<div class\=\"plugin_include_content/m, '<div ')

  # remove title (already given in metadata)
  contents.remove!(
    /\<h1 class\=\"sectionedit1\"\>(.+?)\<\/a\>\<\/h1\>/,
    /\<\!\-\- TOC START \-\-\>(.+?)\<\!\-\- TOC END \-\-\>/m,
    /\<span class\=\"tip\"\>(.+?)\<\/span\>/,                                                # remove citation tooltips
    /\<div class\=\"plugin\_include\_content\ plugin\_include\_\_clip(.+?)\<\/div\>/m,       # remove wiki clippings
    /\<div class\=\"plugin\_include\_content\ plugin\_include\_\_kindle(.+?)\<\/div\>/m
  )

  title = page_contents.scan(/\<h1(.+?)id(.+?)>(.+)\<(.+?)\<\/h1\>/)[0][2]
  title = CGI.unescapeHTML(title)

  entry_contents = {:title => title, :date => Time.now, :link => url, :description => contents}

  exists = false

  rss_entries.map! do |entry|
    if entry[:link] == url
      exists = true
      entry_contents
    else
      entry
    end
  end

  unless exists
    rss_entries << entry_contents
  end

  rss_entries = rss_entries.drop(1) if rss_entries.size > 15

  File.write(fname, Marshal::dump(rss_entries))

  if exists
    growl("Article updated", "Article #{title} updated")
  else
    growl("Article added to feed", "'#{title}' added to RSS feed")
  end
end

# pops up dialogue box, asking where to send text, takes selected text (or just link, if desired) and inserts at the bottom
# of the selected page, with a context-relevant reference to original source
def do_clip(pagename, titletxt, onlytext = false)
  pagepath = ("#{Wiki_path}/data/pages" + "/" + clean_pagename(pagename) + ".txt").gsub(":","/")

  curpage = cururl.split("/").last

  sel = has_selection

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

  insert = (sel ? sel : "  *" )

  if File.exists?(pagepath)
    f = File.read(pagepath)
    growltext = "Selected text added to #{pagename}"
  else
    f = "h1. "+ capitalize_word(pagename) + "\n\n"
    growltext = "Selected text added to newly created #{pagename}"
  end
  filetext = f + "\n----\n" + insert.gsubs({:all_with=> "\n\n"}, "\n", "\n\n\n") + " " + curpage

  dwpage(pagename, filetext)
  growl("Text added", growltext)
end

def clip
  require 'pashua'
  title = curtitle

  # asks for a page name, and appends selected text on current page to that wiki page, with proper citation
  pagetmp = wikipage_selector("Which wikipage do you want to add text to?",true, "
  ob.type = checkbox
  ob.label = do not include citation information, only insert pure text
  fb.type = textbox
  fb.default = #{title.strip}
  fb.label = Link title\n"
  )

  exit if pagetmp["cancel"] == 1
  onlytext = pagetmp['ob'] == "1" ? true : false
  pagename = pagetmp['cb'].strip
  pashua_title = pagetmp['fb'].strip
  filetitle = (title.strip == pashua_title) ? nil : pashua_title
  File.write("/tmp/dokuwiki-clip.tmp","#{pagename}\n#{cururl}\n#{filetitle}\n#{onlytext.to_s}")
  do_clip(pagename, title, onlytext)
end

# uses info stored in temp file to do a clipping from the same page, to the same page
def clip_again
  a = File.read("/tmp/dokuwiki-clip.tmp")
  page, url, title, onlytext_s = a.split("\n")
  onlytext = (onlytext_s == 'true') ? true : false
  if title.strip == "" || url != cururl
    title = curtitle
  end
  do_clip(page, title, onlytext)
end

# cleans up a text into bulleted list, either separated by commas or by line shifts
# there is quite a lot of black magic and guessing in here, a wonder it mostly works
def bulletlist
  b = pbpaste
  a = b.remove(/^[\t]*\*/) # strip off bullet etc from beginning

  if a.scan("\n").size > 1  # determine whether to split on newline, space or comma
    splt = "\n"
  elsif a.scan(")").size > a.scan("(").size + 2
    splt = ")"
    a.gsub!(/[, ]*\d+\)/,")")
  elsif a.scan(";").size > 1
    splt = ";"
  elsif a.scan(".").size > 2
    splt = "."
  elsif a.scan("?").size > 2
    splt = "?"
  elsif a.scan(",").size < 1
    splt = " "
  else
    splt = ","
  end

  splits = a.split(splt)

  # deal with situation where the last two items are delimited with "and", but not for line shift or 1) 2) kind of lists
  if splits.last.index(" and ") && !(splt == "\n" || splt == ")")
    x,y = splits.last.split(" and ")
    splits.pop
    splits << x
    splits << y
  end

  out = ''
  splits.each do |item|
    i = item.remove(
      ", and",
      /[\.\*]/,
      /^ *and /,
      /\.$/,
      "•",""
      ).strip
    out << "  * #{i}\n" if i.size > 0
  end

  puts out
end

# Present a wiki page selector and open the page selected
def go
  require 'pashua'
  pagetmp = wikipage_selector("Jump to which page?")
  exit unless pagetmp
  @chrome.windows[1].get.tabs[@chrome.windows[1].get.active_tab_index.get].get.URL.set("http://localhost/wiki/#{pagetmp}")
end

# Moves last screenshot to DokuWiki media folder, and inserts a link to that image properly formatted
def image
  wiki = cururl[22..-1]
  w,dummy = wiki.split("?")
  wikipage = w.gsubs({:all_with => "_"}, ":", "%3A", "%20").downcase
  curfile =  File.last_added("#{Home_path}/Desktop/Screen*.png") # this might be different between different OSX versions

  if curfile == nil
    growl("No screenshots available")
    exit
  end

  newfilename, pagenum = filename_in_series("#{Wiki_path}/data/media/pages/#{wikipage}",".png")
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
    url = cururl.to_s + "?do=edit&vecdo=print"
  else
    # uses Instapaper to nicely format the article text, for fitting into a split-screen window
    url = "http://www.instapaper.com/text?u=\"+encodeURIComponent(\"#{cururl}\")+\""
  end
  newurl = "http://localhost/wiki/#{page.gsub(" ","_")}"

  js = "var MyFrame=\"<frameset cols=\'*,*\'><frame src=\'#{url}\'><frame src=\'#{newurl}?do=edit&vecdo=print\'></frameset>\";with(document) {    write(MyFrame);};return false;"
  @chrome.windows[1].get.tabs[@chrome.windows[1].get.active_tab_index.get].get.execute(:javascript => js)
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

@chrome = Appscript.app('Google Chrome')
send *ARGV unless ARGV == []
