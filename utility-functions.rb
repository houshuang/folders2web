# utility functions for researchr

# constants
Growl_path = "/usr/local/bin/growlnotify"
Wiki_path = "/wiki"
Wikipages_path = "/wiki/data/pages"
Home_path = "/Volumes/Home/stian"
Script_path = "#{Home_path}/src/folders2web"
PDF_path = "#{Home_path}/Documents/Bibdesk"
Bibliography = "#{Home_path}/Dropbox/Archive/Bibliography.bib"
Downloads_path = "#{Home_path}/Downloads"


# shows notification on screen. one or two arguments, if one, just shows a message, if two, the first is the title
# notice the path to growl
def growl(title,text='')
  if text == ''
    text = title
    title = ''
  end
  `#{Growl_path} -t "#{title}" -m "#{text}"`
end


# a few extra file functions
class File
  class << self

    # adds File.write - analogous to File.read, writes text to filename
    def write(filename, text)
      File.open(filename,"w") {|f| f << text}
    end

    # adds File.append - analogous to File.read, writes text to filename
    def append(filename, text)
      File.open(filename,"a") {|f| f << text}
    end

    # find the last file added in directory
    def last_added(path)
      path += "*" unless path.index("*")
      Dir[path].select {|f| test ?f, f}.sort_by {|f|  File.mtime f}.pop
    end

  end
end


# writes text to clipboard, using a pipe to avoid shell mangling
def pbcopy(text)
  IO.popen("pbcopy","w+") {|pipe| pipe << text}
end


def pbpaste
  IO.popen('pbpaste', 'r+').read
end


# runs pagename through php file from DokuWiki to generate a clean version
def clean_pagename(pname)
  return IO.popen("php #{Script_path}/clean_id.php '#{pname}'", 'r+').read.strip
end


# show GUI selector listing all wiki pages, and letting user choose one, or manually enter a new one
def wikipage_selector(title)
  require 'find'
  require 'pashua'
  include Pashua
  
  config = "
  cb.type = combobox 
  cb.label = #{title}
  cb.default = start 
  cb.width = 220 
  cb.tooltip = Choose from the list or enter another name
  db.type = cancelbutton
  db.label = Cancel
  db.tooltip = Closes this window without taking action"

  # insert list of all wiki pages from filesystem into Pashua config
  Find.find(Wikipages_path) do |path|
    next unless File.file?(path)
    fname = path[17..-5].gsub("/",":").gsub("_", " ")
    idx = fname.index(":")
    config << "cb.option = #{fname}\n" if (path[-4..-1] == ".txt" && path[0] != '_')
  end
  pagetmp = pashua_run config

  pagetmp['cancel'] == 1 ? nil : pagetmp['cb']
end


# capitalize the first letter of each word
def capitalize_word(text)
  out = Array.new
  text.split(":").each do |t| 
    out << t.split(/ /).each {|word| word.capitalize!}.join(" ") 
  end
  out.join(":")
end


# returns nicely formatted citation for a given citekey (very slow, rather used preparsed json file)
def get_citation(citekey)
  require 'bibtex'
  require 'citeproc'

  b = BibTeX.open(Bibliography)
  b.parse_names
  item = b[citekey.to_sym]
  return CiteProc.process(item.to_citeproc, :style => :apa)
end

def utf8safe(text)
  require 'iconv'
  ic = Iconv.new('UTF-8//IGNORE', 'UTF-8')
  return ic.iconv(text + ' ')[0..-2]
end
  
def dwpage(page, text, msg = "Automatically added text")
  tmp = Time.now.to_i.to_s
  File.write("/tmp/researcher-#{tmp}.tmp", text)
  `/wiki/bin/dwpage.php -m '#{msg}' commit "/tmp/researcher-#{tmp}.tmp" '#{page}'`
end