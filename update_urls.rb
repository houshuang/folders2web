# encoding: UTF-8

# researchr scripts relevant to BibDesk (the right one is executed from the bottom of the file)

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
require 'cgi'

# this script goes through all the BibDesk PDFs, extracts download URLs, and adds these fields to BibDesk

BibDesk = Appscript.app('BibDesk')

puts "Updating URLs on all files in #{PDF_path}"

# iterate through Bibdesk PDF directory
Dir.foreach(PDF_path) do |f|
  next if f == '.' or f == '..'
  next unless f[-4..-1].downcase == '.pdf'
   
  docu = f[0..-5]
  puts docu

  a = `mdls -name kMDItemWhereFroms "#{PDF_path+"/"+f}"`
  next unless a.index("http")

  b = a.split('"')

  pub = BibDesk.document.search({:for =>docu})[0]

  pub.fields["Url"].value.set(b[1].gsub(".myaccess.library.utoronto.ca",""))
  puts b[1]
  if b[3] && b[3].scan(/\&q\=(.+?)\&/).size > 0
    pub.fields["GScholar search term"].value.set CGI::unescape($~[1]).gsub('"','')
    puts b[3]
  end
  puts "*" *40
  
end


# kMDItemWhereFroms = (
#     "http://www.lancs.ac.uk/fss/organisations/netlc/past/nlc2010/abstracts/PDFs/Mackness.pdf",
#     "http://scholar.google.com/scholar?q=mooc&hl=en&btnG=Search&as_sdt=1%2C5&as_sdtp=on"
# )
