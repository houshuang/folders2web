# encoding: UTF-8
$:.push(File.dirname($0))
require 'open-uri'
require 'utility-functions'

# grab from clipboard, either look up DOI through API, or
# use anystyle parser to convert text to bibtex. Paste to clipboard.

def lookup_doi(doi)
	doi = doi.downcase.remove(/doi[:>]/,'http://','dx.doi.org/').strip
  url = "http://dx.doi.org/#{doi}"
	return open(url, "Accept" => "text/bibliography; style=bibtex").read
end

search = pbpaste
if search.strip.downcase[0..2] == "doi"
  bibtex = lookup_doi(search)
  growl "Failure", "DOI lookup not successful" unless bibtex
else
  require 'anystyle/parser'
  search = search.gsub("-\n", "").gsub("\n", " ")
  bibtex = Anystyle.parse(search, :bibtex).to_s
end
pbcopy(cleanup_bibtex_string(bibtex))