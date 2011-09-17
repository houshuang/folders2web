# encoding: UTF-8
# utility functions for researchr
require 'settings'

Dir.glob(File.join(File.dirname($0), "vendor", "gems", "*", "lib")).each do |lib|
  $LOAD_PATH.unshift(File.expand_path(lib))
end

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
  # only send the ones that need it to the external php script
  if pname.downcase =~ /[^0-9a-zA-Z ]/
    # make sure we can manually close the process, otherwise we run out of processes
    ret = ''
    IO.popen("php #{Script_path}/clean_id.php '#{pname}'", 'r+') do |iop|
      iop.close_write
      ret = iop.read
    end
    return ret.strip
  else
    return pname.gsub(" ", "_").downcase
  end
end


# show GUI selector listing all wiki pages, and letting user choose one, or manually enter a new one
def wikipage_selector(title, retfull = false, additional_code = "")
  require 'find'
  require 'pashua'
  include Pashua
  
  config = "
  *.title = researchr
  cb.type = combobox 
  cb.label = #{title}
  cb.default = start 
  cb.width = 220 
  cb.tooltip = Choose from the list or enter another name
  db.type = cancelbutton
  db.label = Cancel
  db.tooltip = Closes this window without taking action" + "\n" + additional_code + "\n"

  # insert list of all wiki pages from filesystem into Pashua config
  Find.find(Wikipages_path) do |path|
    next unless File.file?(path)
    fname = path[17..-5].gsub("/",":").gsub("_", " ")
    idx = fname.index(":")
    config << "cb.option = #{fname}\n" if (path[-4..-1] == ".txt" && path[0] != '_')
  end
  pagetmp = pashua_run config

  pagetmp['cancel'] == 1 ? nil : (retfull ? pagetmp : pagetmp['cb'] )
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

# wrapper around DokuWiki dwpage tool, inserts page into dokuwiki  
def dwpage(page, text, msg = "Automatically added text")
  tmp = Time.now.to_i.to_s
  File.write("/tmp/researcher-#{tmp}.tmp", text)
  `/wiki/bin/dwpage.php -m '#{msg}' commit "/tmp/researcher-#{tmp}.tmp" '#{page}'`
end

# properly format full name, extracted from bibtex
def nice_name(name)
  return "#{name.first} #{name.last}".gsub(/[\{\}]/,"")
end

# properly format list of names for citation
def namify(names)
  return names[0] if names.size == 1
  return names[0] + " et al." if names.size > 3
  names[0..-2].join(", ") + " & " + names[-1].to_s
end

# entire bibliography pre-parsed read in from json
def json_bib()
  require 'json'
  return JSON.parse(File.read(JSON_path))
end

# given a start of a filename, and an end, looks if there are already any files existing with the filename (pre)01(post)
# increments number with one and returns. used to generate filenames like picture01.png picture02.png etc
def filename_in_series(pre,post)
  existingfile =  File.last_added("#{pre}*#{post}")
  if existingfile
    c = existingfile.scan(/(..)#{post}/)[0][0].to_i 
    c += 1
  else
    c = 1
  end

  pagenum = c.to_s
  pagenum = "0" + pagenum if pagenum.size == 1
  return "#{pre}#{pagenum}#{post}", pagenum
end