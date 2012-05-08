#!/usr/bin/ruby
require 'net/http'
require "cgi"

# this runs on my server, and is used to check if a certain url links to a publicly available PDF

def checkOA(url)
  url = url.gsub(/http\:\/\/?/,'')
  uri, *path = url.split("/")
  path = "/" + path.join("/")

  # first check against whitelist
  whitelist = [ # list of URLs that don't need to be downloaded to check, first is URI, second is path
    [/arxiv\.org/, /\.pdf$/]
  ]

  whitelist.each { |comp| return true if uri.match(comp[0]) && path.match(comp[1]) }

  # if no luck, try downloading header
  response = nil
  chrome_agent = 'Mozilla/5.0 (X11; CrOS i686 1660.57.0) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.46 Safari/535.19'
  Net::HTTP.start(uri, 80) { |http| response = http.head(path, "User-Agent" => chrome_agent) }

  possible_ctypes = [
    "application/pdf",
    "application/x-pdf",
    "application/vnd.pdf",
    "application/text.pdf"]
  return possible_ctypes.index( response['content-type'] )
end

arg = CGI.new['redir'].to_s
puts "Content-Type: text/html; charset=utf-8\n\n"
puts checkOA(arg) ? 'true' : 'false'
