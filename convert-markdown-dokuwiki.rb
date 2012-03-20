# encoding: UTF-8

# remember to compile with Transformations...Convert Plain Text to Paragraph Spacing
# small script to convert my style of writing markdown(ish) in Scrivener to dokuwiki

$:.push(File.dirname($0))
require 'utility-functions'

a = File.read(ARGV[0])

# convert to paragraph spacing, if not already the case
a.gsub!(/([^\n])\n([^\n])/m,'\1' + "\n\n" + '\2')

# convert bullet lists with - and tabs to * and spaces
a.gsub!(/^(\t*)- /) { |f| "  " + f.gsubs([/\t/, '  '], ["- ", "* "]) }

# no double-spacing between bullet items
a.gsub!(/\*(.+?)\n\n[^ ]/m) {|f| f.gsub("\n\n", "\n")[0..-2] + "\n" + f[-1] }

# clean up spaces before titles
a.gsub!(/.(h[1-9]\.)/, '\1') # {|f| p f}

File.write(ARGV[0]+".txt", a)