# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript

# Moves last screenshot to DokuWiki media folder, and inserts a link to that image properly formatted 

dt = app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get
wiki = cururl[22..-1]
w,dummy = wiki.split("?")
wikipage = w.gsub(":","_").gsub("%3A","_").gsub("%20","_").downcase
curfile =  File.last_added("#{Home_path}/Desktop/Screen Shot*")

if curfile == nil
  growl("No screenshots available")
  exit
end

newfilename, pagenum = filename_in_series("#{Wikimedia_path}/#{wikipage}",".png")
if File.exists?(newfilename)
  growl("Error!", "File already exists, aborting!")
  exit
end
`mv "#{curfile.strip}" "#{newfilename}"`
`touch "#{newfilename}"`

puts "{{pages:#{wikipage}#{pagenum}.png}}"
