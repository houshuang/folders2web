# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
require 'anystyle/parser'

search = utf8safe(pbpaste)
if search.index("doi:")
  require 'doi-lookup/doi-bibtex.rb'
  bibtex = lookup_doi(search, Crossref_API)
  growl "Failure", "DOI lookup not successful" unless bibtex
else
  bibtex = Anystyle.parse(search, :bibtex).to_s
end
pbcopy (bibtex)