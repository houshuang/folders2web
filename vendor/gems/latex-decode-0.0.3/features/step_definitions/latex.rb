When /^I decode the string ('|")(.*)\1$/ do |quote,string|
  @result = LaTeX.decode(string)
end

Then /^the result should be ('|")(.*)\1$/ do |quote,value|
  @result.should == value
end