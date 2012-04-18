# encoding: UTF-8
$:.push(File.dirname($0))
require 'utility-functions'
require 'pp'

#a = File.read('/Users/Stian/Dropbox/PhD/Litreview April 2012.taskpaper')

a="
Paragogy:
[@corneli2012detecting] math learning online @online
\tlearning model @tomato
\t\tA naıve idea: model learning as vocabulary acquisition @p2pu
\t\tMore complete: model learning as a change in patterns of behavior
\t\tSophisticated: model learning in terms of the use of new heuristic strategies
\tevaluate learning - look for @peter
\t\tWorking at the cutting edge (introducing new material?) @onlyone
\t\tProgressive problem solving (working at increasing depth?)
\t\tCollaborative effort (asking or answering questions?)
\t\tUser-Identified high points (bookmarked threads and articles?)
\t\tProcessing level (eg browsing vs attempting to solve problems?)
\t\tQuality of self-explanation (knowing how activities relate to goals?)
\t\tExploration (trying different resources)
[@corneli2010crowdsourcing] ActiveMath as repository and community for math learning @math
\thello
\thi"

ckey = ''
tag = Array.new
level = 0
textary = Array.new
tags = Hash.new
a.lines.each do |l|
  tabs = $1 if /^(\t*)/ =~ l
  curlevel = (defined? tabs) ? tabs.size : 0

  unless curlevel < level # unless there are sub-headers, process any tags and clean out current level textary
    puts "curlevel < level"
    if tag[curlevel]  # if there is a tag at a higher level
      puts "tag[curlevel]"
      text = try {textary[curlevel].join("\n").strip.remove("[@#{ckey}]")}
      tags.add(tag[curlevel], "#{text} [@#{ckey}]\n\n")
#      puts "Removing #{curlevel}: #{textary[curlevel]}"
    end

    textary[curlevel] = nil
    tag[curlevel] = nil
  end
  (curlevel+1..textary.size-2).each {|x| textary[x] = nil } # empty all textary strings from this level and up

  tag[curlevel] = $1 if /[ \n]\@(.+?)[ \n]/ =~ l  # recognize a @tag
  puts "tag[curlevel]: #{tag[curlevel]}" if tag[curlevel]
  ckey = $1 if /^\[@(.+?)\]/ =~ l # get citekey and keep it until it changes

  puts "Curlevel #{curlevel} Level #{level}: #{l}"
  pp "Tags: ", tags
  pp "Tag: ", tag
  pp "Textary: ",textary
  puts "*" * 50

  #puts "Curlevel: #{curlevel} Level #{level}"
  #puts "adding to #{x} (which is now #{textary[x]}): #{l}";
  (curlevel+1).times {|x| puts "adding to level #{x}";textary.add_safe(x, l)}  # add textary to all levels up to present level

  level = curlevel
#  textary.each_with_index {|x,y| puts "#{y}: #{x}"}
#  puts "*" * 50
end

puts "="* 80

tags.each {|x,y| puts "#{x}:\n#{y[0].to_s}\n#{"-"*50}\n"}