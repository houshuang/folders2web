# Copy to settings.rb, and modify to suit your environment
# Settings.rb should also be generated automatically by install.rb (might be out of date)

# title of your wiki
Wiki_title = "Stian's PhD wiki"

# description of your wiki
Wiki_desc = "Raw research notes and article annotations about online collaborative learning"

# where files that you download from Chrome (PDFs) end up
Downloads_path = "/Users/YOURUSERNAME/Downloads"

# where the dokuwiki files are stored
Wiki_path = "/Library/WebServer/Documents/wiki"

# where your main BibDesk database (Bibliography.bib) is stored
Bibliography = "/Users/USERNAME/Dropbox/Public/Bibliography.bib"

# the path to your localhost wiki (I don't recommend changing this)
Internet_path = "http://localhost/wiki"

# the path to your wiki on the public internet (some things might break if the URL isn't /wiki)
Server_path = "http://MYDOMAIN.net/wiki"

# let's researchr know if it's accessing a page in your wiki, or somebody else's wiki
My_domains = ['localhost', 'MYDOMAIN.net']

# where BibDesk stores its PDFs
PDF_path = "/Users/YOURUSERNAME/Bibdesk"

# where the json cache is stored, I don't recommend changing this
JSON_path = "/wiki/lib/plugins/dokuresearchr/json.tmp"

# leave empty unless you are using the Scrobblr service (under development)
Scrobble_token = ""
Scrobble_server_host = "stormy-leaf-9036.herokuapp.com"
Scrobble_server_port = "80"

# make sure RSS generation works before turning on
Make_RSS = false