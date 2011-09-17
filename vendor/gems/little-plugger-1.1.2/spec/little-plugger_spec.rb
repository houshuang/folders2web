
require File.join(File.dirname(__FILE__), %w[spec_helper])

describe LittlePlugger do

  it "converts a string from camel-case to underscore" do
    LittlePlugger.underscore('FooBarBaz').should == 'foo_bar_baz'
    LittlePlugger.underscore('CouchDB').should == 'couch_db'
    LittlePlugger.underscore('FOOBar').should == 'foo_bar'
    LittlePlugger.underscore('Foo::Bar::BazBuz').should == 'foo/bar/baz_buz'
  end

  it "generates a default plugin path" do
    LittlePlugger.default_plugin_path(LittlePlugger).should == 'little_plugger/plugins'
    LittlePlugger.default_plugin_path(Spec::Runner).should == 'spec/runner/plugins'
  end

  it "generates a default plugin module" do
    LittlePlugger.default_plugin_module('little_plugger').should == LittlePlugger
    lambda {LittlePlugger.default_plugin_module('little_plugger/plugins')}.
        should raise_error(NameError, 'uninitialized constant LittlePlugger::Plugins')
    LittlePlugger.default_plugin_module('spec/runner').should == Spec::Runner
  end
end

# EOF
