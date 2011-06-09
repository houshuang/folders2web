# encoding: UTF-8

# cleans up a text into bulleted list, either separated by commas or by line shifts.
b = `pbpaste`
out = ''
a = b.gsub(/^[\t]*\*/,"")
if a.scan("\n").size > 1
  splt = "\n"
else
  splt = ","
end
a.split(splt).each do |item|
  i  =item.gsub(", and","").gsub(".","").gsub("*","").gsub(/^ *and /,"").gsub(/\.$/,"").strip
  out << "  * #{i}\n" if i.size > 0
end
puts out