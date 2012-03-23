# encoding: UTF-8

# remember to compile with Transformations...Convert Plain Text to Paragraph Spacing
# small script to convert my style of writing markdown(ish) in Scrivener to dokuwiki
# allows you to use - with no indent instead of * indented two spaces for bullet list
# (can use tabs to increase indent)
# b. at the beginning of a line wraps line in blockquote
# uses h1. h2. for headings with DW plugin, can be modified to support classic DW headers

class String
  def gsubs!(*searches)
    self.replace(gsubs(*searches))
  end

  def gsubs(*searches)
    if searches[0].kind_of?(Hash)
      args = searches.shift
      all_replace = try { args[:all_with] }
    end
    tmp = self.dup
    searches.each do |search|
      if all_replace
        tmp.gsub!(search, all_replace)
      else
        tmp.gsub!(search[0], search[1])
      end
    end
    return tmp
  end
end

# a few extra file functions
class File
  class << self

    # adds File.write - analogous to File.read, writes text to filename
    def write(filename, text)
      File.open(filename,"w") {|f| f << text}
    end
  end
end

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