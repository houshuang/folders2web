#!/usr/bin/ruby
# encoding: UTF-8

$:.push(File.dirname($0))
require 'utility-functions'
require 'wiki-lib'
require 'iconv'
require 'appscript'
include Appscript


def format(type, text, page, citekey)
  highlight = case type
    when "Sticky Note"
      "::"
    when "Underline-standalone"
      ":::"
    else
      ""
  end
  text.strip!
  text.gsub!(/[•]/, "\n  * ")

  return "#{highlight}#{text}#{highlight} [[skimx://#{citekey}##{page}|p. #{page}]]\n\n"
end

def import_file(filename, citekey)
  a = File.open(filename, 'rb:UTF-16LE')
  c = a.read
  ic = Iconv.new('UTF-8//IGNORE', 'UTF-16LE')
  a = ic.iconv(c[1..-1])
  a += "\n"

  page = 0
  type = ''
  out = ''
  text = ''

  a.each_line do |line|
    line = line.strip

    if line =~ /Author: (.+?) Subject: (.+?)  /
      type = $2
      next
    end

    if line =~ /^Page: (\d+?)/
      page = $1.to_i
      next
    end

    # empty line, check if content, if so, add to out
    if line == '' || line == "----------------------------------------------------------------------------------------------------"
      if text.size > 0 && type != ''
        out << format(type, text, page, citekey)
      end
      text = ''
      type = ''
      next
    end

    text << line + " "

  end

  page = "clip:#{citekey}"
  dwpage(page, out, "Automatically added text from PDF Expert")

end

puts "Looking for files to import from #{PDFExpert_path}."
Dir.glob(PDFExpert_path + "/*.txt").each do |file|
  citekey =  File.basename(file).remove(".txt")
  import_file(file, citekey)
  puts "Imported #{citekey}"
end