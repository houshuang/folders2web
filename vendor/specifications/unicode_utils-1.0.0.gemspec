# -*- encoding: utf-8 -*-

Gem::Specification.new do |s|
  s.name = %q{unicode_utils}
  s.version = "1.0.0"

  s.required_rubygems_version = Gem::Requirement.new(">= 0") if s.respond_to? :required_rubygems_version=
  s.authors = ["Stefan Lang"]
  s.date = %q{2009-01-30}
  s.email = %q{langstefan@gmx.at}
  s.extra_rdoc_files = ["README.txt", "INSTALL.txt"]
  s.files = ["README.txt", "INSTALL.txt"]
  s.homepage = %q{http://github.com/lang/unicode_utils}
  s.rdoc_options = ["--main=README.txt", "--charset=UTF-8"]
  s.require_paths = ["lib"]
  s.required_ruby_version = Gem::Requirement.new(">= 1.9.1")
  s.rubyforge_project = %q{unicode-utils}
  s.rubygems_version = %q{1.7.2}
  s.summary = %q{additional Unicode aware functions for Ruby 1.9}
  s.has_rdoc = true

  if s.respond_to? :specification_version then
    s.specification_version = 2

    if Gem::Version.new(Gem::VERSION) >= Gem::Version.new('1.2.0') then
    else
    end
  else
  end
end
