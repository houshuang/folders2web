# -*- coding: utf-8 -*-

module LaTeX
  module Decode
    
    autoload :Accents, 'latex/decode/accents'
    autoload :Diacritics, 'latex/decode/diacritics'
    autoload :Punctuation, 'latex/decode/punctuation'
    autoload :Symbols, 'latex/decode/symbols'
    
    class Decoder
      class << self
        attr_reader :patterns, :map
    
        def inherited (base)
          subclasses << base
        end
        
        def subclasses
          @subclasses ||= []
        end
        
        def decode (string)
          decode!(string.dup)
        end
        
        def decode! (string)
          puts name unless patterns
          patterns.each do |pattern|
            string.gsub!(pattern) { |m| [$2,map[$1],$3].compact.join }
          end
          string
        end
      end
    end
    
    module Base
      
      module_function
      
      def normalize (string)
        string.gsub!(/\\(?:i|j)\b/) { |m| m == '\\i' ? 'ı' : 'ȷ' }
        string.gsub!(/(\\[a-zA-Z]+)\\(\s+)/, '\1{}\2') # \foo\ bar -> \foo{} bar
        string.gsub!(/([^{]\\\w)([;,.:%])/, '\1{}\2')  #} Aaaa\o, -> Aaaa\o{},        
        string
      end
      
      def strip_braces (string)
        string.gsub!(/(^|[^\\])([\{\}]+)/, '\1')
        string.gsub!(/\\(\{|\})/, '\1')
        string
      end
      
    end
    
  end
end