# encoding: UTF-8
require 'rubygems'
require 'bibtex'
require 'citeproc'
require 'pp'

# batch processes entire bibliography file and generates ref:bibliography in wiki, used for refnotes database

b = BibTeX.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
b.parse_names

out = "h1. Bibliography\n\n^Note name ^ Note text ^\n"
b.each do |item|
  cit = CiteProc.process item.to_citeproc, :style => :apa
  out << "| :ref:#{item.key} | #{cit}|\n"
end

  File.open('/tmp/bibtextmp', 'w') {|f| f << out}  
  `/wiki/bin/dwpage.php -m 'Automatically generated from BibTeX file' commit /tmp/bibtextmp 'ref:bibliography'`


# bib = BibTeX::Bibliography.new
# bib << BibTeX::Entry.new({
#   :type => :book,
#   :key => :rails,
#   :address => 'Raleigh, North Carolina',
#   :author => 'Ruby, Sam and Thomas, Dave, and Hansson, David Heinemeier',
#   :booktitle => 'Agile Web Development with Rails',
#   :edition => 'third',
#   :keywords => 'ruby, rails',
#   :publisher => 'The Pragmatic Bookshelf',
#   :series => 'The Facets of Ruby',
#   :title => 'Agile Web Development with Rails',
#   :year => '2009'
# })
# book = BibTeX::Entry.new
# book.type = :book
# book.key = :mybook
# bib << book
# bib.parse_names
# pp bib[:rails].author

# examples
# >> require 'citeproc'  # requires the citeproc-ruby gem
# => true
#     >> CiteProc.process b[:pickaxe].to_citeproc, :style => :apa
#     => "Thomas, D., Fowler, C., & Hunt, A. (2009). Programming Ruby 1.9:
#       The Pragmatic Programmer's Guide. The Facets of Ruby.
#       Raleigh, North Carolina: The Pragmatic Bookshelf."
#     >> CiteProc.process b[:pickaxe].to_citeproc, :style => 'chicago-author-date'
#     => "Thomas, Dave, Chad Fowler, and Andy Hunt. 2009. Programming Ruby 1.9:
#       The Pragmatic Programmer's Guide. The Facets of Ruby.
#       Raleigh, North Carolina: The Pragmatic Bookshelf."
#     >> CiteProc.process b[:pickaxe].to_citeproc, :style => :mla
#     => "Thomas, Dave, Chad Fowler, and Andy Hunt. Programming Ruby 1.9:
#       The Pragmatic Programmer's Guide. Raleigh, North Carolina:
#       The Pragmatic Bookshelf, 2009."

# >> BibTeX.parse(<<-END)[1].author.map(&:last)
#    @string{ ht = "Nathaniel Hawthorne" }
#    @book{key,
#      author = ht # " and Melville, Herman"
#    }
#    END
# => ["Hawthorne", "Melville"]