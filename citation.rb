require 'net/http'

require 'rubygems'
require 'json'

require 'appscript'
include Appscript

$:.push(File.dirname($0))
require 'utility-functions'

@pdf_path = '/Users/ramuller/Dropbox/PDFs/*.pdf'
@bd = app("BibDesk").document

def send_citation(bibtex, hashkey)
  host = 'stormy-leaf-9036.herokuapp.com'
  port = '80'

  post_ws = "/citations"
  token = "CKwyA5hpKUDaxENtu4YM"

  payload ={ "bibtex" => bibtex, "token" => token }.to_json
    
  req = Net::HTTP::Post.new("/citations", initheader = {'Content-Type' =>'application/json'})
  req.body = payload
  response = Net::HTTP.new(host, port).start {|http| http.request(req) }
end


if ARGV[0] == "batch"
  c=0
  path = @pdf_path 
  Dir[path].select  do |f| 
    fname = File.basename(f)
    citekey = fname[0..-5]
    begin
      bibtx = @bd.search({:for =>citekey})[0].BibTeX_string.get.to_s
      hash = hashsum(f)
      send_citation(bibtx, hash)
    rescue
      puts "Not found"
      next
    end
    c = c+1
  end
  puts "Total #{c} entries sent"
end

