# collects screenshots from Skim for later inclusion during skim notes extraction

a = File.open("/tmp/skim-screenshots-tmp","a")
a << Dir["/Volumes/Home/stian/Desktop/Screen shot*"].select {|f| test ?f, f}.sort_by {|f|  File.mtime f}.pop << "\n"
`/usr/local/bin/growlnotify -m "One picture added to wiki notes cache"`