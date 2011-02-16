require 'find'
require 'fileutils'

searchpath = "MA/"  
ALLOY = "file:///Users/stian/Downloads/alloy-1.0.1"
header = "MA thesis"

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
  f << '<html><head><link rel="stylesheet" href="' + ALLOY + '/build/aui-skin-classic/css/aui-skin-classic-all-min.css" type="text/css" media="screen" />
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

puts '<!DOCTYPE html>

<html>
<head>
<script src="'+ ALLOY + '/build/aui/aui.js" type="text/javascript"></script>

<link rel="stylesheet" href="'+ ALLOY + '/build/aui-skin-classic/css/aui-skin-classic-all-min.css" type="text/css" media="screen" />
</head>

<style type="text/css" media="screen">

</style>

<body>
<h1>' + header + '</h1>

<div id="markupBoundingBox">
<ul id="markupContentBox">'
indent = 1


Find.find(searchpath) do |path|

  dirs = path[searchpath.size..-1].split("/")
  next if dirs == []
  next if File.file?(path)
  next if dirs[dirs.size-1] == "Images"
  cur_ind = dirs.size
  if cur_ind > indent then 
    puts "<ul>"
  elsif cur_ind < indent then
    puts "</ul></li>" * (indent - cur_ind)
  end
  indent = cur_ind
  name = dirs[indent-1]

    puts ("  " * indent) + "<li>" 
    puts "<a href='#{path}/index.html' target='filelist'>#{name}</a>"
    create_index(path)


end
puts '</li></ul></div>

<script type="text/javascript" charset="utf-8">

AUI().ready("aui-tree-view", function(A) {

  var treeView = new A.TreeView({
    boundingBox: "#markupBoundingBox",
    contentBox: "#markupContentBox"
    })
    .render();

    });

    </script>

    </body>
    </html>'