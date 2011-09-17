# -*- encoding: utf-8 -*-

Gem::Specification.new do |s|
  s.name = %q{unicode}
  s.version = "0.4.0"

  s.required_rubygems_version = Gem::Requirement.new(">= 0") if s.respond_to? :required_rubygems_version=
  s.authors = ["Yoshida Masato"]
  s.date = %q{2010-10-13}
  s.email = %q{yoshidam@yoshidam.net}
  s.extensions = ["extconf.rb"]
  s.extra_rdoc_files = ["README"]
  s.files = ["README", "extconf.rb"]
  s.homepage = %q{http://www.yoshidam.net/Ruby.html#unicode}
  s.require_paths = ["."]
  s.rubygems_version = %q{1.7.2}
  s.summary = %q{Unicode normalization library.}
  s.has_rdoc = true

  if s.respond_to? :specification_version then
    s.specification_version = 3

    if Gem::Version.new(Gem::VERSION) >= Gem::Version.new('1.2.0') then
    else
    end
  else
  end
end
