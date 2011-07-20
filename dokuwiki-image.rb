# encoding: UTF-8
$:.push(File.dirname($0))
require 'appscript'
include Appscript
require 'utility-functions'

# Moves last screenshot to DokuWiki media folder, and inserts a link to that image properly formatted 

dt = app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get
wiki = cururl[22..-1]
w,dummy = wiki.split("?")
wikipage = w.gsub(":","_").gsub("%3A","_").gsub("%20","_").downcase
c = 1
curfile =  File.last_added("#{Home_path}/Desktop/Screen Shot*")

if curfile == nil
  growl("No screenshots available")
  exit
end

existingfile =  File.last_added("#{Wikimedia_path}/#{wikipage}*.png")
if existingfile
  c = existingfile.scan(/(..)\.png/)[0][0].to_i 
  c += 1
end

pagenum = c.to_s
pagenum = "0" + pagenum if pagenum.size == 1
newfilename = "#{Wikimedia_path}/#{wikipage}#{pagenum}.png"
if File.exists?(newfilename)
  growl("Error!", "File already exists, aborting!")
  exit
end
`mv "#{curfile.strip}" "#{newfilename}"`
`touch "#{newfilename}"`

puts "{{pages:#{wikipage}#{pagenum}.png}}"
