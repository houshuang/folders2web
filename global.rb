# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'

# Contains keyboard related functionality which can be invoked from any publication

# triggered through Cmd+. shows a Pashua list of all references with titles
# upon selection, a properly formatted citation like [@scardamalia2004knowledge] is inserted
def bib_selector
  require 'pashua'
  include Pashua

  bib = json_bib

  config = "
  *.title = researchr
  cb.type = combobox
  cb.completion = 2
  cb.label = Insert a citation
  cb.width = 800
  cb.tooltip = Choose from the list or enter another name
  db.type = cancelbutton
  db.label = Cancel
  db.tooltip = Closes this window without taking action"

  # create list of citations
  out = ''
  json_bib.sort.each do |a|
    out << "cb.option = #{a[0]}: #{a[1][3][0..90]}\n"
  end

  # show dialogue
  pagetmp = pashua_run config + out

  exit if pagetmp['cancel'] == 1

  /^(?<citekey>.+?)\:/ =~ pagetmp['cb']  # extract citekey from citekey + title string

  pbcopy("[@#{citekey}]")
end

# grab from clipboard, either look up DOI through API, or
# use anystyle parser to convert text to bibtex. Paste to clipboard.
def anystyle_parse
  search = pbpaste
  if search.downcase[0..2] == "doi" || (search =~ /^10\./ && !search.strip.index(" "))
    bibtex = doi_to_bibtex(search)
    growl "Failure", "DOI lookup not successful" unless bibtex
  else
    require 'anystyle/parser'
    search = search.gsub("-\n", "").gsub("\n", " ")
    bibtex = Anystyle.parse(search, :bibtex).to_s
  end

  pbcopy(cleanup_bibtex_string(bibtex))
end

send *ARGV unless ARGV == []