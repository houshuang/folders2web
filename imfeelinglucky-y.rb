require 'appscript'
include Appscript
dt = app('Google Chrome')
cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get

a = File.open("/tmp/imfeelinglucky-tmp").readlines
stext = a[0].strip.gsub("%20"," ")
url = a[ARGV[0].to_i].strip

if cururl.index("localhost/wiki")
  text = "[[#{url}|#{stext}]]"
elsif cururl.index("reganmian.net/blog")
  text = %q|{\rtf1{\field{\*\fldinst{HYPERLINK "URL"}}{\fldrslt TEXT}}}|
  text.gsub!("URL", url)
  text.gsub!("TEXT", stext)
else
  text = "<a href='#{url}'>#{stext}</a>"  
end

IO.popen("pbcopy","w+") {|pipe| pipe << text.strip}