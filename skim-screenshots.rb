# collects screenshots from Skim for later inclusion during skim notes extraction
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript

a = File.open("/tmp/skim-screenshots-tmp","a")
page = app('Skim').document.get[0].current_page.get.index.get

curfile =  File.last_added("/Volumes/Home/stian/Desktop/Screen shot*")
a << "#{curfile},#{page}\n"
growl("One picture added to wiki notes cache")
