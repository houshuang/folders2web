#!/usr/bin/ruby
require 'net/http'
require "cgi"
require 'open-uri'

# this runs on my server, and is used to check if a certain url links to a publicly available PDF

def checkOA(url)
  url = url.gsub(/https?\:\/\/?/,'')
  uri, *path = url.split("/")
  path = "/" + path.join("/")

  # first check against whitelist
  whitelist = [ # list of URLs that don't need to be downloaded to check, first is URI, second is path
    [/arxiv\.org/, /\.pdf$/]
  ]
  whitelist.each { |comp| return true if uri.match(comp[0]) && path.match(comp[1]) }

  # if no luck, try downloading header
  response = nil

  # faking agent, to avoid no-robots
  chrome_agent = 'Mozilla/5.0 (X11; CrOS i686 1660.57.0) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.46 Safari/535.19'

  Net::HTTP.start(uri, 80) { |http| response = http.head(path, "User-Agent" => chrome_agent) }

  possible_ctypes = [
    "application/pdf",
    "application/x-pdf",
    "application/vnd.pdf",
    "application/text.pdf"]

  # if ctype matches PDF, true, otherwise explore further
  if possible_ctypes.index( response['content-type'] )
    return true
  end

  # final check - does the file downloaded have the PDF magic bytes
  readurl = "http://" + url.gsub(" ", "%20")
  a = open(readurl).read
  return true if (a[0..3] == "%PDF")

  # let's try https as well, just for fun
  readurl = "https://" + url.gsub(" ", "%20")
  a = open(readurl).read
  return true if (a[0..3] == "%PDF")

  # we tried, but we failed.
  return false
end

arg = CGI.new['redir'].to_s
puts "Content-Type: text/html; charset=utf-8\n\n"
puts checkOA(arg) ? 'true' : 'false'