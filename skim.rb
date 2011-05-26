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
out = "h2. Highlights\n\n"

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

if File.exists?("/tmp/skim-screenshots-tmp")
  a = File.readlines("/tmp/skim-screenshots-tmp")
  out << "\nh2. Images\n\n"
  c = 0
  a.each do |f|
    `mv "#{f.strip}" "/wiki/data/media/skim/#{Citekey}#{c.to_s}.png"`
    out << "{{skim:#{Citekey}#{c.to_s}.png}}\n\n"
    c += 1
  end
  `rm "/tmp/skim-screenshots-tmp"`
end
File.open("/tmp/skimtmp", "w") {|f| f << out}

`/wiki/bin/dwpage.php -m 'Automatically extracted from Skim' commit /tmp/skimtmp 'clip:#{docu}'`

ensure_refpage(docu)
make_newimports_page([docu])
`open http://localhost/wiki/ref:#{docu}`