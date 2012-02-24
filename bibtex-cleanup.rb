# encoding: UTF-8

require 'bibtex'
require 'namecase'
require 'unicode'
require 'latex/decode'
require 'prettyprint'

cit = '@inproceedings{keselj4ppdn,
	Author = {Ke{\v{s}}elj, V. and Cercone, N. and MACDOUGAL, K.D.A.},
	Date-Added = {2012-02-19 23:37:27 +0000},
	Date-Modified = {2012-02-19 23:37:35 +0000},
	Keywords = {keyword1, keyword2, keyword3, keyword4},
	Organization = {Citeseer},
	Title = {PPDN—A Framework f{\aa}r Peer-to-peer Collaborative Research Network},
}'


class Fix_namecase < BibTeX::Filter
	def apply(field)
		temp = NameCase(Unicode::capitalize(field))
	
		# fix problem of initials without space between
		temp.to_s.gsub(/\.([A-Za-z])/, '. \1')
	end
end

b = BibTeX::parse(cit, :filter => :latex)
b[0][:author].convert!(:fix_namecase)

puts b.to_s