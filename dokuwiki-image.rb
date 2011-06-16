# encoding: UTF-8
$:.push(File.dirname($0))
require 'pp'
require 'appscript'
include Appscript
require 'utility-functions'

dt = app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get
wiki = cururl[22..-1]
w,dummy = wiki.split("?")
wikipage = w.gsub(":","_").gsub("%3A","_").gsub("%20","_").downcase
c = 1
curfile =  File.last_added("/Volumes/Home/stian/Desktop/Screen shot*")

if curfile == nil
  growl("No screenshots available")
  exit
end

existingfile =  File.last_added("/wiki/data/media/pages/#{wikipage}#*.png")
if existingfile
  c = existingfile.scan(/\#(.)\.png/)[0][0].to_i 
  c += 1
end

`mv "#{curfile.strip}" "/wiki/data/media/pages/#{wikipage}#{c.to_s}.png"`
`touch "/wiki/data/media/pages/#{wikipage}#{c.to_s}.png"`

puts "{{pages:#{wikipage}#{c.to_s}.png}}"
