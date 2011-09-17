# -*- encoding: utf-8 -*-

Gem::Specification.new do |s|
  s.name = %q{latex-decode}
  s.version = "0.0.3"

  s.required_rubygems_version = Gem::Requirement.new(">= 0") if s.respond_to? :required_rubygems_version=
  s.authors = ["Sylvester Keil"]
  s.date = %q{2011-07-15}
  s.description = %q{Decodes strings formatted in LaTeX to equivalent Unicode strings.}
  s.email = %q{http://sylvester.keil.or.at}
  s.extra_rdoc_files = ["README.md", "LICENSE"]
  s.files = ["README.md", "LICENSE"]
  s.homepage = %q{http://github.com/inukshuk/latex-decode}
  s.licenses = ["GPL-3"]
  s.rdoc_options = ["--line-numbers", "--inline-source", "--title", "\"LaTeX-Decode Documentation\"", "--main", "README.md", "--webcvs=http://github.com/inukshuk/latex-decode/tree/master/"]
  s.require_paths = ["lib"]
  s.rubygems_version = %q{1.7.2}
  s.summary = %q{Decodes LaTeX to Unicode.}
  s.has_rdoc = true

  if s.respond_to? :specification_version then
    s.specification_version = 3

    if Gem::Version.new(Gem::VERSION) >= Gem::Version.new('1.2.0') then
      s.add_runtime_dependency(%q<unicode>, [">= 0.4"])
      s.add_development_dependency(%q<rake>, [">= 0.8"])
      s.add_development_dependency(%q<bundler>, [">= 1.0"])
      s.add_development_dependency(%q<rdoc>, [">= 3.6"])
      s.add_development_dependency(%q<rspec>, [">= 2.6"])
      s.add_development_dependency(%q<cucumber>, [">= 0.10"])
    else
      s.add_dependency(%q<unicode>, [">= 0.4"])
      s.add_dependency(%q<rake>, [">= 0.8"])
      s.add_dependency(%q<bundler>, [">= 1.0"])
      s.add_dependency(%q<rdoc>, [">= 3.6"])
      s.add_dependency(%q<rspec>, [">= 2.6"])
      s.add_dependency(%q<cucumber>, [">= 0.10"])
    end
  else
    s.add_dependency(%q<unicode>, [">= 0.4"])
    s.add_dependency(%q<rake>, [">= 0.8"])
    s.add_dependency(%q<bundler>, [">= 1.0"])
    s.add_dependency(%q<rdoc>, [">= 3.6"])
    s.add_dependency(%q<rspec>, [">= 2.6"])
    s.add_dependency(%q<cucumber>, [">= 0.10"])
  end
end
