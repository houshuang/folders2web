require 'find'
require 'fileutils'
require './library'

#######################################################################
searchpath = "MA/"  
TITLE = "Raw notes from research on Top Level Courses Project"
OUT = "/Users/stian/src/folders2web/out/"
layout = 3
#######################################################################

def rel_path(path)
  path[OUT.size..-1]
end

def sanitize_filename(filename)
  name = filename.strip 
  name.gsub!(/^.*(\\|\/)/, '')
  name.gsub!(/[^0-9A-Za-z.\-]/, '_')
  return name
end

def skip?(filename)
  true if ['index.html','Images'].index(filename) || filename[0..1] == '.'
end

def clean_name(path) # takes full path, moves file, returns new rel path and display name
  name = File.basename(path)
  onlypath = path[0..-name.size-2]
  onlypath = onlypath + "/" unless onlypath[-1..-1] == "/"
  clean = sanitize_filename(name)
  cleanpath = onlypath + clean
  FileUtils.mv(path, cleanpath) unless name == clean
  shortname = name.gsub(".html","")
  return rel_path(cleanpath), shortname
end

def create_index(path) # only called on layout = 3
  path = path + "/"
  f = File.open(path + "index.html","w")
  f << '<html><head><link rel="stylesheet" href="http://reganmian.net/alloy/build/aui-skin-classic/css/aui-skin-classic-all-min.css" type="text/css" media="screen" />
  </head><body><div id="markupBoundingBox">
  <ul id="markupContentBox">'
  Dir.glob(path + "*").each do |node| 
    if File.file?(node) then
      paths = node.split("/")
      name = paths[paths.size-1]
      next if skip?(name)
      name, shortname = clean_name(node) # no path, in current directory
      f << "<li><a href='#{File.basename(name)}' target='content'>#{shortname}</a></li>"
    end
  end
  f << "</ul></div></body></html>"
end

#######################################################################
indent = 1

`rm -rf #{OUT}`
`mkdir #{OUT}`
`cp -R #{searchpath}* #{OUT}`
searchpath = OUT
File.open(OUT + "index.html", 'w') { |f| f << indexhtml(TITLE, layout) }

menufile = File.open(searchpath + "dirs.html", "w")

menufile << header(TITLE)

Find.find(searchpath) do |path|

  dirs = path[searchpath.size..-1].split("/")
  next if dirs == []
  next if File.file?(path) && layout == 3
  next if dirs[dirs.size-1] == "Images"
  cur_ind = dirs.size
  if cur_ind > indent then 
    menufile << "<ul>"
  elsif cur_ind < indent then
    menufile << "</ul></li>" * (indent - cur_ind)
  end
  indent = cur_ind
  name = dirs[indent-1]
  
  filename = File.basename(path)
  relpath = rel_path(path)
#  relpath = "" if relpath = filename
  
  next if skip?(filename)
  
  menufile << ("  " * indent) + "<li>" 
  if layout == 3
    menufile << "<a href='#{relpath}/index.html' target='filelist'>#{name}</a>"
    create_index(path) 
  elsif layout == 2
    if File.file?(path)
      name, shortname = clean_name(path)
      menufile << "<a href='#{name}' target='content'>#{shortname}</a>"
    else
      menufile << "<a href='#'>#{name}</a>"
    end
  end

end
menufile << footer