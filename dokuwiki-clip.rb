# encoding: UTF-8

# Add a text field 
$:.push(File.dirname($0))
require 'appscript'
include Appscript
require 'find'
require 'Pashua'
include Pashua
require 'iconv'

def titleize(page)
  return page.split(':').last.gsub("_", " ").split(/ /).each{|word| word.capitalize!}.join(" ")
end

config = <<EOS
cb.type = combobox 
cb.label = Which wiki page do you want to add text to?
cb.default = start 
cb.width = 220 
cb.tooltip = Choose from the list or enter another name
db.type = cancelbutton
db.label = Cancel
db.tooltip = Closes this window without taking action
EOS
Find.find("/wiki/data/pages") do |path|
  next unless File.file?(path)
  config << "cb.option = #{titleize(path[17..-5].gsub("/",":").gsub("_", " "))}\n" if (path[-4..-1] == ".txt" && path[0] != '_')
end
pagetmp = pashua_run config
exit if pagetmp["cancel"] == 1

page = pagetmp["cb"]
page = page + ".txt" 

filename = "/wiki/data/pages/#{page.gsub(":","/").gsub(" ","_")}"
page = page[0..-5]

dt=app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get
curpage = cururl.split("/").last

# format properly if citation
if curpage.index("ref:")
  curpage = "[@#{curpage.split(':').last}]" 
elsif cururl.index("localhost/wiki")
  curpage = "[[#{curpage}|#{titleize(curpage)}]]"
else
  title = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.title.get
  curpage ="[[#{cururl}|#{title[0..50]}]]"
end

insert_untrusted = IO.popen('pbpaste', 'r+').read
ic = Iconv.new('UTF-8//IGNORE', 'UTF-8')
insert = ic.iconv(insert_untrusted + ' ')[0..-2]


if File.exists?(filename)
  f = File.read(filename) + "\n\n<html><hr></html>\n\n"
  growl = "Selected text added to #{page}"
else
  f = "h1. "+titleize(page) + "\n\n"
  growl = "Selected text added to newly created #{page} #{filename}"
end

File.open("/tmp/insert-tmp","w") {|tmpf| tmpf << "#{f}#{insert.gsub("\n","\n\n").gsub("\n\n\n","\n\n")} #{curpage}"}
`/wiki/bin/dwpage.php -m 'Automatically added text' commit /tmp/insert-tmp '#{page}'`
`/usr/local/bin/growlnotify -t "Text added" -m "#{growl}"`