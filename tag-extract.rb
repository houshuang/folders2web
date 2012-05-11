# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'

unless ARGV.size >= 2
  puts "Usage: ruby taskpaper-extract.rb <scrivener|taskpaper> <infile> [outfile]\n
  For example: ruby taskpaper-extract.rb scrivener litreview.taskpaper litreview
  (Scrivener outputs to a directory, with each tag in a separate textfile, taskpaper to a single hierarchical textfile)
  Without an output name specified, it's automatic infile + .out"
  exit
end

# these defaults are for Taskpaper formatted files, modify for org-mode or any other format
Indent_pattern = /^(\t*)/           # each indent level is determined by one tab
Citekey_pattern = /^\[@(.+?)\]/     # lines that begin with [@...] "contaminate" all indented lines below

a = try { File.read(ARGV[1]) }
unless a
  puts "Could not read input file"
  exit
end

lines = Array.new
linecontext = Array.new

# insert lines into an array with first argument being indent level, second being line content
a.lines.each_with_index do |l, i|
  # count tabs at front of line to get indent level
  tabs = $1 if Indent_pattern =~ l
  level = (defined? tabs) ? tabs.size : 0

  lines[i] = [level, l]
end

# iterate through array and for each line, pick out "line context" (all subsequent lines at lower levels)

ckey = '' # holds the current citation key
all_tagged = '' # holds all tagged text, to later check for lines that have not been tagged

ckey_pattern = /^\[@(.+?)\]/
lines.each_with_index do |l, i|
  level = l[0]

  # if first level, grab ckey or empty out
  if level == 0
    ckey = (Citekey_pattern =~ l[1]) ? $1 : ''
  end

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

tag_regexp = /
\B                  # non-word marker
(?<!\[)             # not preceded by [ (to avoid catching publication references like [@publication])
\@(?<tagcapt>.+?)   # word starting with @
\b                  # word boundary
/x

tags = Hash.new

# iterate through array, and if tag is found, insert line context for that line into tag hash
lines.each_with_index do |l, i|
  f = l[1].scan2(tag_regexp)        # recognize a @tag
  if f                              # has tag
    f[:tagcapt].each do |x|         # for each tag if multiple
      cont = linecontext[i][0]
      cont.remove!(tag_regexp, /\[\@#{linecontext[i][1]}\]/, /\:$/)
      tags.add(x, [cont.strip, linecontext[i][1]])
      all_tagged << cont
    end
  end
end

# do a final sweep to see if any lines have not been collected
lines.each_with_index do |l, i|
  next if l[1].remove(ckey_pattern).strip.size == 0       # nothing but ckey
  next if l[1].scan2(tag_regexp)                          # recognize a @tag

  cont = l[1].remove(/\[\@#{linecontext[i][1]}\]/, /\:$/).strip

  unless all_tagged.index(cont) # unless it has been tagged
    tags.add('not_tagged', [cont, linecontext[i][1]])
  end
end

outdir = ARGV[2]
outdir ||= ARGV[1].remove(".taskpaper") + ".out"

case ARGV[0]
when 'scrivener'
  `mkdir '#{outdir}'`
  `rm -rf '#{outdir}/*.txt'`
  tags.each do |tag, content|

    out = ''
    nockey = ''
    content.each do |fragments|
      if fragments[1] == ''
        nockey << "#{fragments[0]}\n\n"
        next
      end
      out << "#{fragments[0]} [@#{fragments[1]}]\n\n"
    end
    if nockey.size > 0
      out << nockey
    end
    File.write("#{outdir}/#{tag}.txt", out)
  end

when /(taskpaper|dokuwiki)/ #Taskpaper
  out = ''

  tags.each do |tag, content|
    nockey = ''

    out << "#{tag}:\n"
    content.each do |fragments|

      fragments[0] = fragments[0].lines.map {|ln| ("\t\t" + ln).remove("\n")}

      if fragments[1] == ''
        nockey << "#{fragments[0].join("\n")}\n"
        next
      end

      out << "\t[@#{fragments[1]}]:\n#{fragments[0].join("\n")}\n"
    end
    out << "\tNo citekey:\n#{nockey}" if nockey.size > 0
  end

  if ARGV[0] == 'dokuwiki'
    out.gsubs!(
      ["\t", '  '],
      [/(?! )(.+?)$/, '  * \1'],
      [/^  \* /, 'h2. ']
    )
    outdir = outdir + ".txt" unless outdir.index(".txt")
  else
    outdir = outdir + ".taskpaper" unless outdir.index(".taskpaper")
  end


  File.write(outdir, out)

else
  puts "Did not recognize output format"
  exit
end

puts "#{tags.size} tags written to #{outdir}."

# ideas:
# - hierarchy of tags
# - if tag starts with @-, only take current line