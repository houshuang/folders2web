a = File.open(ARGV[0])

loc = ''
while !a.eof? do 
  line = a.readline.gsub("----","")
  next if line.strip ==  ''
  if line.index("Loc: ")
    loc = line.gsub("Loc: ", "").strip
  else
    puts "#{line.strip} (loc: #{loc})\n\n"
  end
end
