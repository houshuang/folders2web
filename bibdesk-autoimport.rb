# encoding: UTF-8

# triggered by a watch script, tries to import all PDFs in a folder to BibDesk

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'

growl ARGV[0]