# encoding: UTF-8

# researchr scripts relevant to BibDesk (the right one is executed from the bottom of the file)

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'

#### utility functions ####
# opens a given reference as passed by skimx:// URL in Skim
# launched by skimx:// url in Chrome (skimx.app must be registered first)
def url(argv)
  arg = argv[8..-1]
  arg.gsub!("#","%23")
  pdf, page = arg.split("%23")
  fname = "#{PDF_path}/#{pdf}.pdf"
  if File.exists?(fname)
    dd = Skim.open(fname)
    dd.go({:to => dd.pages.get[page.to_i-1]}) unless page == nil
    Skim.activate
  else
    growl("File not found", "Cannot find PDF #{fname}")
  end
end

# stores a link to the last screenshot taken, and the current PDF page. when export to the wiki happens, the picture is
# moved to the wiki media folder, and linked to the notes
def screenshot
  dname = Document.name.get[0][0..-5]
  a = File.open("/tmp/skim-#{dname}-tmp","a")
  page = Document.get[0].current_page.get.index.get

  curfile =  File.last_added("#{Home_path}/Desktop/Screen*.png")
  a << "#{curfile},#{page}\n"
  growl("One picture added to wiki notes cache")
end

# pops up a dialogue which asks how many pages, and runs a CLI command (pdfmanipulate) which extracts a piece of the current
# PDF starting with the current page and the number of pages indicated forwards. Useful for extracting articles out of
# proceedings, etc. 
def splitpdf
  require 'pashua'
  include Pashua
  docu = Document.path.get[0]
  dname = Document.name.get[0][0..-5]
  page = Document.get[0].current_page.get.index.get

  # configuring Pashua dialogue
  config = " 
  *.title = researchr
  fb.type = textfield
  fb.default = 1
  fb.label = Starting on page #{page}, how many pages to extract?\n
  xb.type = checkbox
  xb.label = last page number, instead of number of pages
  db.type = cancelbutton
  db.label = Cancel
  db.tooltip = Closes this window without taking action\n"

  pagetmp = pashua_run config
  exit if pagetmp['cancel'] == 1 

  startpage = page.to_i
  tmppage = pagetmp['fb'].to_i
  if pagetmp['xb'] == "1"
    endpage = tmppage
  else
    endpage = pagetmp['fb'].to_i + startpage - 1
  end

  outfile = "#{Downloads_path}/#{dname[0..-5]}-split.pdf"

  `pdfmanipulate split "#{docu}" #{startpage}-#{endpage} -o "#{outfile}"`
  puts "pdfmanipulate split \"#{docu}\" #{startpage}-#{endpage} -o \"#{outfile}\""
  growl("File extracted and put in Downloads directory")
end

#### Running the right function, depending on command line input ####

Skim = Appscript.app('Skim')
Document = Skim.document
send *ARGV