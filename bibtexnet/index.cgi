#!/usr/bin/ruby -KU
require 'open-uri'
require "cgi"

cgi = CGI.new

puts "Content-Type: text/html; charset=utf-8\n\n"
puts "<html><head><meta http-equiv=\"content-type\" content=\"text-html; charset=utf-8\">"
hash =  cgi['hash'][0..-6]
require 'sequel'

# create database if does not exist
dbfile= "/Users/Stian/src/folders2web/pubs.sqlite"
DB = Sequel.sqlite(dbfile)
dbase = DB[:items]
puts "BIBTEX<<<#{dbase.filter[:hash => hash.strip][:bibtex]}>>>"
