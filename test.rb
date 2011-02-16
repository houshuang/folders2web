def create_index(path)
  f = File.open(path + "index.html","w")
  f << "<html><head></head><body>"
  Dir.glob(path + "*").each do |node| 
    if File.file?(node) then
      name = node.split("/")[1]
      puts f << "<li><a href='#{name}'>#{name}</a></li>"
    end
  end
  f << "</body></html>"
end

create_index("Clippings/")
