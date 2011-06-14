require 'appscript'
include Appscript
require 'pp'

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
  return {:text => an, :oldnew => chars, :size => string.size}
end

dt = app("Skim")
doc = dt.open(ARGV[0])
search = alpha(ARGV[1])

# go through each page, extract characters, and process
pages = Array.new
oldnew = Array.new
sizes = Array.new
doc.pages.get.each_with_index do |page, idx|
  p = page.characters.get.join
  pages[idx] = process(p)
  sizes[idx] = p.size
  puts idx
end

# first try only one page, then two pages, then three pages together
find = []
text = ''
3.times do |num|
  pages.size.times do |p| 
    pp = [p]
    num.times {|n| pp << p + n + 1}
    text = ''
    pp.each {|pn| text << pages[pn][:text]}
    
    if text.index(search)
      find = pp
      break
    end
  end
  break if find != []
end

# now find contains page numbers, and text the text of the page
idx = text.index(search)
p idx
p find