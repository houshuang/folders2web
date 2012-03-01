# inspired by Python script from http://www.thamnos.de/misc/look-up-bibliographical-information-from-a-doi/
# returns a bibtex entry 
require 'open-uri'
require 'xmlsimple'

def try(&block)
  begin
    yield block
  rescue
    return false
  end
end

def lookup_doi(doi, crossref_api_key)
  debug = false
  doi = doi.gsub('doi:','').gsub('http://','').gsub('dx.doi.org/','').gsub('doi>','').strip
  url = "http://www.crossref.org/openurl/?id=doi:#{doi}&noredirect=true&pid=#{crossref_api_key}&format=unixref"
  puts url if debug

  html = open(url).read
  puts html if debug
  
  # break if DOI lookup failed, or there was another error message resulting in a HTML page instead of an XML object
  return false if html.index("<error>") || html.index("<head>")

  doc = XmlSimple.xml_in(html)
p doc
  cit = doc['doi_record'][0]['crossref'][0]['journal'][0]
  journal_meta = cit["journal_metadata"][0]
  journal_title = journal_meta['full_title'][0]
  journal_issue = cit["journal_issue"][0]
  date = journal_issue['publication_date'][0]
  year = date['year'][0]
  journal_volume = try {journal_issue['journal_volume'][0]}
  volume = try {journal_issue['volume'][0]}
  issue = try {journal_issue['issue'][0]}
  journal_article = cit['journal_article'][0]
  titles = journal_article['titles'][0]
  title = titles['title'][0]

  contributors = journal_article['contributors'][0]

  first_author_surname = ''
  authorlist = []
  contributors['person_name'].each do |person_name|
    given_name = person_name['given_name'][0]
    surname = person_name['surname'][0]
    authorlist << "#{surname}, #{given_name}" 
    if person_name['sequence'].strip == 'first'
      first_author_surname = surname
    end
  end

  pages = try {journal_article['pages'][0]}
  first_page = try {pages['first_page'][0]}
  last_page = try {pages['last_page'][0]}

  unless pages
    pages = try{journal_article['publisher_item'][0]}
    first_page = try{pages['item_number'][0]}
  end

  out = ''
  firstword = false
  title.split(" ").each do |word|
    if word.size > 3
      firstword = word
      break
    end
  end
  firstword = title.split(" ")[0] unless firstword
  firstword.gsub!(/[;:,.]/,'')
  
  citekey = "#{first_author_surname}#{year}#{firstword}".downcase
  out << "@ARTICLE{#{citekey},\n"
  out << "author = {#{authorlist.join(" and ")}},\n"
  out << "title = {#{title}},\n"
  out << "journal = {#{journal_title}},\n"
  out << "volume = {#{volume}},\n" if volume
  out << "number = {#{issue}},\n" if issue
  out << "year = {#{year}},\n"
  if first_page
    out << "pages = {#{first_page}"
    out << "-#{last_page}" if last_page
    out << "},\n"
  end
  out << "doi = {#{doi}},\n}"

  return out
end