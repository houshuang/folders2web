# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'bibtex'
require 'citeproc'
require 'find'
require 'rss/maker'
require 'sanitize'

# batch processes entire bibliography file and generates ref:bibliography in wiki, used for refnotes database

log "Started bibtex-batch on #{Time.now}"

# trigger generating different categories of pages - takes a long time
authoropt = true
journalopt = true
keywordopt = true
#######################################################################

try { dt = app('BibDesk') }
try { dt.document.save }

def sort_pubs(pubs)
  return pubs.sort {|x,y| x.to_s.scan(/[0-9]+/)[0].to_i <=> y.to_s.scan(/[0-9]+/)[0].to_i}
end

def make_rss_feed
  fname = Wiki_path + "/rss-temp"
  rss_entries = Marshal::load(File.read(fname))

  version = "2.0" # ["0.9", "1.0", "2.0"]

  urls = Array.new

  content = RSS::Maker.make(version) do |m|
    m.channel.title = Wiki_title
    m.channel.link = Internet_path
    m.channel.description = Wiki_desc
    m.items.do_sort = true # sort items by date

    rss_entries.each do |entry|
      next if urls.index(entry[:link]) # avoid duplicate content (should not be possible, but just in case)
      urls << entry[:link]

      i = m.items.new_item
      i.title = entry[:title]
      puts "--#{i.title}"
      i.link = entry[:link]
      i.date = entry[:date]
      i.description = Sanitize.clean( entry[:description], Sanitize::Config::RELAXED )
    end
  end

  # make sure all links point to the online version, not the localhost
  feedcontent = content.to_s.gsub(Internet_path, Server_path)

  File.write(Wiki_path + "/feed.xml", feedcontent)
end

timetot = Time.now
timetmp = Time.now

if defined?(Make_RSS) && Make_RSS
  puts "Making RSS feed"
  make_rss_feed
  puts "RSS feed complete (will be updated next time you sync with server) (#{Time.now - timetmp} s.)"
end

timetmp = Time.now
puts "Parsing BibTeX"
b = BibTeX.parse(File.read(Bibliography))
b.parse_names
puts "Initial parse complete (#{Time.now - timetmp} s.)"
timetmp = Time.now
out1 = ''
out2 = ''
out3 = ''
out4 = ''
authors = Hash.new
json = Hash.new
keywords = Hash.new
journals = Hash.new

counter = Hash.new
counter[:hasref] = 0
counter[:noref] = 0
counter[:notes] = 0
counter[:clippings] = 0
counter[:images] = 0
counter[:pdf] = 0



puts "Starting secondary parse"
b.each do |item|
  next unless try { item.title } # fragment, doesn't need its own listing
  ax = []
  if (item.respond_to? :author) && (item.author != nil)
    item.author.each do |a|
      next if item.author == nil #thanks Bodong
      authors[nice_name(a)] = Array.new unless authors[nice_name(a)]
      ax << a.last.gsub(/[\{\}]/,"")
      authors[nice_name(a)] << item.key
    end
  end
  if item.respond_to? :keywords
    item.keywords.split(";").each do |a|
      a.strip!
      keywords[a] = Array.new unless keywords[a]
      keywords[a] << item.key
    end
  end
  if item.respond_to? :journal
    j = item[:journal].to_s
    journals[j] = Array.new unless journals[j]
    journals[j] << item.key
  end

  cit = CiteProc.process item.to_citeproc, :style => :apa
  cit.gsub!(/Retrieved from(.+?)$/, '')
  item[:cit] = cit
  year = (defined? item.year) ? item.year.to_s : "n.d."
  if year == "n.d." and cit.match(/\((....)\)/)
    year = $1
  end
  json[item.key.to_s] = [namify(ax), year, cit, item.title, item[:"oa-url"].to_s]

  hasfiles = Array.new
  hasfiles[2] = '' # ensure that array is filled even if some fields are empty, for alignment

  if File.exists?("#{Wiki_path}/data/pages/ref/#{item.key}.txt")
    counter[:hasref] += 1
    if File.exists?("#{Wiki_path}/data/pages/clip/#{item.key}.txt") || File.exists?("#{Wiki_path}/data/pages/kindle/#{item.key}.txt")
      counter[:clippings] += 1
      hasfiles[1] = "C"
    end
    if File.exists?("#{Wiki_path}/data/pages/skimg/#{item.key}.txt")
      counter[:images] += 1
      hasfiles[2] = "I"
    end
    if item.respond_to? :"oa-url"
      pdfurl = "[[#{item[:"oa-url"]}|{{wiki:filetype_pdf.png}}]]"
      counter[:pdf] += 1
    else
      pdfurl = ''
    end
    if File.exists?("#{Wiki_path}/data/pages/notes/#{item.key}.txt")
      counter[:notes] += 1
      hasfiles[0] = "N"
    end

    txt = "| [#:ref:#{item.key}|#{item.key}] #{pdfurl}| #{hasfiles.join(" | ")} |#{cit}|\n"

    if hasfiles[0] == "N"
      out1 << txt
    elsif hasfiles[1] == "C"
      out2 << txt
    end
  end

  # mark as read if notes exist
  # if File.exists?("#{Wiki_path}/data/pages/clip/#{item.key}.txt") || File.exists?("#{Wiki_path}/data/pages/kindle/#{item.key}.txt")
  #   dt.document.search({:for =>item.key.to_s})[0].fields["Read"].value.set("1")
  # end

