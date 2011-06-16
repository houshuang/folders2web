# Ruby module to use Pashua (http://www.bluem.net/downloads/pashua_en/).
# This module was contributed to the Pashua distribution by Mike Hall,
# with some changes by Carsten Bluem.

require 'tempfile'
require 'iconv'

module Pashua

  def pashua_run(script, encoding = '', path = '')
    pbin = pashua_locate(path) or return nil
   	encoding = "-e " + encoding + " " if encoding != ''
    res = Hash.new
    tmp = Tempfile.open('Pashua')
    tmp.puts script
    tmp.close
    IO.popen("'" + pbin + "' " + encoding + tmp.path).each do |s|
#      ic = Iconv.new('UTF-8//IGNORE', 'UTF-8')
#      s2 = ic.iconv(s + ' ')[0..-2]
      key, val = s.chomp.split('=', 2)
      res[key] = val
    end
    return res
  end

  def pashua_rewrite(s)
    return (s.nil?) ? s : s.gsub(/\[return\]/, "\n") 	# rewrite any 'returns'
  end

  private 
  CWD='.'
  ROOT = '/'
  APPS = '/Applications'
  USER = File::expand_path('~' + APPS)
  def pashua_locate(path = '')
  	locations = [File.dirname($0), $0, CWD, ROOT, APPS, USER]
  	locations = [path] + locations if path != ''
    for d in locations
      p = File::join(d, 'Pashua')
      return p if File::executable?(p)
      p = File::join(d, 'Pashua.app/Contents/MacOS/Pashua')
      return p if File::executable?(p)
    end
    $stderr.puts "Cannot find Pashua binary"
    return nil
  end

end

