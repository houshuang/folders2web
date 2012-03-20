# encoding: UTF-8

# remember to compile with Transformations...Convert Plain Text to Paragraph Spacing
# small script to convert my style of writing markdown(ish) in Scrivener to dokuwiki

$:.push(File.dirname($0))
require 'utility-functions'

a = File.read(ARGV[0])

a.gsubs!(
  [/([^\n])\n([^\n])/m, '\1' + "\n\n" + '\2'],    # convert to paragraph spacing
  [/.(h[1-9]\.)/, '\1'],                          # clean up spaces before titles
  [/\n\n\n+/, "\n\n"],                            # remove extraneous linespacing
  [/^b.(.+?)$/, '<blockquote>\1</blockquote>']
  )

# convert bullet lists with - and tabs to * and spaces
a.gsub!(/^(\t*)- /) { |f| "  " + f.gsubs([/\t/, '  '], ["- ", "* "]) }

# no double-spacing between bullet items
a.gsub!(/\*(.+?)\n\n[^ ]/m) {|f| f.gsub("\n\n", "\n")[0..-2] + "\n" + f[-1] }

File.write(ARGV[0]+".txt", a)