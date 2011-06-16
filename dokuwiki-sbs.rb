# encoding: UTF-8

# Add a text field 
$:.push(File.dirname($0))
require 'find'
require 'Pashua'
include Pashua
require 'appscript'
include Appscript
dt=app('Google Chrome')

config = <<EOS
cb.type = combobox 
cb.label = Which page to edit side-by-side with this page?
cb.default = start 
cb.width = 220 
cb.tooltip = Choose from the list or enter another name
db.type = cancelbutton
db.label = Cancel
db.tooltip = Closes this window without taking action
EOS
Find.find("/wiki/data/pages") do |path|
  next unless File.file?(path)
  config << "cb.option = #{(path[17..-5].gsub("/",":").gsub("_", " "))}\n" if (path[-4..-1] == ".txt" && path[0] != '_')
end
pagetmp = pashua_run config
exit if pagetmp["cancel"] == 1
page = pagetmp["cb"]
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get
if cururl.index("localhost/wiki")
  cururl = cururl.to_s + "?do=edit&vecdo=print"
end
newurl = "http://localhost/wiki/#{page.gsub(" ","_")}"
js = "var MyFrame=\"<frameset cols=\'*,*\'><frame src=\'#{cururl}\'><frame src=\'#{newurl}?do=edit&vecdo=print\'></frameset>\";with(document) {    write(MyFrame);};return false;"
dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.execute(:javascript => js)