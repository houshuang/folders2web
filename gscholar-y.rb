# encoding: UTF-8
$:.push(File.dirname($0))
require 'appscript'
include Appscript
require 'utility-functions'

# works with gscholar.rb - takes the number chosen as argument, downloads the file, and autolinks it to the publication

def download full_url, to_here
    require 'open-uri'
    writeOut = open(to_here, "wb")
    writeOut.write(open(full_url).read)
    writeOut.close
end

# grabs the name of the currently open BibDesk file, and puts on clipboard formatted as a DokuWiki reference
a = File.open("/tmp/gscholar-tmp").readlines
citekey  = a[0].strip
url = a[ARGV[0].to_i]
puts url
domain = url.scan(/http:\/\/(.+?)\//)[0][0]

growl("Download started", "Downloading from #{domain}")

download(url, "/tmp/pdftmp.pdf")

dt = app('BibDesk')
d = dt.search({:for=>citekey})
f = MacTypes::FileURL.path('/tmp/pdftmp.pdf')
d[0].linked_files.add(f,{:to =>d[0]})
d[0].auto_file

growl("PDF added", "File added successfully to #{d.cite_key.get}")