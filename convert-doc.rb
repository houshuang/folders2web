# encoding: UTF-8
$:.push(File.dirname($0))
require 'pp'
require 'bibtex'
require 'citeproc'
require 'utility-functions'

# processes a text file and converts all citations, and generates a bibliography - inspired by pandoc

def get_citation(citekey)
  item = B[citekey.to_sym]
  cit = CiteProc.process item.to_citeproc, :style => :apa
  year = (defined? item.year) ? item.year.to_s : "n.d."
  if year == "n.d." and cit.match(/\((....)\)/) 
    year = $1
  end
  ax = []
  if item.respond_to? :author
    item.author.each do |a|
      ax << a.last.gsub(/[\{\}]/,"")
    end
  end
  
  names = namify(ax)
  return [cit, names, year]
end
  
B = BibTeX.open("/Volumes/Home/stian/Dropbox/Archive/Bibliography.bib")
B.parse_names

doc = "<html><head></head><style>
<!--
 /* Font Definitions */
@font-face
	{font-family:Times;
	panose-1:2 0 5 0 0 0 0 0 0 0;}
h1
	{margin-right:0cm;
	margin-left:0cm;
	font-size:12.0pt;
	font-family:Times;
	font-weight:bold;}
  	@font-face
	{font-family:Times;
	panose-1:2 0 5 0 0 0 0 0 0 0;}
 /* Style Definitions */
p.MsoNormal, li.MsoNormal, div.MsoNormal
	{margin:0cm;
	margin-bottom:.0001pt;
	font-size:10.0pt;
	font-family:Times;}
span.Heading1Char
	{font-family:Calibri;
	color:#345A8A;
	font-weight:bold;}
.MsoChpDefault
	{font-size:10.0pt;}
@page WordSection1
	{size:612.0pt 792.0pt;
	margin:72.0pt 90.0pt 72.0pt 90.0pt;}
div.WordSection1
	{page:WordSection1;}
-->
</style>
<body>\n" +  File.read(ARGV[0])

citations = Hash.new
doc.scan( /\@([a-zA-Z]+[0-9]+[a-zA-Z]+)/).each do |hit|
  hit = hit[0]
  citations[hit] = get_citation(hit) unless citations[hit]
  doc.gsub!("@#{hit}", citations[hit][1] + ", " + citations[hit][2])
end
doc.gsub!(/^# (.+?)$/, '<h1 style="font size:12.0pt; font-weight: bold">\1</h1>')
doc.gsub!(/^## (.+?)$/, '<h2>\1</h2>')
doc.gsub!(/^### (.+?)$/, '<h3>\1</h3>')
doc.gsub!(/^#### (.+?)$/, '<h4>\1</h4>')


doc << "\n\n<h1>References:</h1>\n"
citations.sort.each do |item|
doc << "<p  style='margin-left:1.0cm;text-indent:-1.0cm'><span style='font-size:12.0pt;
  font-weight:normal'>"  +item[1][0] + "</h1></style>\n"
end
doc.gsub!("\n","</p><p class=MsoNormal style='margin-bottom:12.0pt'><span style='font-size:12.0pt'>")

puts doc