# execute ruby script from ruby:// handler

arg = ARGV[0][7..-1]
`ruby -KU /Volumes/Home/stian/src/folders2web/#{arg}`