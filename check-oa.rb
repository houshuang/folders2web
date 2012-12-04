#!/usr/bin/ruby
require 'net/http'
require "cgi"
require 'open-uri'

# this runs on my server, and is used to check if a certain url links to a publicly available PDF

def checkOA(origurl)
  url = origurl.gsub(/https?\:\/\/?/,'')
  uri, *path = url.split("/")
  path = "/" + path.join("/")
  origurl.sub!(':/', '://') unless origurl.index("//")

  # first check against whitelist
  whitelist = [ # list of URLs that don't need to be downloaded to check, first is URI, second is path
    [/arxiv\.org/, /\.pdf$/]
  ]
  whitelist.each { |comp| return true if uri.match(comp[0]) && path.match(comp[1]) }

  # faking agent, to avoid no-robots
  chrome_agent = 'Mozilla/5.0 (X11; CrOS i686 1660.57.0) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.46 Safari/535.19'

  # grab header using curl
  response = `curl -A '#{chrome_agent}' -I '#{origurl}'`

  possible_ctypes = [
    "application/pdf",
    "application/x-pdf",
    "application/vnd.pdf",
    "application/text.pdf"]

  # if ctype matches PDF, true, otherwise explore further
  possible_ctypes.each {|ctype| return true if response.index("Content-Type: #{ctype}")}

  # try curl
  `curl -r 0-99 -s -A '#{chrome_agent}' '#{origurl}' > output.tmp`

  return (`file output.tmp;rm output.tmp`.index("PDF document") ? true : false)

  # we tried, but we failed.
  return false
end

arg = CGI.new['redir'].to_s
puts "Content-Type: text/html; charset=utf-8\n\n"
puts checkOA(arg) ? 'true' : 'false'