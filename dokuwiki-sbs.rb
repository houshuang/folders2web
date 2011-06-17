# encoding: UTF-8

# Add a text field 
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript

# asks for the name of a page, and presents it side-by-side with the existing page, in editing mode if it's a wiki page

dt=app('Google Chrome')

page = wikipage_selector("Choose page to view side-by-side with the current page")
exit unless page

cururl = dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.get

if cururl.index("localhost/wiki")
  cururl = cururl.to_s + "?do=edit&vecdo=print"
else
  cururl = "http://www.instapaper.com/text?u=\"+encodeURIComponent(\"#{cururl}\")+\""
end

newurl = "http://localhost/wiki/#{page.gsub(" ","_")}"

js = "var MyFrame=\"<frameset cols=\'*,*\'><frame src=\'#{cururl}\'><frame src=\'#{newurl}?do=edit&vecdo=print\'></frameset>\";with(document) {    write(MyFrame);};return false;"
puts js
dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.execute(:javascript => js)

# javascript:function iptxt(){var d=document;try{if(!d.body)throw(0);window.location='http://www.instapaper.com/text?u='+encodeURIComponent(d.location.href);}catch(e){alert('Please wait until the page has loaded.');}}iptxt();void(0)
# 
# http://www.instapaper.com/text?u=http%3A%2F%2Fjenniferclaro.wordpress.com%2F2011%2F05%2F11%2Fode-to-piaget%2F