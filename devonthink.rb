require 'utility-functions'
require 'appscript'
include Appscript

# grabs the name of the selected file, chops off the extension, and opens the relevant reference in DokuWiki

name = app('DevonThink Pro').selection.get[0].name.get
ref, ext = name.split(".")
`open http://localhost/wiki/ref:#{ref}`