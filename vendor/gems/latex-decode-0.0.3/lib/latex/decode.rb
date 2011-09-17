#--
# LaTeX::Decode
# Copyright (C) 2011 Sylvester Keil <sylvester.keil.or.at>
# Copyright (C) 2010 François Charette
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#++

require 'unicode'

require 'latex/decode/version'
require 'latex/decode/compatibility'
require 'latex/decode/base'

module LaTeX
  
  class << self
    def decode (string)
      return string unless string.is_a? String

      string = string.dup
      
      Decode::Base.normalize(string)
      
      Decode::Accents.decode!(string)
      Decode::Diacritics.decode!(string)
      Decode::Punctuation.decode!(string)
      Decode::Symbols.decode!(string)
      
      Decode::Base.strip_braces(string)
      
      Unicode::normalize_C(string)
    end
  end
end