$:.push(File.dirname($0))
require 'utility-functions'
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
fname = "#{PDF_path}/#{pdf}.pdf"
if File.exists?(fname)
  dt = app('Skim')
  dd = dt.open(fname)
  dd.go({:to => dd.pages.get[page-1]})
  dt.activate
else
  growl("File not found", "Cannot find PDF #{fname}")
end
