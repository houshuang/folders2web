# encoding: UTF-8
require 'appscript'
include Appscript
require 'pp'

search = %q|re in the mind as propositions, mental images, and so forth, while external representations refer to the visualization of the learnersí thinking, such as physical symbols, or diagrams consisting of nodes and links (Hayes, 1989; Ellis & Siegler, 1994).
However, ìexternal representations are not simply peripheral aidsî (Zhang, 1991, p.1). This is because cognition is concerned with represen- tational states, and people can develop their understanding depending on how representations act as intermediaries in dynamically evolving collabo- rative processes.
280	Lee and Kim
Moreover, the relations of external representation and internal mental representation are interwoven, leading learners to solve given problems in a dynamic manner (Zhang, 1997). Roschelle and Teasley (1995) suggest- ed that the representation tool and the collaborative learning process could be designed together for more effective learning. That is, the two factors can be combined with a more powerful instructional representation tool resulting. In fact, as Suthers (2000) pointed out, ìrepresentation tools mediate collaborative learning interactions by providing learners with the means to express their emerging knowledge in a persistent medium, inspectable by all participants, where the knowledge then becomes part of the shared contextî (p. 2).
In addition to primary ideas about the representation tool defined by Suthers (2000), the CRST, the PBL-net schema in Figure 2, refers to the graphical external representation tool to support learners to display, orga- nize, and reorganize their thoughts in PBL processes. Learners can easily interact with one another and reach a mutual understanding as they work with group members. Specifically, the CRST reflects ongoing learning activ- ities that learners perform according to the six phases of PBL. It also becomes a resource for further conversation and elaborated argumentation rather than a simple delivery tool. When utilizing the CRST, learners are required to construct representation diagrams by using a restricted set of typed nodes and typed links. All nodes and links reflect their thoughts and relationship between their thoughts. Considering continuous changes of our thoughts, the CRST is composed of different kinds of typed nodes and typed links according to six phases of PBL. Through constructing the CRST, learn- ers are expected to effectively externalize their opinions and directly per- ceive and use a lot of information.
OTHER PBL SUPPORT SYSTEMS STUDIES ON SUPPORTING PROBLEM-BASED LEARNING
The CRST supports collaborative learning discourse by providing learn- ers with the means to visualize their emerging knowledge in a constant tool. When using the CRST, learners can freely express their own opinions whether they are right or not. Namely, learners can reflect their internal rep- resentations on a physical medium by externalizing their thoughts (Zhang, 1997). The socially constructed CRST reflects the status of consistent, con- flicting, and complementary knowledge among participants. The CRST in turn stimulates individualsí cognitive processes and initiates further social learning interaction. So, if an external representation tool that perfectly reflects learnersí thoughts can be developed, learners will be able to interact with one another without any restraint of communication. Because of these reasons, the study of representation tools has received extensive attention by
The Effects of the Collaborative Representation Supporting Tool	281
researchers using different representation tools for similar objectives. This study analyzes the following six main PBL supporting systems, focusing on whether they support learnersí external representation or not and how they support it. The comparison of existing PBL support systems is next.
As shown in Table 1, the representation supports of ideas and relations in major PBL supporting systems have some limitations in order to scaffold a variety of learning activities that can occur in whole collaborative PBL processes (Miao, 2000). Only Belvedere provided partial representation sup- ports of relations between ideas such as 1) against, 2) for. Most PBL sup- porting systems were designed and developed not considering representa- tion supports of relations between ideas, but considering some representa- tion supports of|

# given an array of sizes, a start, and an end point, returns for each page the start and endpoint for a selection
def find_loc(a,start,endd)
  endpos = start + endd
  pos  = res= 0
  phigh = []

  # find startpoint
  a.size.times do |count|
    pos += a[count]
    puts "#{pos} > #{start}"
    if pos > start
      res = count+1
      if pos > endd 
        phigh[count] = [count,  start - (pos - a[count]), endd - (pos - a[count])]
        return phigh
      else
        phigh[count] = [count,  start - (pos - a[count]), a[count]]
        break
      end
    end
  end

  # find endpoint
  (a.size-res).times do |count|
    puts count+res
    pos += a[count+res]
    puts "#{pos} >> #{start}"
    if pos > endd
      phigh << [count+res,  0, endd - (pos - a[count])]
      break
    else
      phigh << [count+res,  0, a[count+res]]    
    end
  end
  return phigh
end

# reduce a string to only a-z
def alpha(string)
  return string.downcase.gsub(/[^a-z]/,"")
end

# like alpha, but mapping new locations to old locations
def process(string)
  c = cn = 0
  chars = Array.new
  an = ''
  string.downcase.split("").each do |char|
    if char =~ /[a-z]/
      chars[cn] = c
      an << char
      cn +=1
    end
    c+= 1
  end
  return an, chars
end

# ======================

dt = app("Skim")
doc = dt.open("/Volumes/Home/stian/Documents/Bibdesk/lee2005effects.pdf")
search = alpha(search)

# go through each page, extract characters, record page markers, and concatenate to one string
pages = Array.new
positions = Array.new
counter = 0
document = ''
doc.pages.get.each_with_index do |page, idx|
  p = page.characters.get.join
  document << p
  pages[idx] = p.size
  counter += p.size
  positions[idx] = counter
end

documentconv, oldnew= process(document)
File.open("tmp","w"){|f|f<< "#{search}\n\n#{documentconv}"}
exit

idx = documentconv.index(search)
unless idx 
  puts "Could not find it"
  exit
end
puts "#{oldnew[idx]} : #{oldnew[idx+search.size]}"
positions.each_with_index {|p,i| puts "#{i}: #{p}"}