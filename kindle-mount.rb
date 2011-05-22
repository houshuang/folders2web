#!/usr/bin/ruby
# encoding: UTF-8

  File.open("/Volumes/Home/stian/src/folders2web/kindle","a"){|f| f<<Time.now.to_s << File.exists?("/Volumes/Kindle").to_s <<
  "\n"}
