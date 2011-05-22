# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"

require 'bibtex'
require 'citeproc'
require 'appscript'
include Appscript


# clean braces
def cb(text)
  # clean braces
  text.gsub(/[\{|\}]/,'')
end

# ensure that main refpage exists for a given citekey, taking info from BibDesk (needs to be running,
# maybe replace with taking info from Bibtex file eventually)
def ensure_refpage(citekey)
  unless false  #File.exists?("/wiki/data/pages/ref/#{citekey}.txt")
    
    b = BibTeX.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
    b.parse_names
    item = b[citekey.to_sym]
    
    citation = CiteProc.process(item.to_citeproc, :style => :apa)
    citation = "^ Citation | " + citation + " ^ <html><a href=\"javascript:var MyFrame='<frameset cols=\\'*,*\\'><frame src=\\'/wiki/notes:#{citekey}?do=edit&vecdo=print\\'><frame src=\\'/wiki/clip:#{citekey}?vecdo=print\\'></frameset>';with(document) {    write(MyFrame);};return false;\">Sidewiki</a></html>^\n^[[bibdesk://#{citekey}|BibDesk]] | ::: ^  [[skimx://#{citekey}|PDF]] ^ "
    
    File.open('/tmp/bibdesktmp', 'w') {|f| f << "h1. #{cb(item[:title])}\n\n#{citation}\n\n<hidden BibTex>\n  #{item.to_s}\n</hidden>\n\n{{page>notes:#{citekey}}}\n\nh2. Links here\n{{backlinks>.}}\n\n{{page>clip:#{citekey}}}"}  
    `/wiki/bin/dwpage.php -m 'Automatically generated from Bibdesk' commit /tmp/bibdesktmp 'ref:#{citekey}'`
  end
end

#ensure_refpage("scardamalia2006knowledge")
