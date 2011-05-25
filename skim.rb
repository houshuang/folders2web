# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"
require 'pp'
require 'rubygems'
require 'appscript'
require curpath + 'wiki-lib'
include Appscript

# grabs the name of the currently open Skim file, uses skimnotes to extract notes and highlights to a text file,
# and inserts this as a page, using the filename as a page name, in DokuWiki. intended to be turned into a service.

dt = app('Skim')
dt.save(dt.document)
docu = dt.document.name.get[0][0..-5]
`/Applications/Skim.app/Contents/SharedSupport/skimnotes get -format text #{dt.document.file.get[0].to_s}`

# format notes
a = File.readlines("/Volumes/Home/stian/Documents/Bibdesk/#{docu}.txt") 
text=''
lineno=nil
Citekey = docu
type = ""
out = "h1. Highlights\n\n"

def format(text,page,type)
  highlight = (type == "Text Note" ? "::" : "")
  return "#{highlight}#{text.strip}#{highlight} [[skimx://#{Citekey}##{page}|p. #{page}]]\n\n"
end

a.each do |line|
  # puts "###{line}"
  if line =~ /^\* (Highlight|Text Note), page (.+?)$/
    if lineno != nil
      out <<  format(text,lineno,type)      
    end
    lineno = $2.to_i
    type = $1
    text = ''
  else
    text << line
  end
end
highlight = (type == "Text Note" ? "**" : "")
out << format(text,lineno,type)

File.open("/tmp/skimtmp", "w") {|f| f << out}

`/wiki/bin/dwpage.php -m 'Automatically extracted from Skim' commit /tmp/skimtmp 'clip:#{docu}'`

ensure_refpage(docu)
make_newimports_page([docu])
`open http://localhost/wiki/ref:#{docu}`