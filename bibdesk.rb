# encoding: UTF-8

# researchr scripts relevant to BibDesk (the right one is executed from the bottom of the file)

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
 
#### keyboard commands ####
 
# properly formats an author list for BibDesk with " and " as separator
def authorlist
  a = pbpaste.strip
  a.force_encoding("ISO-8859-1")

  # determine whether to split on newline, space or comma
  if a.scan(";").size > 1
    splt = ";"
  elsif a.scan(",").size > 2
    splt = ","
  end

  a= a.split(splt).join("||").gsub(" and ","").gsub("&","").gsub("||", " and ").gsub(/ +/," ")
  pbcopy(a)
end

# put on the clipboard a properly formatted list of references to the currently selected publications
def copy
  out = ''

  Selection.each do |dd|
    docu = dd.cite_key.get
    docu.strip!
    out << "[@#{docu}] "
  end

  pbcopy(out.strip)
  growl("#{Selection.size} citation references copied to the clipboard")
end

# attaches the last added PDF in the download directory to the currently selected Bibdesk reference
def linkfile
  curfile =  File.last_added("#{Downloads_path}/*.pdf")

  f = MacTypes::FileURL.path(curfile)
  Selection[0].linked_files.add(f,{:to =>Selection[0]})
  Selection[0].auto_file

  growl("PDF added", "File added successfully to #{Selection[0].cite_key.get}")
end

# uses quicklook to preview the PDF of the currently selected publication
def qlook
  file =  Selection[0].linked_files.get[0].to_s
  if File.exists?(file)
    `qlmanage -p '#{file}'`
  else
    growl('No file available', 'No file available for #{Selection[0].cite_key.get}')
  end
end

# opens a given reference as passed by bibdesk:// URL in BibDesk
def url(argv)
  arg = argv[10..-1]
  find = BibDesk.search({:for => arg})
  unless find == []
    find[0].show
    BibDesk.activate
  else
    growl("File not found", "Cannot find citation #{ARGV[0]}")
  end
end

def open
  require 'wiki-lib'
  ary = Array.new
  Selection.each do |dd|
    docu = dd.cite_key.get
    ary << docu unless File.exists?("#{Wikipages_path}/ref/#{docu}.txt")
    ensure_refpage(docu)
  end
  `open http://localhost/wiki/ref:#{Selection[0].cite_key.get}`
end

#### Running the right function, depending on command line input ####

BibDesk = Appscript.app('BibDesk')
Selection = BibDesk.document.selection.get[0]
send *ARGV
