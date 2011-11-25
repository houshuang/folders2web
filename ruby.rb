# execute ruby script from ruby:// handler

arg = ARGV[0][7..-1]
arg.gsub!(".rb",'')
`ruby -KU /Volumes/Home/stian/src/folders2web/dokuwiki.rb #{arg}`