# encoding: UTF-8
$:.push(File.dirname($0))
require 'wiki-lib'
require 'appscript'
include Appscript

# grabs the name of the currently open Skim file, uses skimnotes to extract notes and highlights to a text file,
# and inserts this as a page, using the filename as a page name, in DokuWiki. intended to be turned into a service.

def process(type, text, page)
   if type == "Underline"
    if @out[-1].index(text.strip)
      @out << @out.pop.gsub(text.strip,"::::#{text.strip}::::")
    else
      type = "Underline-standalone"
    end
  end
  return type == "Underline" ? "" : format(type, text, page) 
end

def format(type, text, page)
  highlight = case type
    when "Text Note"
      "::"
    when "Underline-standalone"
      ":::"
    else
      ""
  end
  return "#{highlight}#{text.strip}#{highlight} [[skimx://#{Citekey}##{page}|p. #{page}]]\n\n"
end

app('BibDesk').document.save
dt = app('Skim')
dt.save(dt.document)
docu = dt.document.name.get[0][0..-5]
`/Applications/Skim.app/Contents/SharedSupport/skimnotes get -format text #{dt.document.file.get[0].to_s}`

# format notes
a = File.readlines("#{PDF_path}/#{docu}.txt") 
`rm "#{PDF_path}/#{docu}.txt"`
Citekey = docu

page = nil
@out = Array.new
@out << "h2. Highlights\n\n"

type = ''
text=''

a.each do |line|
  if line =~ /^\* (Highlight|Text Note|Underline), page (.+?)$/
    if page != nil  # ie. don't execute the first time, only when there is a previous annotation in the system
      @out << process(type, text, page)
    end
    page = $2.to_i
    type = $1
    text = ''
  else
    text << line  # just add the text 
  end
end

@out << process(type, text, page)  # pick up the last annotation

File.open("/tmp/skimtmp", "w") {|f| f << @out.join('')}
`/wiki/bin/dwpage.php -m 'Automatically extracted from Skim' commit /tmp/skimtmp 'clip:#{docu}'`

if File.exists?("/tmp/skim-screenshots-tmp")
  a = File.readlines("/tmp/skim-screenshots-tmp")
  @out = "h2. Images\n\n"
  c = 0
  a.each do |line|
    f,pg = line.split(",")
    `mv "#{f.strip}" "/wiki/data/media/skim/#{Citekey}#{c.to_s}.png"`
    @out << "{{skim:#{Citekey}#{c.to_s}.png}}\n\n[[skimx://#{Citekey}##{pg.strip}|p. #{pg.strip}]]\n----\n\n"
    c += 1
  end
  `rm "/tmp/skim-screenshots-tmp"`
  File.open("/tmp/skimtmp", "w") {|f| f << @out}
  `/wiki/bin/dwpage.php -m 'Automatically extracted from Skim' commit /tmp/skimtmp 'skimg:#{docu}'`
end

app("BibDesk").document.search({:for =>docu})[0].fields["Read"].value.set("1")
ensure_refpage(docu)
dt.save(dt.document)

#make_newimports_page([docu])
`open http://localhost/wiki/ref:#{docu}`