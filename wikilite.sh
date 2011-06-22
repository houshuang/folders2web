#!/bin/bash
rm -rf /wiki/data/cache/*
rsync --delete -avzPe ssh /Library/WebServer/Documents/wiki/data houshuan@reganmian.net:~/public_html/wiki
rsync --delete -avzPe ssh /Library/WebServer/Documents/wiki/lib/plugins/test/json.tmp houshuan@reganmian.net:~/public_html/wiki/lib/plugins/test/
ssh houshuan@reganmian.net 'chmod -R 755 ~/public_html/wiki/data/*; chmod 755 ~/public_html/wiki/data;touch ~/public_html/wiki/conf/local.php'
