require 'digest/md5'
require 'digest/sha1'
require 'digest/sha2'
$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
include Appscript
require 'sequel'

# create database if does not exist
dbfile= "/Users/Stian/src/folders2web/pubs.sqlite"
unless File.exists?(dbfile)
  DB = Sequel.sqlite("/Users/Stian/src/folders2web/pubs.sqlite")

  DB.create_table :items do
    primary_key :id
    String :hash
    String :bibtex
  end
else
  DB = Sequel.sqlite("/Users/Stian/src/folders2web/pubs.sqlite")
end

@bd = app("BibDesk").document
@dbase = DB[:items]

### INSERT ENTRY
def insert_entry(fname, bibtx = "")
  hash = hashsum(fname)
  citekey = File.basename(fname)[0..-5]
  unless bibtx.size > 0
    begin
      bibtx = @bd.search({:for =>citekey})[0].BibTeX_string.get.to_s
    rescue
      puts "Citekey #{citekey} not found in BibDesk"
      return -1
    end
  end
  puts "Inserting hash #{hash} for file #{fname}"
  @dbase.insert({:hash => hash, :bibtex => bibtx.to_s})
end


if ARGV[0] == "batch"
  c=0
  @dbase = DB[:items]
  path = "/Volumes/SSDHome/Users/Stian/Bibdesk/*.pdf"
  Dir[path].select  do |f| 
    fname = File.basename(f)
    citekey = fname[0..-5]
    puts citekey
    begin
      bibtx = @bd.search({:for =>citekey})[0].BibTeX_string.get.to_s
    rescue
      puts "Not found"
      next
    end
    hash = hashsum(fname)
    c = c+1
  end
  puts "Total #{c} entries added"
elsif ARGV[0] == "lookup"
  f = ARGV[1]
  hash = hashsum(f)
  puts hash
  result = @dbase.filter[:hash => hash]
  unless result == nil
    puts result[:bibtex]
  else
    puts "Nothing found"
  end
elsif ARGV[0] == "hash"
  puts hashsum(ARGV[1])
elsif ARGV[0] == "insert"
  if ARGV[2].size > 0
    bibtex = File.read(ARGV[2])
  else
    bibtex = ''
  end
  insert_entry(ARGV[1],bibtex)
end