# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript
arg = ARGV[0][10..-1]

# opens a given reference as passed by bibdesk:// URL in BibDesk

dt = app('Bibdesk')
dd = dt.open(Bibliography)
find = dt.search({:for => arg})
unless find == []
  find[0].show
  dt.activate
else
  growl("File not found", "Cannot find citation #{ARGV[0]}")
end
