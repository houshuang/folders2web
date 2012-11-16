$:.push(File.dirname($0))
require 'utility-functions'
require 'listen'
require 'appscript'

dir = "/Users/Stian/Library/Application Support/iLifeAssetManagement/assets/sub/"

growlapp=Appscript.app('Growl')
growlapp.register({:as_application=>'Researchr', :all_notifications=>['Note'], :default_notifications=>['Note']})

Listen.to(dir, :filter => /\.JPG$/) do |modified, added, removed|
  if added
    added.each do |path|
      # we need to send TIFF data to Growl, so convert file to tiff, read in, and create data object for appscript
      `sips -s format tiff '#{path}' --out '#{path + ".tiff"}'`
      data = File.read(path + ".tiff")
      raw = AE::AEDesc.new(KAE::TypeTIFF, data)

      growlapp.notify({:with_name=>'Note',:title=>'New photo added',:description=>'from iPhone',:application_name=>'Researchr', :image => raw, :callback_URL=>"ruby://preview_iphone_image"})
    end
  end
end