# encoding: UTF-8

# Present a wiki page selector and open the page selected
$:.push(File.dirname($0))
require 'utility-functions'

pagetmp = wikipage_selector("Jump to which page?")
exit unless pagetmp
`open "http://localhost/wiki/#{pagetmp}"`