end

puts "Finished secondary parse, generating main bibliography (#{Time.now - timetmp} s.)"
timetmp = Time.now

out = "h1. Bibliography\n\nDownload [[http://dl.dropbox.com/u/1341682/Bibliography.bib|entire BibTeX file]].
Also see bibliography by [[abib:start|author]] or by [[kbib:start|keyword]].\n\nPublications that have their
own pages are listed on top, and hyperlinked. Most of these also have clippings and many have key ideas.\n\n
Statistics: Totally **#{counter[:hasref] + counter[:noref]}** publications, and **#{counter[:hasref]}**
publications have their own wikipages. Of these, **#{counter[:images]}** with notes (key ideas) **(N)**,
**#{counter[:clippings]}** with highlights (imported from Kindle or Skim) **(C)**, and **#{counter[:images]}**
with images (imported from Skim) **(I)**. **#{counter[:pdf]}** publications are OA, and available for immediate download.\n\n"

#dt.document.save

File.open("#{Wiki_path}/lib/plugins/dokuresearchr/json.tmp","w"){|f| f << JSON.fast_generate(json)}

out << out1 << out2 << out3
File.open("#{Wiki_path}/data/pages/bib/bibliography.txt", 'w') {|f| f << out}

###############################################
# generate individual files for each author

if authoropt
puts "Generating individual files for each author"
authorlisted = Array.new
authors.each do |axx, pubs|
  out =''
  out1 = ''
  out2 =''
  author = axx.strip

  # only generates individual author pages for authors with full names. this is because I want to deduplicate author names
  # when you import bibtex, you get many different spellings etc.
  next if (author.strip[-1] == "." || author[-2] == " " || author[-2] == author[-2].upcase || author[1] == '.')
  out = "h2. #{author}'s publications\n\n"
  sort_pubs(pubs).each do |i|
    item = b[i]
    if File.exists?("#{Wiki_path}/data/pages/ref/#{item.key}.txt")
      out1 << "| [@#{item.key}] | #{item[:cit]}|\n"
    else
      out2 << "| [@#{item.key}] | #{item[:cit]}|\n"
    end
  end

  out << out1 << out2
  authorname = clean_pagename(author)
  authorlisted << [authorname,author,pubs.size]
  File.open("#{Wiki_path}/data/pages/abib/#{authorname}.txt", 'w') {|f| f << out}
end

File.open("#{Wiki_path}/data/pages/abib/start.txt","w") do |f|
  f << "h1.List of authors with publications\n\nList of authors with publications. Only includes authors with three or more publications, with full names.\n\n"
  authorlisted.sort {|x,y| y[2].to_i <=> x[2].to_i}.each do |ax|
    apage = ''
    if File.exists?("#{Wiki_path}/data/pages/a/#{ax[0]}.txt")
      apage = "[#:a:#{ax[0]}|author page]"
    end
    f << "| [##{ax[0]}|#{ax[1]}] | #{apage} |#{ax[2]}|\n"
  end
