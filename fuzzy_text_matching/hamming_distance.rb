# encoding: UTF-8

# researchr scripts relevant to BibDesk (the right one is executed from the bottom of the file)

$:.push(File.dirname($0))
require 'utility-functions'
require 'phash'
require 'yaml'

# class Array
#   def nitems
#     count {|i| !i.nil?}
#   end
# end
# #texts = Phash::Text.for_paths(Dir['/Users/Stian/Bibdesk/*.txt'])
# a = Phash::Text.new('/Users/stian/Bibdesk/ainsworth2003effects.txt')
# b = Phash::Text.new('/Users/stian/Bibdesk/ainsworth2003effectsa.txt')
# comp = a % b
# puts YAML::dump(a)
# # texts.combination(2) do |a,b|
# #   comp = a % b
# #   if comp > 0.8
# #     puts "#{a.path} + #{b.path}: #{comp}"
# #   end
# # end

# require 'simhash'
# include Simhash
# p "hello".simhash
# p Simhash::DEFAULT_STRING_HASH_METHOD

class String

  def shingles
    self.split(//).each_cons(2).map(&:join).uniq
  end

  def simhash
    v = Array.new(64, 0)
    hashes = shingles.map(&:hash)
    hashes.each do |hash|
      hash.to_s(2).chars.each_with_index do |bit, i|
        bit.to_i & 1 == 1 ? v[i] += 1 : v[i] -= 1
      end
    end
    v.map{ |i| i >= 0 ? 1 : 0 }.join
  end

  def hamming_distance(other)
    other_sh = other.simhash
    self.simhash.chars.each_with_index.inject(0) do |total, (bit, i)|
      total += 1 if bit != other_sh[i]
      total
    end
  end

end

p "hello".hamming_distance("hellohellohellohellohello")