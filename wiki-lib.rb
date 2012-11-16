# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'bibtex'
require 'citeproc'
require 'appscript'
include Appscript


# clean braces
def clean_braces(text)
  # clean braces
  text.gsub(/[\{\|\}]/,'')
end

# ensure that main refpage exists for a given citekey, taking info from BibDesk (needs to be running,
# maybe replace with taking info from Bibtex file eventually)
def ensure_refpage(citekey,override=false)
  if  true#!File.exists?("/wiki/data/pages/ref/#{citekey}.txt") || override
    dt = app('Bibdesk')
    find = dt.document.search({:for => citekey})
    if find == []
      growl("File not found", "Cannot find citation #{citekey} in BibDesk, the citekey in BibDesk needs to be the same as the name of the PDF.")
      raise
    end

    bibstring = find[0].BibTeX_string.get
    item = BibTeX.parse(bibstring)[0]

    oa_url = item[:"oa-url"]
    title = item[:title]

    header = "h1. #{clean_braces(title)}"
    citation = clean_braces( CiteProc.process(item.to_citeproc, :style => :apa) )

    bibdesk = "[[bibdeskx://#{citekey}|BibDesk]]"
    skim = "[[skimx://#{citekey}|Skim]]"

    sidewiki_js = "javascript:var MyFrame='<frameset cols=\\'*,*\\'><frame src=\\'/wiki/notes:#{citekey}?do=edit&vecdo=print\\'><frame src=\\'/wiki/clip:#{citekey}?vecdo=print\\'></frameset>';with(document) {    write(MyFrame);};return false;\""
    sidewiki = "<html><a href=\"#{sidewiki_js}\">Sidewiki</a></html>"

    page_includes = "{{page>notes:#{citekey}}}\n\nh2. Links here\n{{backlinks>.}}\n\n{{page>clip:#{citekey}}}\n\n{{page>kindle:#{citekey}}}\n\n{{page>skimg:#{citekey}}}"

    hidden_bibtex = "<hidden BibTex>\n  #{item.to_s}\n</hidden>"

    puts "OA" if oa_url

    download_img = if oa_url
      "[[#{oa_url}|{{ wiki:downloadbutton.png}}]]"
    else
      ""
    end

    puts download_img

    # this is the format of the header, feel free to modify as you see fit. The text between the first ifauth is shown
    # to not logged in users, between the second ifauth to logged in users (you)
    out = "
#{header}
#{download_img}
<ifauth !@admin>|#{citation}|</ifauth>
<ifauth @admin>
^ #{bibdesk}  | #{citation} |
^ #{skim}     | ::: |
^ #{sidewiki} | ::: |
</ifauth>
#{hidden_bibtex}

#{page_includes}
"

    dwpage("ref:#{citekey}", out.strip, 'Automatically generated from Bibdesk')

    Thread.new { try {submit_citation(item.to_s)} }
  end
end

def make_newimports_page(ary)
  # b = BibTeX.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
  # b.parse_names
  #
  # out = "h1. Recently imported items\n\n^Note name ^ Note text ^\n"
  # ary.each do |citekey|
  #   item = b[citekey.to_sym]
  #   cit = CiteProc.process item.to_citeproc, :style => :apa
  #   out << "| [[:ref:#{item.key}]] | #{cit}|\n"
  # end
  #
  # File.open('/wiki/data/pages/bib/recent_imports.txt', 'w') {|f| f << out}
end
