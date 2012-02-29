# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'

require 'bibtex'
require 'latex/decode'
require 'prettyprint'
require 'iconv'


def cleanup_bibtex(cit)
	b = BibTeX::parse(cit, :filter => :latex)
	b[0][:author].convert!(:fix_namecase)
	b.each do |e| 
		e.key = e.std_key 
	end
	b.to_s
end

cit= '@article{cakir2002effectiveness,title={Effectiveness of conceptual change text-oriented instruction on students\' understanding of cellular respiration concepts},author={{\c{C}}akir, {\"O}.S. and Geban, {\"O}. and Y{\"u}r{\"u}k, N.},journal={Biochemistry and Molecular Biology Education},volume={30},number={4},pages={239--243},year={2002},publisher={Wiley Online Library}},keywords={keyword1, keyword2},'

pbpaste

# first fix key, because bibtex chokes on unicode keys
cit.gsub!(/\@(.+?)\{(.+?)\,(.+?)$/m, '@\1{key,\3')

newcit = cleanup_bibtex(cit)
pbcopy(cleanup_bibtex(cit))