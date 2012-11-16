#!/usr/bin/ruby
# encoding: UTF-8

$:.push(File.dirname($0))
require 'utility-functions'
require 'wiki-lib'
require 'appscript'
include Appscript


def format(type, text, page, citekey)
  highlight = case type
    when "Text Note"
      "::"
    when "Underline-standalone"
      ":::"
    else
      ""
  end
  text.strip!
  text.gsub!(/[•]/, "\n  * ")
  text = text[1..-2]

  return "#{highlight}#{text}#{highlight} [[skimx://#{citekey}##{page}|p. #{page}]]\n\n"
end

def import_file(filename, citekey)
  a = File.read(filename)
  page = 0
  type = ''
  out = ''

  a.each_line do |line|
    line = line.remove("\t").strip

    case type
      when 'Highlight'
        out << format("Text", line, page, citekey)
      when 'Underline'
        out << format("Underline-standalone", line, page, citekey)
      when 'Note'
        out << format("Text Note", line, page, citekey)
      when "and Note"
        out << format("Text Note", line, page, citekey)
    end

    # capture page number
    page = $1.to_i if line =~ /PAGE (\d+?)\:/

    if ( line == "Highlight" || line == "Underline" || line == "Note"  || line == "and Note")
      type = line
    else
      type = ''
    end
  end

  page = "ref:#{citekey}"

  dwpage(page, out, "Automatically added text from PDF Expert")

end

puts "Looking for files to import from #{PDFExpert_path}."
Dir.glob(PDFExpert_path + "/*.txt").each do |file|
  citekey =  File.basename(file).remove(".txt")
  import_file(file, citekey)
  puts "Imported #{citekey}"
end