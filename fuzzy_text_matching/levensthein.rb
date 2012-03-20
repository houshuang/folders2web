# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'

require 'text'

if ARGV == []
  f1 = "/Users/Stian/Bibdesk/buckinghamshum2003roots.pdf"
  f2 = "/Users/Stian/Desktop/bck.pdf"
else
  f1 = ARGV[0]
  f2 = ARGV[1]
end

`pdftotext #{f1} /tmp/text1`
`pdftotext #{f2} /tmp/text2`

File.write('/tmp/txt1', utf8safe(File.read('/tmp/text1')).gsub("\n", " "))
File.write('/tmp/txt2', utf8safe(File.read('/tmp/text2')).gsub("\n", " "))
#p Text::Levenshtein.distance(text1, text2) #returns 1
puts `git diff --no-index --word-diff=porcelain /tmp/txt1 /tmp/txt2`
puts `git diff --no-index --word-diff=porcelain /tmp/txt1 /tmp/txt2|wc`