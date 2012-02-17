framework 'Cocoa'
framework 'ScriptingBridge'
framework 'WebKit'
require 'rubygems' # disable this for a deployed application
require 'hotcocoa'
require 'hotkeys'
require 'citeproc'
require 'bibtex'


class File
  class << self

    # adds File.write - analogous to File.read, writes text to filename
    def write(filename, text)
      File.open(filename,"w") {|f| f << text}
    end

    # adds File.append - analogous to File.read, writes text to filename
    def append(filename, text)
      File.open(filename,"a") {|f| f << text + "\n"}
    end

    # find the last file added in directory
    def last_added(path)
      path += "*" unless path.index("*")
      Dir[path].select {|f| test ?f, f}.sort_by {|f|  File.mtime f}.pop
    end

    def replace(path, before, after, newpath = "")
      a = File.read(path)
      a.gsub!(before, after)
      newpath = path if newpath == ""
      File.write(newpath, a)
    end
  end
end

def get_frontmost_app
  sysevent = SBApplication.applicationWithBundleIdentifier("com.apple.systemevents")
  return sysevent.processes.select {|p| p.frontmost==true}[0].bundleIdentifier
end

def attach_last
  file_path = NSURL.fileURLWithPath(File.last_added(NSHomeDirectory()+"/Downloads/*.pdf"))
  bib_desk = SBApplication.applicationWithBundleIdentifier("edu.ucsd.cs.mmccrack.bibdesk")
  selected_doc = bib_desk.documents.first.selection.first
  bib_desk.add(file_path, to:selected_doc)
  return selected_doc.citeKey.to_str
end

FULL={:expand => [:width,:height]}
 BASE=<<-END
   <html><head><style type="text/css">
   * { font-family: Monaco; }
   </style>
   </head><body><h1>Researchr MacRuby Demo</h1>Welcome to the MacRuby Researchr demo, hopefully auguring a bright future for Researchr. This demo offers two choices. Click on the button below to display the bibliography of the currently selected publication in BibDesk. This demonstrates the use of ScriptingBridge, bibtex-ruby and citeproc-ruby. <P>You can also switch to the BibDesk window and click Cmd+Opt+Cmd+X to attach the most recent PDF from ~/Downloads. This demonstrates the modified Hotkeys gem.</body></html>
 END
class Restest
  include HotCocoa

  # def start
  #   @hotkeys = HotKeys.new
  #   @hotkeys.addHotString("X+COMMAND+CONTROL+OPTION", "edu.ucsd.cs.mmccrack.bibdesk") do
  #     citekey = attach_last
  #     @label.text="2Added publication to "+citekey
  #   end
  #   @hotkeys.addHotString("Y+COMMAND+CONTROL+OPTION") do
  #     bib_desk = SBApplication.applicationWithBundleIdentifier("edu.ucsd.cs.mmccrack.bibdesk")
  #     selected_doc = bib_desk.documents.first.selection.first
  # 
  #     @label.text="Added publication to "+selected_doc.citeKey.to_str
  #   end
  # 
  #   application name: 'Restest' do |app|
  #     app.delegate = self
  #     # @label = label(text: 'Press Ctrl+Opt+Cmd to attach a file in BibDesk (when BibDesk is in focus)', layout: {start: false})
  #     # window frame: [100, 100, 500, 500], title: 'Attach files' do |win|
  #     #   win << @label
  #     #   win.will_close { exit }
  #     # end
  # 
  #     @view   = WebView.alloc.initWithFrame([0, 0, 520, 520])
  #     @window = NSWindow.alloc.initWithContentRect([200, 200, 520, 520],
  #     styleMask:NSTitledWindowMask|NSClosableWindowMask|NSMiniaturizableWindowMask|NSResizableWindowMask, 
  #     backing:NSBackingStoreBuffered, 
  #     defer:false)
  # 
  #     @window.contentView = view
  #     # Use the screen stylesheet, rather than the print one.
  #     view.mediaStyle = 'screen'
  #     view.customUserAgent = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_2; en-us) AppleWebKit/531.21.8 (KHTML, like Gecko) Version/4.0.4 Safari/531.21.10'
  #     # Make sure we don't save any of the prefs that we change.
  #     view.preferences.autosaves = false
  #     # Set some useful options.
  #     view.preferences.shouldPrintBackgrounds = true
  #     view.preferences.javaScriptCanOpenWindowsAutomatically = false
  #     view.preferences.allowsAnimatedImages = false
  #     # Make sure we don't get a scroll bar.
  #     view.mainFrame.frameView.allowsScrolling = false
  #     view.frameLoadDelegate = self
  #   end
  # end
  def bezel_button(opts)
    button({:layout => {:expand=>[:width],:start => false},
      :bezel=>:recessed}.merge(opts))
  end  
  
    def start
      application :name => "Restest" do |app|
        app.delegate = self
        window :title => "MyBridge",
          :frame => [10, 620, 600, 600] do |win|
          win << @web_view=web_view(:layout => FULL) do |wv|
            wv.mainFrame.loadHTMLString BASE, baseURL: nil
            wv.frameLoadDelegate=self
            wso=wv.windowScriptObject #make visible to JS
            wso.setValue(self, forKey:"TheBridge")
            win << bezel_button(:title=>"Get citation info",
              :on_action=>lambda { |s| getinfo })
            win << bezel_button(:title=>"Quit",
              :on_action=>lambda { |s| exit })
          end
          win.will_close { exit }
        end
      end
    end


  # file/open
  def on_open(menu)
  end

  # file/new
  def on_new(menu)
  end

  # help menu item
  def on_help(menu)
  end

  # This is commented out, so the minimize menu item is disabled
  #def on_minimize(menu)
  #end

  # window/zoom
  def on_zoom(menu)
  end

  # window/bring_all_to_front
  def on_bring_all_to_front(menu)
  end
end

Restest.new.start
