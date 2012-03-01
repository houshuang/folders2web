require 'pp'
require 'open-uri'
require 'yaml'

curpath = File.dirname(File.expand_path(__FILE__)) + "/"


def json_parse(json)
  null=nil
  return  eval(json.gsub(/(["'])\s*:\s*(['"0-9tfn\[{])/){"#{$1}=>#{$2}"})
end

search = `pbpaste`.strip.gsub(" ","%20")

if search.index("|")
  searchphrase = search.gsub("|","%20")
  stext, dummy = search.split("|")
elsif search.index("[")
    stext, searchphrase = search.split("[")
else
  stext = search
  searchphrase = search
end
a = open("http://api.bing.net/json.aspx?AppId=A203F8FB37F05FFF4756C5217E1FCCA156AD7D6C&Query=#{searchphrase}&Sources=Web").read
pp json_parse(a)
out = ''
c =0 
File.open("/tmp/imfeelinglucky-tmp",'w') do |file|
  file << "#{stext}\n"
  json_parse(a)["SearchResponse"]["Web"]["Results"].each do |item|
    c += 1
    file << "#{item["Url"]}\n"
    out << "#{c}: #{item["Title"]}\n\t#{item["Url"]}\n"
  end
end

`/usr/local/bin/growlnotify -t "Possible links" -m "#{out}"`