end
end
###############################################
# generate individual files for each keyword
puts "Finished (#{Time.now - timetmp} s.)"
if keywordopt
  timetmp = Time.now
  puts "Generating individual files for each keyword"

  keywordslisted = Array.new
  keywords.each do |keyword, pubs|
    out =''
    out1 = ''
    out2 =''
    out = "h2. Publications with keyword \"#{keyword}\"\n\n"
    sort_pubs(pubs).each do |i|
      item = b[i]
      if File.exists?("#{Wiki_path}/data/pages/ref/#{item.key}.txt")
        out1 << "| [@#{item.key}] | #{item[:cit]}|\n"
      else
        out2 << "| [@#{item.key}] | #{item[:cit]}|\n"
      end
    end

    out << out1 << out2
    kwname = keyword.gsub(/[\,\.\/ ]/,"_").downcase
    keywordslisted << [kwname,keyword,pubs.size]
    File.open("#{Wiki_path}/data/pages/kbib/#{kwname}.txt", 'w') {|f| f << out}
  end

  File.open("#{Wiki_path}/data/pages/kbib/start.txt","w") do |f|
    f << "h1. List of publication keywords\n\n"
    keywordslisted.sort {|x,y| y[2].to_i <=> x[2].to_i}.each do |ax|
      f << "|[##{ax[0]}|#{ax[1]}]|#{ax[2]}|\n"
    end
  end
  puts "Finished (#{Time.now - timetmp} s.)"

end
###############################################
# generate individual files for each journal with more than five cits.

if journalopt
  timetmp = Time.now
  puts "Generating individual files for each journal"

authorlisted = Array.new
journals.each do |axx, pubs|
  out =''
  out1 = ''
  out2 =''
  author = axx.strip
  next unless pubs.size > 5
  # only generates individual author pages for authors with full names. this is because I want to deduplicate author names
  # when you import bibtex, you get many different spellings etc.
  out = "h2. Publications in #{author}\n\n"
  sort_pubs(pubs).each do |i|
    item = b[i]
    if File.exists?("#{Wiki_path}/data/pages/ref/#{item.key}.txt")
      out1 << "| [@#{item.key}] | #{item[:cit]}|\n"
    else
      out2 << "| [@#{item.key}] | #{item[:cit]}|\n"
    end
  end

  out << out1 << out2
  authorname = clean_pagename(author)
  authorlisted << [authorname,author,pubs.size]
  File.open("#{Wiki_path}/data/pages/jbib/#{authorname}.txt", 'w') {|f| f << out}
end
end

File.open("#{Wiki_path}/data/pages/jbib/start.txt","w") do |f|
  f << "h1.List of journals with publications\n\nList of journals with publications. Only includes journals with five or more publications.\n\n"
  authorlisted.sort {|x,y| y[2].to_i <=> x[2].to_i}.each do |ax|
    apage = ''
    if File.exists?("#{Wiki_path}/data/pages/a/#{ax[0]}.txt")
      apage = "[#:a:#{ax[0]}|author page]"
    end
    f << "| [##{ax[0]}|#{ax[1]}] | #{apage} |#{ax[2]}|\n"
  end
  puts "Finished (#{Time.now - timetmp} s.)"
end


###############################################
# generate file with imported citations missing key ideas

# pages = Array.new
# out = "h1.Needs key ideas\n\nList of publications with clippings, which do not have key ideas.\n\n"
#
# Find.find("#{Wiki_path}/data/pages/ref") do |path|
#   next unless File.file?(path)
#   fn = File.basename(path)
#   if (File.exists?("#{Wiki_path}/data/pages/kindle/#{fn}") || File.exists?("#{Wiki_path}/data/pages/clip/#{fn}")) && !File.exists?("#{Wiki_path}/data/pages/notes/#{fn}")
#     out << "  * [@#{fn[0..-5]}]\n"
#   end
# end
# File.open("#{Wiki_path}/data/pages/bib/needs_key_ideas.txt","w") {|f| f << out}

puts "All tasks finished. Total time #{Time.now - timetot} s."