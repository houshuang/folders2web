require 'utility-functions'
require 'appscript'
include Appscript
dt = app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get

# a = File.open("/tmp/imfeelinglucky-tmp").readlines
# stext = a[0].strip.gsub("%20"," ")
# url = a[ARGV[0].to_i].strip
url = "http://reganmian.net/blog"
stext = "stuff"
if cururl.index("localhost/wiki")
  text = "[[#{url}|#{stext}]]"
elsif cururl.index("reganmian.net/blog")
  text = %q|{\rtf1\ansi\ansicpg1250\cocoartf1038\cocoasubrtf350
  {\fonttbl\f0\froman\fcharset0 Times-Roman;}
  {\colortbl;\red255\green255\blue255;\red0\green0\blue238;}
  \deftab720
  \pard\pardeftab720\ql\qnatural
  {\field{\*\fldinst{HYPERLINK "URL"}}{\fldrslt 
  \f0\fs24 \cf2 \ul \ulc2 TEXT}}}|
  text.gsub!("URL", url)
  text.gsub!("TEXT", stext)
else
  text = "<a href='#{url}'>#{stext}</a>"  
end

IO.popen("pbcopy","w+") {|pipe| pipe << text.strip}