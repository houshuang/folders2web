# encoding: UTF-8
$:.push(File.dirname($0))
require 'pp'
require 'utility-functions'

# processes a text file and converts all citations, and generates a bibliography - inspired by pandoc
  
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

bib = json_bib
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