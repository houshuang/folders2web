# encoding: UTF-8
$:.push(File.dirname($0))
require 'open-uri'
require 'utility-functions'

# grab from clipboard, either look up DOI through API, or 
# use anystyle parser to convert text to bibtex. Paste to clipboard.

def lookup_doi(doi, crossref_api_key)
	doi = doi.downcase.gsub('doi:','').gsub('http://','').gsub('dx.doi.org/','').gsub('doi>','').gsub('doi ','').strip
  url = "http://dx.doi.org/#{doi}"
	return open(url, "Accept" => "text/bibliography; style=bibtex").read
end

search = pbpaste
if search.strip[0..2] == "doi"
  bibtex = lookup_doi(search, Crossref_API)
  growl "Failure", "DOI lookup not successful" unless bibtex
else
  require 'anystyle/parser'
  bibtex = Anystyle.parse(search, :bibtex).to_s
end
pbcopy (bibtex)