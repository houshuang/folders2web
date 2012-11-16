# execute ruby script from ruby:// handler
# currently used for newauthor (generate template for new author page) and callback from growl to preview_iphone_image
$:.push(File.dirname($0))
require 'utility-functions'

arg = ARGV[0][7..-1]
arg.gsub!(".rb",'')

`ruby -KU /Users/Stian/src/folders2web/dokuwiki.rb #{arg}`