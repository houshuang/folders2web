require 'pp'
require 'rubygems'
require 'appscript'
include Appscript

# grabs the name of the currently open Skim file, uses skimnotes to extract notes and highlights to a text file,
# and inserts this as a page, using the filename as a page name, in DokuWiki. intended to be turned into a service.

dt = app('Skim')
dt.save(dt.document)
docu = dt.document.name.get[0][0..-5]
`/Applications/Skim.app/Contents/SharedSupport/skimnotes get -format text #{dt.document.file.get[0].to_s}`
File.open('/tmp/skimtmp', 'w') {|f| f << "h1. Notes\n\n" << File.read("/Volumes/Home/stian/Documents/Bibdesk/#{docu}.txt").gsub("\n","\n\n") }
`/wiki/bin/dwpage.php -m 'Automatically extracted from Skim' commit /tmp/skimtmp 'clip:#{docu}'`