require 'appscript'
include Appscript

arg = ARGV[0][8..-1]
if arg.index("%23")
  pdf, page_s = arg.split("%23")
  page = page_s.to_i
else
  pdf = arg
  page = 1
end

File.open("log","a"){|f|f << "#{pdf} -- #{page}\n"}
fname = "/Volumes/Home/stian/Documents/Bibdesk/#{pdf}.pdf"
if File.exists?(fname)
  dt = app('Skim')
  dd = dt.open("/Volumes/Home/stian/Documents/Bibdesk/#{pdf}.pdf")
  dd.go({:to => dd.pages.get[page-1]})
  dt.activate
else
  `/usr/local/bin/growlnotify -t "File not found" -m "Cannot find PDF #{fname}"`
end
#dd = dt.open("/Volumes/Home/stian/Documents/Bibdesk/scardamalia2003knowledge.pdf")
