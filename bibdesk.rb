# encoding: UTF-8

# researchr scripts relevant to BibDesk (the right one is executed from the bottom of the file)

$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'

#### utilities ####


#### keyboard commands ####

# email selected files to Kindle - needs some polish
# launched by ctrl+alt+cmd+K
def send_to_kindle
  require 'mail-lib'
  Selection.each do |dd|
    docu = dd.cite_key.get
    title = dd.title.get.gsub(/[\{|\}]/,"")
    authors = dd.author.name.get.join(", ")
    `/usr/local/bin/pdftotext "#{PDF_path}/#{docu}.pdf" /tmp/#{docu}.txt`
    `ebook-convert /tmp/#{docu}.txt /tmp/#{docu}.mobi`
    `ebook-meta /tmp/#{docu}.mobi -t "#{title} [#{docu}]" -a "#{authors}" --category="Bibdesk"`
    `cp /tmp/#{docu}.mobi /Volumes/Kindle/documents`
  #  mail_file("/tmp/[#{docu}].pdf")
  end
  growl("#{d.size} file(s) sent")
end

# properly formats an author list for BibDesk with " and " as separator
# launched by ctrl+alt+cmd+P
def authorlist
  a = pbpaste.strip
  a.force_encoding("ISO-8859-1")

  # determine whether to split on newline, space or comma
  if a.scan(";").size > 1
    splt = ";"
  elsif a.scan(",").size > 2
    splt = ","
  end

  a= a.split(splt).join("||").gsub(" and ","").gsub("&","").gsub("||", " and ").gsub(/ +/," ").gsub(/\(.+?\)/, '')
  pbcopy(a)
end

# put on the clipboard a properly formatted list of references to the currently selected publications
# launched by ctrl+alt+cmd+C
def copy
  out = ''

  Selection.each do |dd|
    docu = dd.cite_key.get
    docu.strip!
    out << "[@#{docu}] "
  end

  pbcopy(out.strip)
  growl("#{Selection.size} citation references copied to the clipboard")
end

# attaches the last added PDF in the download directory to the currently selected Bibdesk reference
# launched by ctrl+alt+cmd+L
def linkfile
  curfile =  File.last_added("#{Downloads_path}/*.pdf")
  fail "Sorry, no PDFs found in that directory" unless curfile # no last file found

  f = MacTypes::FileURL.path(curfile)
  Selection[0].linked_files.add(f,{:to =>Selection[0]})
  Selection[0].auto_file

  growl("PDF added", "File added successfully to #{Selection[0].cite_key.get}")
end


# if cannot manage to overwrite a publication, grab PDF, delete publication, create new publication from
# bibtex import, attach PDF, and autofile
def get_bibtexnet
  require 'open-uri'

  growl "Bibtexnet", "Trying to acquire metadata for #{Selection.size} publication(s)"

  c = 0

  Selection.each do |item|
<<<<<<< Updated upstream
    file = try {item.linked_files.get[0].to_s}

    next unless file && File.exists?(file)
=======
    begin
      file =  item.linked_files.get[0].to_s
    rescue
      next
    end

    next unless File.exists?(file)
>>>>>>> Stashed changes

    hash = hashsum(file)
    bibtex = open("http://localhost/bibtexnet/#{hash}").read

  # ensure proper reply
    next unless bibtex.index("BIBTEX<<<")

    btxstring = bibtex.match(/BIBTEX\<\<\<(.+?)\>\>\>/m)[1]
<<<<<<< Updated upstream
    newpub = try {BibDesk.documents[0].import(BibDesk.documents[0], {:from => btxstring})[0]}
=======
    begin
      newpub = BibDesk.documents[0].import(BibDesk.documents[0], {:from => btxstring})[0]
    rescue
      next
    end
>>>>>>> Stashed changes

    f = MacTypes::FileURL.path(file)
    newpub.linked_files.add(f,{:to =>newpub})
    newpub.auto_file

    item.remove
    c += 1
  end

  growl "Successfully imported metadata for #{c} publications from bibtexnet"
end

# uses quicklook to preview the PDF of the currently selected publication
# launched by cmd+space
def qlook
  file =  Selection[0].linked_files.get[0].to_s
  if File.exists?(file)
    `qlmanage -p '#{file}'`
  else
    growl('No file available', "No file available for #{Selection[0].cite_key.get}")
  end
end

# opens a given reference as passed by bibdesk:// URL in BibDesk
# launched by bibdesk:// url in Chrome (bibdesk.app must be registered first)
def url(argv)
  arg = argv[11..-1]
  find = BibDesk.search({:for => arg})
  unless find == []
    find[0].show
    BibDesk.activate
  else
    growl("File not found", "Cannot find citation #{ARGV[0]}")
  end
end

# makes sure that all selected citations have corresponding wiki pages, and opens the first selected citation in Chrome
# launched by ctrl+alt+cmd+E
def open_page
  require 'wiki-lib'
  ary = Array.new
  Selection.each do |dd|
    docu = dd.cite_key.get
    ary << docu unless File.exists?("#{Wiki_path}/data/pages/ref/#{docu}.txt")
    ensure_refpage(docu)
  end
  `open http://localhost/wiki/ref:#{Selection[0].cite_key.get}`
end

#### Running the right function, depending on command line input ####
BibDesk = Appscript.app('BibDesk')
Selection = BibDesk.document.selection.get[0]
send *ARGV
