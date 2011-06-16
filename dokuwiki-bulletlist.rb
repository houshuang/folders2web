# encoding: UTF-8
# cleans up a text into bulleted list, either separated by commas or by line shifts.
b = `pbpaste`
out = ''
a = b.gsub(/^[\t]*\*/,"")
if a.scan("\n").size > 1
  splt = "\n"
elsif a.scan(",").size < 1
  splt = " "
else
  splt = ","
end
splits = a.split(splt)
if splits.last.index("and") 
  x,y = splits.last.split("and")
  splits.pop
  splits << x
  splits << y
end
splits.each do |item|
  i  =item.gsub(", and","").gsub(".","").gsub("*","").gsub(/^ *and /,"").gsub(/\.$/,"").strip
  out << "  * #{i}\n" if i.size > 0
end
puts out