ENV['GEM_PATH'] = "/home/houshuan/gems:/usr/lib/ruby/gems/1.8"
ENV['GEM_HOME'] = "/home/houshuan/gems"
Gem.clear_paths
#$LOAD_PATH.unshift(File.dirname(__FILE__))
require 'check-oa'

run CheckOA

