# encoding: UTF-8

# let's you take screenshots from Kindle app, attached to the publication currently open in Chrome. 

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'

# duplicate from dokuwiki.rb, move to utility-functions?
def cururl
  Chrome.windows[1].get.tabs[Chrome.windows[1].get.active_tab_index.get].get.URL.get.strip
end

def export(docu)
  if File.exists?("/tmp/skim-#{docu}-tmp")
    a = File.readlines("/tmp/skim-#{docu}-tmp")
    @out = "h2. Images\n\n"
    c = 0
    a.each do |line|
      f,pg = line.split(",")
      puts "mv \"#{f.strip}\" \"/wiki/data/media/skim/#{docu}#{c.to_s}.png\""
      `mv "#{f.strip}" "/wiki/data/media/skim/#{docu}#{c.to_s}.png"`
      @out << "{{skim:#{docu}#{c.to_s}.png}}\n----\n\n"
      c += 1
    end
    `rm "/tmp/skim-#{docu}-tmp"`
    File.write("/tmp/skimtmp", @out)
    puts @out
    growl("#{c} images added to #{docu}")
    `/wiki/bin/dwpage.php -m 'Automatically extracted from Skim' commit /tmp/skimtmp 'skimg:#{docu}'`
    `open http://localhost/wiki/ref:#{docu}`
  else
    growl "No image cache found for #{docu}"
  end
  exit
end

Chrome = Appscript.app('Google Chrome')

# getting current publication
wiki = cururl[22..-1] # hardcoded to localhost - make more dynamic?
w,dummy = wiki.split("?")
dummy, dname = wiki.downcase.split(":")

export(dname) if ARGV[0] == 'export'

curfile =  File.last_added("#{Home_path}/Desktop/Screen*.png") # this might be different between different OSX versions
if curfile == nil
  growl("No screenshots available")
  exit
end

File.append("/tmp/skim-#{dname}-tmp","#{curfile},0")
growl("One picture added to wiki notes cache for #{dname}")