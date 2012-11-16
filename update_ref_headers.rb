$:.push(File.dirname($0))
require 'utility-functions'
require 'wiki-lib'

# script which goes through all the ref pages in wiki, and updates headers
attempt = 0
success = 0
Dir.foreach(Wiki_path + "/data/pages/ref") do |f|
  next if f == '.' or f == '..'
  next unless f.size < 4 || f[-4..-1].downcase == '.txt'
  ref = f[0..-5]
  attempt += 1
  try { ensure_refpage(ref)
  success += 1
  }
end

puts "#{success} ref pages updated, out of #{attempt} attempted."