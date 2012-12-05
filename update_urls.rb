# encoding: UTF-8

# script that goes through all PDFs in the BibDesk PDF folder, checks if it has a Finder download URL,
# checks if that URL is OA, and if it is, adds the field OA-URL and URL to the bibtex entry

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
require 'cgi'
require 'net/http'
require 'open-uri'


# this script goes through all the BibDesk PDFs, extracts download URLs, and adds these fields to BibDesk

BibDesk = Appscript.app('BibDesk')

def is_url(url)
  return true if url.index("http")
end

def update_url(pub, url)
  pub.fields["Url"].value.set(url)
  pub.fields["OA-URL"].value.set(url)
  puts "*" * 78
  puts "OA! #{url}"
  puts "*" * 78
end

def checkOA(url)
  return false unless is_url(url)
  puts "Checking OA: #{url}"
  res = checkOArun(url)
  puts res ? "True" : "False"
  return res
end

def checkOArun(origurl)
  url = origurl.gsub(/https?\:\/\/?/,'')
  uri, *path = url.split("/")
  path = "/" + path.join("/")
  origurl.sub!(':/', '://') unless origurl.index("//")

  chrome_agent = 'Mozilla/5.0 (X11; CrOS i686 1660.57.0) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.46 Safari/535.19'
  curl_opts = "--connect-timeout 5 -A '#{chrome_agent}'"

  # first check against whitelist
  whitelist = [ # list of URLs that don't need to be downloaded to check, first is URI, second is path
    [/arxiv\.org/, /\.pdf$/]
  ]
  whitelist.each { |comp| return true if uri.index(comp[0]) && path.index(comp[1]) }

  # faking agent, to avoid no-robots

  # grab header using curl
  response = `curl #{curl_opts} -I '#{origurl}'`

  possible_ctypes = [
    "application/pdf",
    "application/x-pdf",
    "application/vnd.pdf",
    "application/text.pdf"]

  # if ctype matches PDF, true, otherwise explore further
  possible_ctypes.each {|ctype| return true if response.index("Content-Type: #{ctype}")}

  # try curl
  `curl #{curl_opts} -r 0-99 -s '#{origurl}' > output.tmp`

  return (`file output.tmp;rm output.tmp`.index("PDF document") ? true : false)

  # we tried, but we failed.
  return false

end


if __FILE__==$0
  t = Time.now
  puts "Updating URLs on all files in #{PDF_path}"

  # logfiles
  dontmatch = File.open('dontmatch.txt','w')
  nourl = File.open('noturl.txt','w')
  notoa = File.open('notoa.txt','w')
  oa = File.open('oa.txt','w')


  # iterate through Bibdesk PDF directory
  Dir.foreach(PDF_path) do |f|
    next if f == '.' or f == '..'
    next unless f.size < 4 || f[-4..-1].downcase == '.pdf'

    docu = f[0..-5]

    p docu
    pub = BibDesk.document.search({:for =>docu})

    # PDF name doesn't match any citekeys
    unless pub.class == Array && pub.size > 0
      puts "#{docu}: Doesn't match citekey"
      dontmatch << docu << "\n"
      next
    end

    # already has OA pub
    if pub[0].fields["OA-Url"].value.get.size > 0
      puts "#{docu}: Already OA"
      next
    end

    # if already has URL field, check if OA
    url = try { pub[0].fields["URL"].value.get }
    if is_url(url) && checkOA(url)
      update_url(pub[0], url)
      puts "#{docu}: URL OA"
      oa << docu << "\n"
      next
    end

    # try to get d/l URL from Finder metadata
    a = `mdls -name kMDItemWhereFroms "#{PDF_path}/#{docu}.pdf"`

    if url = try {a.split('"')[1]} && checkOA(url)
      update_url(pub[0], url)
      puts "#{docu}: Finder OA"
      oa << docu << "\n"
      next
    end
    if url.class == String
      puts "#{docu}: No OA"
      notoa << docu << "\n"
    else
      puts "#{docu}: No file"
      nourl << docu << "\n"
    end
  end
  puts Time.now-t

end
