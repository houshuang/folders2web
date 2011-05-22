# encoding: UTF-8
require 'pp'
require 'appscript'
include Appscript

def download full_url, to_here
    require 'open-uri'
    writeOut = open(to_here, "wb")
    writeOut.write(open(full_url).read)
    writeOut.close
end

# grabs the name of the currently open BibDesk file, and puts on clipboard formatted as a DokuWiki reference
a = File.open("gscholar-tmp").readlines
citekey  = a[0]
url = a[ARGV[0].to_i]

domain = url.scan(/http:\/\/(.+?)\//)[0][0]

`/usr/local/bin/growlnotify -t "Download started" -m "Downloading from #{domain}"`

download(url, "/tmp/pdftmp.pdf")

dt = app('BibDesk')
d = dt.search({:for=>citekey})

f = MacTypes::FileURL.path('/tmp/pdftmp.pdf')
d[0].linked_files.add(f,{:to =>d[0]})
d[0].auto_file
`/usr/local/bin/growlnotify -t "PDF added" -m "File added successfully to #{d.cite_key.key}"`