require 'appscript'
include Appscript

name = app('DevonThink Pro').selection.get[0].name.get
ref, ext = name.split(".")
`open http://localhost/wiki/ref:#{ref}`