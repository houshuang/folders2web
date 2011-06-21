# encoding: UTF-8

# Present a wiki page selector and open the page selected
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript

pagetmp = wikipage_selector("Jump to which page?")
exit unless pagetmp
dt = app('Google Chrome')
dt.windows[1].get.tabs[dt.windows[1].get.active_tab_index.get].get.URL.set("http://localhost/wiki/#{pagetmp}")