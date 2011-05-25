# encoding: UTF-8
curpath = File.dirname(File.expand_path(__FILE__)) + "/"
require 'pp'
require 'rubygems'
require 'appscript'
require curpath + 'wiki-lib'
include Appscript

# grabs the name of the currently open Skim file, uses skimnotes to extract notes and highlights to a text file,
# and inserts this as a page, using the filename as a page name, in DokuWiki. intended to be turned into a service.


dt = app('BibDesk')
d = dt.document.selection.get[0]
dt.document.save
ary = Array.new
d.each do |dd|
  docu = dd.cite_key.get
  ary << docu unless File.exists?("/wiki/data/pages/ref/#{docu}.txt")
  ensure_refpage(docu)
  # open it in the default browser
end
`open http://localhost/wiki/ref:#{d[0].cite_key.get}`
make_newimports_page(ary)

#   
#   @inproceedings{stahl2002contributions,
#   Author = {Stahl, Gerry and Augustin, Sankt},
#   Booktitle = {Proceedings of Computer Supported Collaborative Learning (CSCL 2002)},
#   Pages = {62-71},
#   Title = {{Contributions to a Theoretical Framework for CSCL}},
#   Year = {2002}}
# </hidden> 
# 
# {{page>:notes:stahl2002contributions}}
# 
# {{page>:clip:stahl2002contributions}}
# 
# {{page>:cit:stahl2002contributions}}