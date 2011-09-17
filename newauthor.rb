# encoding: UTF-8

# Add a text field 
$:.push(File.dirname($0))
require 'utility-functions'
require 'Pashua'
include Pashua

config = <<EOS
*.title = Add a new author page
cb.type = textfield 
cb.label = Name of author page to create
cb.width = 220 
db.type = cancelbutton
db.label = Cancel
db.tooltip = Closes this window without taking action
EOS
pagetmp = pashua_run config
puts pagetmp
exit if pagetmp["cancel"] == 1
page = pagetmp["cb"]
pname = "/wiki/data/pages/a/#{clean_pagename(page)}.txt"
File.open(pname,"w") {|f| f<<"h1. #{page}\n\nh2. Research\n\nh2. Links\n  * [[ |Homepage]]
\n{{page>abib:#{page}}}"}
`chmod a+rw "#{pname}"`
`open "http://localhost/wiki/a:#{page}?do=edit"`
