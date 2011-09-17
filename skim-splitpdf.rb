# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript
require 'pashua'
include Pashua

# pops up a dialogue which asks how many pages, and runs a CLI command (pdfmanipulate) which extracts a piece of the current
# PDF starting with the current page and the number of pages indicated forwards. Useful for extracting articles out of
# proceedings, etc. 

dt = app('Skim').document
docu = dt.path.get[0]
dname = dt.name.get[0]
page = dt.get[0].current_page.get.index.get

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