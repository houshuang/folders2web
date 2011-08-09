# encoding: UTF-8
# this script takes a doc file as input (or anything, really), and generates a PDF, using qlmanage and wkhtmltopdf
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript
require 'cgi'

def do_pdf(path)
  arg = path
  puts path
  file = File.basename(path)
  `qlmanage -p -o /tmp "#{arg}"`
  if File.exists?("/tmp/#{file}.qlpreview/Preview.html")
    a = File.read("/tmp/#{file}.qlpreview/Preview.html") 
    b = a.gsub('<div class="PageStyle">',"").gsub('{background:#ACB2BB;}','')
    File.open("/tmp/qlmanage-doc2pdf-tmp.html","w") {|f| f << b}
    `wkhtmltopdf /tmp/qlmanage-doc2pdf-tmp.html "#{arg}.pdf"`
    app('Finder').reveal( MacTypes::FileURL.path(arg + ".pdf"))
  else
    growl("Not able to convert to PDF", "Preview could not convert #{file}")
  end
end

growl("Starting conversion")
app('Finder').selection.get(:result_type=>:alias).each do |item|
  url = item.path # http://stackoverflow.com/questions/6976898
  do_pdf(url)
end
growl("Conversion complete")