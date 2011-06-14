require 'amatch'
include Amatch

  def scanner( input )
    out = [] unless block_given?
    pos = 0
    input.scan(/(\w+)(\W*)/) do |word, white|
      startpos = pos
      pos = word.length + white.length
      if block_given?
        yield startpos, word
      else
        out << [startpos, word]
      end
    end
    return out
  end

  def find( text, doc )
    index = scanner(doc)
    sstr = text.gsub(/\W/,'')
    levenshtein = Levenshtein.new(sstr)
    minlen = sstr.length
    maxndx = index.length
    possibles = []
    minscore = minlen*2
    p index
    index.each_with_index do |x, i|
      spos = x[0]
      str = x[1]
      si = i
      while (str.length < minlen)
        i += 1
        break unless i < maxndx
        str += index[i][1]
      end
      str = str.slice(0,minlen) if (str.length > minlen)
      score = levenshtein.search(str)
      if score < minscore
        possibles = [spos]
        minscore = score
      elsif score == minscore
        possibles << spos
      end
    end
    [minscore, possibles]
  end

string1 = %q|We saw in the cases of other East Asian countries how personal relationships often played a key role in introducing the idea of OpenCourseWare to new societies, and this is true even in China as far as the limited role of China Open Resources for Education goes. This is quite consistent with much of the literature on policy diffusion. For example, Mintrom (1997) has studied how policy entrepreneurs can play a role in spreading policy between states in the US, and also how membership in professional organizations and networks can assist this spreading of norms. He defines policy entrepreneurs as “people who seek to initiate dynamic policy change”, which they do through attempting to win support for ideas for policy innovations.
The strategies available to them are identifying problems, networking in policy circles, shaping the terms of the policy debates and building coalitions. They also face the challenge of crafting arguments differently for different audiences, while maintaining an image of integrity.|

string2 = "He defines policy entrepreneurs as people who seek to initiate dynamic policy change, which they do thro-ugh attempting to win  32 support for ideas for policy innovations."

p find(string2,string1)