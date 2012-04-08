# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'

# Contains keyboard related functionality which can be invoked from any publication

# triggered through Cmd+. shows a Pashua list of all references with titles
# upon selection, a properly formatted citation like [@scardamalia2004knowledge] is inserted
def bib_selector
  require 'pashua'
  include Pashua

  bib = json_bib

  config = "
  *.title = researchr
  cb.type = combobox
  cb.completion = 2
  cb.label = Insert a citation
  cb.width = 800
  cb.tooltip = Choose from the list or enter another name
  db.type = cancelbutton
  db.label = Cancel
  db.tooltip = Closes this window without taking action"

  # create list of citations
  out = ''
  json_bib.sort.each do |a|
    out << "cb.option = #{a[0]}: #{a[1][3][0..90]}\n"
  end

  # show dialogue
  pagetmp = pashua_run config + out

  exit if pagetmp['cancel'] == 1

  /^(?<citekey>.+?)\:/ =~ pagetmp['cb']  # extract citekey from citekey + title string

  pbcopy("[@#{citekey}]")

end

send *ARGV unless ARGV == []