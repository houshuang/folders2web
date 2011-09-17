require 'open-uri'
require 'yaml'

curpath = File.dirname(File.expand_path(__FILE__)) + "/"
conf = YAML::load(File.read(curpath + "config.yaml"))


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
a = open("https://www.googleapis.com/customsearch/v1?key=#{conf["googleapi"]}&q=#{searchphrase}").read
pp a
out = ''
c =0 
File.open("/tmp/imfeelinglucky-tmp",'w') do |file|
  file << "#{stext}\n"
  json_parse(a)["responseData"]["results"].each do |item|
    c += 1
    file << "#{item["unescapedUrl"]}\n"
    out << "#{c}: #{item["titleNoFormatting"]}\n\t#{item["unescapedUrl"]}\n"
  end
end

`/usr/local/bin/growlnotify -t "Possible links" -m "#{out}"`