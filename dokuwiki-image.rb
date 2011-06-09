# encoding: UTF-8
require 'pp'
require 'appscript'
include Appscript

dt = app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get
wiki = cururl[22..-1]
w,dummy = wiki.split("?")
p w
wikipage = w.gsub(":","_").gsub("%3A","_").downcase
c = 1
curfile =  Dir["/Volumes/Home/stian/Desktop/Screen shot*"].select {|f| test ?f, f}.sort_by {|f|  File.mtime f}.pop 

if curfile == nil
  `/usr/local/bin/growlnotify -m "No screenshots available"`
  exit
end

existingfile =  Dir["/wiki/data/media/pages/#{wikipage}#*.png"].select {|f| test ?f, f}.sort_by {|f|  File.mtime f}.pop 
if existingfile
  puts existingfile
  c = existingfile.scan(/\#(.)\.png/)[0][0].to_i 
  puts c
  c += 1
  puts c
end

`mv "#{curfile.strip}" "/wiki/data/media/pages/#{wikipage}#{c.to_s}.png"`
`touch "/wiki/data/media/pages/#{wikipage}#{c.to_s}.png"`

puts "{{pages:#{wikipage}#{c.to_s}.png}}"
