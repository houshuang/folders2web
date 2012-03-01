# encoding: UTF-8
# utility functions for researchr
$:.push(File.dirname($0))
Bibliography_header = "h1. Bibliography\n\n
Also see bibliography by [[abib:start|author]] or by [[kbib:start|keyword]].\n\n
Publications that have their own pages are listed on top, and hyperlinked. Most of these also have clippings and many have key ideas.\n\n"
Home_path = ENV['HOME']
Script_path = File.dirname(__FILE__)

require 'settings' if File.exists?("#{Script_path}/settings.rb")

# comment the three next lines to use your own gems, instead of the frozen ones, if you don't have OSX 10.7
# or there are other errors with incompatible libraries etc
# Dir.glob(File.join(File.dirname($0), "vendor", "gems", "*", "lib")).each do |lib|
#   $LOAD_PATH.unshift(File.expand_path(lib))
# end

# shows notification on screen. one or two arguments, if one, just shows a message, if two, the first is the title
# notice the path to growl
def growl(title,text='')
  if text == ''
    text = title
    title = ''
  end
  `#{Script_path}/growlnotify -t "#{title}" -m "#{text}"`
end

def log(text)
  File.append("#{Script_path}/log.txt",text)
end

# a new bibtex filter to recapitalize names with proper unicode
require 'bibtex'
class Fix_namecase < BibTeX::Filter
  def apply(field)
    require 'namecase'
    temp = NameCase(Unicode::capitalize(field))

    # fix problem of initials without space between
    temp.to_s.gsub(/\.([A-Za-z])/, '. \1')
  end
end

# providesa new function for bibtex entries to generate a nice looking citekey
module BibTeX
  class Entry
    def std_key
      require 'iconv'
      k = names[0]
      k = k.respond_to?(:family) ? k.family : k.to_s
      cstr = Iconv.conv('us-ascii//translit', 'utf-8', k)
      cstr << (has_field?(:year) ? year : '')
      t = title.dup.split.select {|f| f.size > 3}[0]
      cstr << t ? t : ''
      cstr = cstr.downcase.gsub(/[^a-zA-Z0-9\-]/, '')
      return cstr
    end
  end
end

# cleanup bibtex for BibDesk, convert names, clean key etc
def cleanup_bibtex_string(cit)
  require 'latex/decode'
  cit.gsub!(/\@(.+?)\{(.+?)\,(.+?)$/m, '@\1{key,\3')
  b = BibTeX::parse(cit, :filter => :latex)
  b.parse_names
  b[0][:author].convert!(:fix_namecase)
  b[0].key = b[0].std_key
  return b.to_s
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
      File.open(filename,"a") {|f| f << text + "\n"}
    end

    # find the last file added in directory
    def last_added(path)
      path += "*" unless path.index("*")
      Dir[path].select {|f| test ?f, f}.sort_by {|f|  File.mtime f}.pop
    end

    def replace(path, before, after, newpath = "")
      a = File.read(path)
      a.gsub!(before, after)
      newpath = path if newpath == ""
      File.write(newpath, a)
    end
  end
end

def dl_file(full_url, to_here, require_type = false)
  require 'open-uri'
  writeOut = open(to_here, "wb")
  url = open(full_url)
  if require_type
    raise NameError if url.content_type.strip.downcase != require_type
  end
  writeOut.write(url.read)
  writeOut.close
end


# writes text to clipboard, using a pipe to avoid shell mangling
# rewritten using osascript for better UTF8 support (from http://www.coderaptors.com/?action=browse&diff=1&id=Random_tips_for_Mac_OS_X)
def pbcopy(text)
  IO.popen("osascript -e 'set the clipboard to do shell script \"cat\"'","w+") {|pipe| pipe << text}
end

# gets text from clipboard
def pbpaste
  IO.popen("osascript -e 'the clipboard as unicode text' | tr '\r' '\n'", 'r+').read.strip
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
  cb.completion = 2
  cb.label = #{title}
  cb.default = start
  cb.width = 220
  cb.tooltip = Choose from the list or enter another name
  db.type = cancelbutton
  db.label = Cancel
  db.tooltip = Closes this window without taking action" + "\n" + additional_code + "\n"

  # insert list of all wiki pages from filesystem into Pashua config
  wpath = "#{Wiki_path}/data/pages/"
  Find.find(wpath) do |path|
    next unless File.file?(path)
    fname = path[wpath.size..-5].gsub("/",":").gsub("_", " ")
    idx = fname.index(":")
    config << "cb.option = #{capitalize_word(fname)}\n" if (path[-4..-1] == ".txt" && path[0] != '_')
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
  return JSON.parse(File.read(Wiki_path+"/lib/plugins/dokuresearchr/json.tmp"))
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

# enables you to do
#   a = Hash.new
#   a.add(:peter,1)
# without checking if a[:peter] has been initialized yet
# works differently for integers (incrementing number) and other objects (adding a new object to array)
class Hash
  def add(var,val)
    if val.class == Fixnum
      if self[var].nil?
        self[var] = val
      else
        self[var] = self[var] + val
      end
    else
      if self[var].nil?
        self[var] = [val]
      else
        self[var] = self[var] + [val]
      end
    end
  end
end

# calculate SHA-2 hash for a given file
def hashsum(filename)
  require 'digest/sha2'
  hashfunc = Digest::SHA2.new
  File.open(filename, "r") do |io|
    counter = 0
    while (!io.eof)
      readBuf = io.readpartial(1024)
      #       putc '.' if ((counter+=1) % 3 == 0)
      hashfunc.update(readBuf)
    end
  end
  return hashfunc.hexdigest
end
