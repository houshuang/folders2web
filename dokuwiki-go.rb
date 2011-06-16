# encoding: UTF-8

# Add a text field 
$:.push(File.dirname($0))
require 'find'
require 'Pashua'
include Pashua

config = <<EOS
cb.type = combobox 
cb.label = Jump to which page?
cb.default = start 
cb.width = 220 
cb.tooltip = Choose from the list or enter another name
db.type = cancelbutton
db.label = Cancel
db.tooltip = Closes this window without taking action
EOS
Find.find("/wiki/data/pages") do |path|
  next unless File.file?(path)
  config << "cb.option = #{path[17..-5].gsub("/",":").gsub("_", " ")}\n" if (path[-4..-1] == ".txt" && path[0] != '_')
end
pagetmp = pashua_run config
exit if pagetmp["cancel"] == 1
page = pagetmp["cb"]
`open http://localhost/wiki/#{page.gsub(" ","_")}`