$:.push(File.dirname($0))
require 'utility-functions'
require 'appscript'
require 'anystyle/parser'

pbcopy (Anystyle.parse(pbpaste, :bibtex).to_s)