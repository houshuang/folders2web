framework 'Cocoa'
framework 'ScriptingBridge'
require 'rubygems' # disable this for a deployed application
require 'hotcocoa'
require 'hotkeys'



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


class Restest
  include HotCocoa

  def start
    @hotkeys = HotKeys.new
    @hotkeys.addHotString("X+COMMAND+CONTROL+OPTION", "edu.ucsd.cs.mmccrack.bibdesk") do
    citekey = attach_last
    @label.text="2Added publication to "+citekey
    end
    @hotkeys.addHotString("Y+COMMAND+CONTROL+OPTION") do
      bib_desk = SBApplication.applicationWithBundleIdentifier("edu.ucsd.cs.mmccrack.bibdesk")
      selected_doc = bib_desk.documents.first.selection.first

      @label.text="Added publication to "+selected_doc.citeKey.to_str
    end

    application name: 'Restest' do |app|
      app.delegate = self
      @label = label(text: 'Press Ctrl+Opt+Cmd to attach a file in BibDesk (when BibDesk is in focus)', layout: {start: false})
      window frame: [100, 100, 500, 500], title: 'Attach files' do |win|
        win << @label
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
