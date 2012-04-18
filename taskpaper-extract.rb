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
  if level == 0
    if /^\[@(.+?)\]/ =~ l[1]
      ckey = "[@#{$1}]"
    else
      ckey = ''
    end
  end
  if i == lines.size-1 || lines[i+1][0] <= level   # if it's the last entry, or the next entry is lower level
    linecontext[i] = "#{l[1].strip} #{ckey}"
  else
    curlevel = level
    c = i+1 # set start of counter to current pub
    text = l[1].dup
    while c < lines.size && lines[c][0] > level # as long as the level of the line in question is higher than the start level
      curlevel, t = lines[c]
      text << t
      c += 1
    end

    linecontext[i] = "#{text.strip} #{ckey}"
  end
end
#ap linecontext
# iterate through array, and if tag is found, insert line context for that line into tag hash
tags = Hash.new

tag_regexp = /
  \B                  # non-word marker
  (?<!\[)             # not preceded by [ (to avoid catching publication references like [@publication])
  \@(?<tagcapt>.+?)   # word starting with @
  \b                  # word boundary
/x

lines.each_with_index do |l, i|
  f = l[1].scan2(tag_regexp)  # recognize a @tag
  f[:tagcapt].each {|x| tags.add(x, linecontext[i].remove(tag_regexp))} if f
end

outdir = 'litreview'
`mkdir #{outdir}`
tags.each do |tag, content|
  File.write("#{outdir}/#{tag}.txt", content.join("\n\n"))
end

puts "#{tags.size} tags written to #{outdir}."