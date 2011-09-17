# -*- encoding: utf-8 -*-

Gem::Specification.new do |s|
  s.name = %q{citeproc-ruby}
  s.version = "0.0.2"

  s.required_rubygems_version = Gem::Requirement.new(">= 1.3.6") if s.respond_to? :required_rubygems_version=
  s.authors = ["Sylvester Keil"]
  s.date = %q{2011-07-26}
  s.description = %q{A CSL (Citation Style Language) Processor}
  s.email = ["http://sylvester.keil.or.at"]
  s.extra_rdoc_files = ["README.md"]
  s.files = ["README.md"]
  s.homepage = %q{http://github.com/inukshuk/citeproc-ruby}
  s.rdoc_options = ["--charset=UTF-8", "--line-numbers", "--inline-source", "--title", "\"CiteProc-Ruby Documentation\"", "--main", "README.md", "--webcvs=http://github.com/inukshuk/citeproc-ruby/tree/master/"]
  s.require_paths = ["lib"]
  s.rubygems_version = %q{1.7.2}
  s.summary = %q{A CSL 1.0 (Citation Style Language) Processor}
  s.has_rdoc = true

  if s.respond_to? :specification_version then
    s.specification_version = 3

    if Gem::Version.new(Gem::VERSION) >= Gem::Version.new('1.2.0') then
      s.add_runtime_dependency(%q<logging>, ["~> 1.5"])
      s.add_runtime_dependency(%q<nokogiri>, ["~> 1.4"])
      s.add_runtime_dependency(%q<unicode_utils>, ["~> 1.0"])
      s.add_development_dependency(%q<bundler>, ["~> 1.0"])
      s.add_development_dependency(%q<rdoc>, ["~> 2.5"])
      s.add_development_dependency(%q<rake>, [">= 0.8.0"])
      s.add_development_dependency(%q<rspec>, ["~> 2.5"])
      s.add_development_dependency(%q<cucumber>, ["~> 0.3"])
    else
      s.add_dependency(%q<logging>, ["~> 1.5"])
      s.add_dependency(%q<nokogiri>, ["~> 1.4"])
      s.add_dependency(%q<unicode_utils>, ["~> 1.0"])
      s.add_dependency(%q<bundler>, ["~> 1.0"])
      s.add_dependency(%q<rdoc>, ["~> 2.5"])
      s.add_dependency(%q<rake>, [">= 0.8.0"])
      s.add_dependency(%q<rspec>, ["~> 2.5"])
      s.add_dependency(%q<cucumber>, ["~> 0.3"])
    end
  else
    s.add_dependency(%q<logging>, ["~> 1.5"])
    s.add_dependency(%q<nokogiri>, ["~> 1.4"])
    s.add_dependency(%q<unicode_utils>, ["~> 1.0"])
    s.add_dependency(%q<bundler>, ["~> 1.0"])
    s.add_dependency(%q<rdoc>, ["~> 2.5"])
    s.add_dependency(%q<rake>, [">= 0.8.0"])
    s.add_dependency(%q<rspec>, ["~> 2.5"])
    s.add_dependency(%q<cucumber>, ["~> 0.3"])
  end
end
