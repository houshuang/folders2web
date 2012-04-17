#!/usr/bin/ruby
require 'net/http'
require "cgi"

# this runs on my server, and is used to check if a certain url links to a publicly available PDF

def checkOA(url)
  url = url.gsub("http:/",'').gsub("http://",'')
  uri, *path = url.split("/")
  path = "/" + path.join("/")
  response = nil
  Net::HTTP.start(uri, 80) { |http| response = http.head(path) }

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