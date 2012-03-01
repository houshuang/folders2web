require 'net/http'

require 'rubygems'
require 'json'

require 'appscript'
include Appscript

$:.push(File.dirname($0))
require 'utility-functions'
puts "hello"
@bd = app("BibDesk").document

def send_citation(bibtex, hashkey)
  host = Scrobble_server_host
  port = Scrobble_server_port

  post_ws = "/citations"
  token = Scrobble_token
  payload ={ "bibtex" => bibtex, "token" => token }.to_json

  req = Net::HTTP::Post.new("/citations", initheader = {'Content-Type' =>'application/json'})
  req.body = payload
  response = Net::HTTP.new(host, port).start {|http| http.request(req) }
end


if ARGV[0] == "batch"
  c=0
  path = PDF_path + "/*.pdf"
  growl "Beginning to send citation data to Scrobble server"
  Dir[path].select  do |f|
    puts f
    fname = File.basename(f)
    citekey = fname[0..-5]
    begin
      bibtx = @bd.search({:for =>citekey})[0].BibTeX_string.get.to_s
      hash = hashsum(f)
      send_citation(bibtx, hash)
    rescue
      puts "#{citekey} not found in BibDesk database"
      next
    end
    c = c+1
  end
  growl "Total #{c} entries sent"
end

