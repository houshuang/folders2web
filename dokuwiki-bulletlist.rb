# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'

# cleans up a text into bulleted list, either separated by commas or by line shifts.

b = pbpaste

# strip off bullet etc from beginning
a = b.gsub(/^[\t]*\*/,"")

# determine whether to split on newline, space or comma
if a.scan("\n").size > 1
  splt = "\n"
elsif a.scan(",").size < 1
  splt = " "
else
  splt = ","
end

splits = a.split(splt)

# deal with situation where the last two items are delimited with "and"
if splits.last.index("and") 
  x,y = splits.last.split("and")
  splits.pop
  splits << x
  splits << y
end

out = ''
splits.each do |item|
  i = item.gsub(", and","").gsub(/[\.\*]/,"").gsub(/^ *and /,"").gsub(/\.$/,"").strip
  out << "  * #{i}\n" if i.size > 0
end

puts out