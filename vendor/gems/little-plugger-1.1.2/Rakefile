
begin
  require 'bones'
rescue LoadError
  abort '### please install the "bones" gem ###'
end

ensure_in_path 'lib'
require 'little-plugger'

task :default => 'spec:specdoc'
task 'gem:release' => 'spec:run'

Bones {
  name 'little-plugger'
  authors 'Tim Pease'
  email 'tim.pease@gmail.com'
  url 'http://gemcutter.org/gems/little-plugger'
  version LittlePlugger::VERSION
  readme_file 'README.rdoc'
  ignore_file '.gitignore'
  rubyforge.name 'codeforpeople'
  spec.opts << '--color'
  use_gmail

  depend_on 'rspec', :development => true
}

# depending on bones (even as a development dependency) creates a circular
# reference that prevents the auto install of little-plugger when instsalling
# bones
::Bones.config.gem._spec.dependencies.delete_if {|d| d.name == 'bones'}
