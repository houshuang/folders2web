# execute ruby script from ruby:// handler
arg = ARGV[0][7..-1]
arg.gsub!(".rb",'')

`ruby -KU /Users/stian/src/folders2web/dokuwiki.rb #{arg}`