    # Borrowed from the MacRuby sources on April 18, 2011
    framework 'Cocoa'

    # Loading all the Ruby project files.
    main = File.basename(__FILE__, File.extname(__FILE__))
    dir_path = NSBundle.mainBundle.resourcePath.fileSystemRepresentation
    dir_path += "/lib/"
    Dir.glob(File.join(dir_path, '*.{rb,rbo}')).map { |x| File.basename(x, File.extname(x)) }.uniq.each do |path|
    if path != main
      require File.join(dir_path, path)
    end
    end

    # Starting the Cocoa main loop.
    NSApplicationMain(0, nil)
