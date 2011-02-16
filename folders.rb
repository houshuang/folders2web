require 'find'
require 'fileutils'
require './library'

searchpath = "MA/"  
TITLE = "MA thesis"
OUT = "/Users/stian/src/kindle/out/"
layout = 3

def sanitize_filename(filename)
  name = filename.strip 
  # NOTE: File.basename doesn't work right with Windows paths on Unix
  # get only the filename, not the whole path
  name.gsub! /^.*(\\|\/)/, ''

  # Finally, replace all non alphanumeric, underscore 
  # or periods with underscore
  # name.gsub! /[^\w\.\-]/, '_'
  # Basically strip out the non-ascii alphabets too 
  # and replace with x. 
  # You don't want all _ :)
  name.gsub!(/[^0-9A-Za-z.\-]/, '_')
  return name
end

def create_index(path)
  path = path + "/"
  f = File.open(path + "index.html","w")
  f << '<html><head><link rel="stylesheet" href="http://alloy.liferay.com/deploy/build/aui-skin-base/css/aui-skin-classic-all-min.css" type="text/css" media="screen" />
  </head><body><div id="markupBoundingBox">
  <ul id="markupContentBox">'
  Dir.glob(path + "*").each do |node| 
    if File.file?(node) then
      paths = node.split("/")
      name = paths[paths.size-1]
      next if name == "index.html"
      clean_name = sanitize_filename(name)
      FileUtils.mv(path + name, path + clean_name) unless name == clean_name
      shortname = name.gsub(".html","")
      name.gsub!("'","\\'")
      f << "<li><a href='#{clean_name}' target='content'>#{shortname}</a></li>"
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
`cp index-#{layout}.html #{OUT}index.html`

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

  menufile << ("  " * indent) + "<li>" 
  if layout == 3
    menufile << "<a href='#{path}/index.html' target='filelist'>#{name}</a>"
    create_index(path) 
  elsif layout == 2
    if File.file?(path)
      menufile << "<a href='#{path}' target='content'>#{name}</a>"
    else
      menufile << "<a href='#'>#{name}</a>"
    end
  end

end
menufile << footer