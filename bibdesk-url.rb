require 'appscript'
include Appscript
arg = ARGV[0][10..-1]

File.open("log","a"){|f|f << "BDsk #{ARGV[0]}\n"}

dt = app('Bibdesk')
dd = dt.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
find = dt.search({:for => arg})
unless find == []
  find[0].show
  dt.activate
else
  `/usr/local/bin/growlnotify -t "File not found" -m "Cannot find citation #{ARGV[0]}"`
end
#dd = dt.open("/Volumes/Home/stian/Documents/Bibdesk/scardamalia2003knowledge.pdf")