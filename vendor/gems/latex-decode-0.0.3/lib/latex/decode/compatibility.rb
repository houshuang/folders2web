
if RUBY_VERSION < "1.9"
  $KCODE = 'U'
  
  module LaTeX
    def self.to_unicode (string)
      string.gsub(/\\?u([\da-f]{4})/i) { |m| [$1.to_i(16)].pack('U') }
    end
  end
    
  def ruby_18; yield; end
  def ruby_19; false; end
else  

  module LaTeX
    def self.to_unicode (string); string; end
  end

  def ruby_18; false; end
  def ruby_19; yield; end
end