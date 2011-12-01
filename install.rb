# README: These are constants which will have to be modified for every install, please modify the paths below and
# save this file as settings.rb. The reason I am distributing this as settings-dist.rb is so that your modified
# settings.rb will not be overwritten the next time you update researchr.

# constants
$:.push(File.dirname($0))
require 'utility-functions'
require 'pashua'
include Pashua

questions = [
  ["Wiki_path","Path to your wiki installation", "/Library/WebServer/Documents/wiki",:directory],
  ["PDF_path","Directory where BibDesk stores the PDFs","#{Home_path}/Documents/Bibdesk",:directory],
  ["Bibliography","The database file that BibDesk uses (typically called Bibliography.bib)","",:file],
  ["Downloads_path","Directory where you store your downloads","#{Home_path}/Downloads",:directory],
  ["Internet_path","URL to your wiki when it's on the server (for example http://reganmian.net/wiki)","http://",:text],
  ["Wiki_title","The title of your wiki (for the RSS feed)","My Research Wiki",:text],
  ["Wiki_desc","The description of your wiki (for the RSS feed)","Raw research notes and article annotations related to online collaborative learning",:text]
]

pash = ""
questions.each do |q|
  case q[3]
  when :directory
    pash << "#{q[0]}.type = openbrowser\n#{q[0]}.filetype = directory\n"
  when :file  
    pash << "#{q[0]}.type = openbrowser\n#{q[0]}.filetype = bib\n"
  when :text
    pash << "#{q[0]}.type = textfield\n"
  end
  pash << "#{q[0]}.default = #{q[2]}\n#{q[0]}.label = #{q[1]}\n"    

end
answers = pashua_run pash
settings = "# Settings generated automatically by install.rb. You can modify these manually, or rerun install.rb\n"
settings << "# If you rerun install.rb, this file will be overwritten. \n\n"
answers.each do |x|
  settings << "#{x[0]} = \"#{x[1]}\"\n"
end
File.write("#{Script_path}/settings.rb", settings)
growl "Your settings have been modified. You can rerun this at any point. "