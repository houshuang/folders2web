#!/bin/bash
rm -rf /wiki/data/cache/*
rsync --exclude .htaccess --delete -avzPe ssh /Library/WebServer/Documents/wiki houshuan@reganmian.net:~/public_html
ssh houshuan@reganmian.net 'chmod -R 755 ~/public_html/wiki/*; chmod 755 ~/public_html/wiki;touch ~/public_html/wiki/conf/dokuwiki.php'
gunzip /wiki/sitemap.xml.gz
ruby -pe 'gsub("localhost","reganmian.net")' < /wiki/sitemap.xml > /wiki/sitemap-tmp.xml
mv /wiki/sitemap-tmp.xml /wiki/sitemap.xml
gzip /wiki/sitemap.xml
rsync --delete -avzPe ssh /wiki/sitemap.xml.gz houshuan@reganmian.net:~/public_html/
