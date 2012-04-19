# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'ap'

a = File.read('/Users/Stian/Dropbox/PhD/Litreview April 2012.taskpaper')

lines = Array.new
linecontext = Array.new

# insert lines into an array with first argument being indent level, second being line content
a.lines.each_with_index do |l, i|
  # count tabs at front of line to get indent level
  tabs = $1 if /^(\t*)/ =~ l
  level = (defined? tabs) ? tabs.size : 0

  lines[i] = [level, l]
end

# iterate through array and for each line, pick out "line context" (all subsequent lines at lower levels)

ckey = '' # holds the current citation key

lines.each_with_index do |l, i|

  level = l[0]

  # if first level, grab ckey or empty out
  # if level == 0
  ckey_pattern = /^\[@(.+?)\]/
  ckey = $1 if ckey_pattern =~ l[1]
    #   ckey = "[@#{$1}]"
    # else
    #   ckey = ''
    # end
  # end
  if i == lines.size-1 || lines[i+1][0] <= level   # if it's the last entry, or the next entry is lower level
    linecontext[i] = [l[1].strip, ckey]
  else
    curlevel = level
    c = i+1 # set start of counter to current pub
    text = l[1].dup
    while c < lines.size && lines[c][0] > level # as long as the level of the line in question is higher than the start level
      curlevel, t = lines[c]
      text << t[l[0]..-1] # remove the same number of indents as highest level has, preserve subsequent indents
      c += 1
    end

    linecontext[i] = [text.strip, ckey]
  end
end

# iterate through array, and if tag is found, insert line context for that line into tag hash
tags = Hash.new

tag_regexp = /
\B                  # non-word marker
(?<!\[)             # not preceded by [ (to avoid catching publication references like [@publication])
\@(?<tagcapt>.+?)   # word starting with @
\b                  # word boundary
/x

lines.each_with_index do |l, i|
  f = l[1].scan2(tag_regexp)        # recognize a @tag
  if f                              # has tag
    f[:tagcapt].each do |x|         # for each tag if multiple
      cont = linecontext[i][0]
      cont.remove!(tag_regexp, /\[\@#{linecontext[i][1]}\]/)
      tags.add(x, [cont.strip, linecontext[i][1]])
    end
  end
end

if ARGV[0] == 'scrivener'
  outdir = 'litreview'
  `mkdir #{outdir}`
  `rm -rf #{outdir}/*.txt`
  tags.each do |tag, content|

    out = ''
    content.each do |fragments|
      #fragments[0] = fragments[0].lines.map {|ln| ("\t\t" + ln).remove("\n")}
      out << "#{fragments[0]} [@#{fragments[1]}]\n\n"
    end

    File.write("#{outdir}/#{tag}.txt", out)
  end

else
  out = ''
  tags.each do |tag, content|
    out << "#{tag}:\n"
    content.each do |fragments|
      fragments[0] = fragments[0].lines.map {|ln| ("\t\t" + ln).remove("\n")}
      out << "\t[@#{fragments[1]}]:\n#{fragments[0].join("\n")}\n"
    end
  end
  File.write('out.taskpaper', out)
end

puts "#{tags.size} tags written to #{outdir}."

# ideas:
# - not tagged text, go through each line and search for it in the index of all tagged text
#   exclude beginning of line with only [@ckeys], and lines with tags
# - hierarchy of tags
# - create Taskpaper document as well
# - if tag starts with @-, only take current line