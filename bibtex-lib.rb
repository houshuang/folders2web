# encoding: UTF-8
require 'rubygems'
require 'bibtex'
require 'citeproc'
require 'pp'

def get_citation(citekey)
  b = BibTeX.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
  b.parse_names
  item = b[citekey.to_sym]
  return CiteProc.process(item.to_citeproc, :style => :apa)
end

#pp get_citation("hoadley1999between")