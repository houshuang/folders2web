# -*- encoding: utf-8 -*-

Gem::Specification.new do |s|
  s.name = %q{bibtex-ruby}
  s.version = "1.3.12"

  s.required_rubygems_version = Gem::Requirement.new(">= 0") if s.respond_to? :required_rubygems_version=
  s.authors = ["Sylvester Keil"]
  s.date = %q{2011-09-06 00:00:00.000000000Z}
  s.description = %q{A (fairly complete) BibTeX library and parser written in Ruby. Includes a name parser and supports regular BibTeX entries, @comments, string replacement via @string. Allows for easy export/conversion to formats such as YAML, JSON, and XML.}
  s.email = ["http://sylvester.keil.or.at"]
  s.extra_rdoc_files = ["README.md"]
  s.files = ["README.md"]
  s.homepage = %q{http://inukshuk.github.com/bibtex-ruby}
  s.licenses = ["GPL-3"]
  s.rdoc_options = ["--line-numbers", "--inline-source", "--title", "\"BibTeX-Ruby Documentation\"", "--main", "README.md", "--webcvs=http://github.com/inukshuk/bibtex-ruby/tree/master/"]
  s.require_paths = ["lib"]
  s.rubygems_version = %q{1.7.2}
  s.summary = %q{A BibTeX parser and converter written in Ruby.}

  if s.respond_to? :specification_version then
    s.specification_version = 3

    if Gem::Version.new(Gem::VERSION) >= Gem::Version.new('1.2.0') then
      s.add_runtime_dependency(%q<latex-decode>, [">= 0.0.3"])
      s.add_development_dependency(%q<rake>, ["~> 0.9"])
      s.add_development_dependency(%q<racc>, ["~> 1.4"])
      s.add_development_dependency(%q<mini_shoulda>, ["~> 0.3"])
      s.add_development_dependency(%q<mynyml-redgreen>, ["~> 0.7"])
      s.add_development_dependency(%q<autowatchr>, ["~> 0.1"])
      s.add_development_dependency(%q<cucumber>, ["~> 0.10"])
      s.add_development_dependency(%q<json>, ["~> 1.5"])
      s.add_development_dependency(%q<rdoc>, ["~> 3.9"])
    else
      s.add_dependency(%q<latex-decode>, [">= 0.0.3"])
      s.add_dependency(%q<rake>, ["~> 0.9"])
      s.add_dependency(%q<racc>, ["~> 1.4"])
      s.add_dependency(%q<mini_shoulda>, ["~> 0.3"])
      s.add_dependency(%q<mynyml-redgreen>, ["~> 0.7"])
      s.add_dependency(%q<autowatchr>, ["~> 0.1"])
      s.add_dependency(%q<cucumber>, ["~> 0.10"])
      s.add_dependency(%q<json>, ["~> 1.5"])
      s.add_dependency(%q<rdoc>, ["~> 3.9"])
    end
  else
    s.add_dependency(%q<latex-decode>, [">= 0.0.3"])
    s.add_dependency(%q<rake>, ["~> 0.9"])
    s.add_dependency(%q<racc>, ["~> 1.4"])
    s.add_dependency(%q<mini_shoulda>, ["~> 0.3"])
    s.add_dependency(%q<mynyml-redgreen>, ["~> 0.7"])
    s.add_dependency(%q<autowatchr>, ["~> 0.1"])
    s.add_dependency(%q<cucumber>, ["~> 0.10"])
    s.add_dependency(%q<json>, ["~> 1.5"])
    s.add_dependency(%q<rdoc>, ["~> 3.9"])
  end
end
