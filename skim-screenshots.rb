# collects screenshots from Skim for later inclusion during skim notes extraction
require 'appscript'
include Appscript

a = File.open("/tmp/skim-screenshots-tmp","a")
page = app('Skim').document.get[0].current_page.get.index.get

curfile =  Dir["/Volumes/Home/stian/Desktop/Screen shot*"].select {|f| test ?f, f}.sort_by {|f|  File.mtime f}.pop 
a << "#{curfile},#{page}\n"
`/usr/local/bin/growlnotify -m "One picture added to wiki notes cache"`
