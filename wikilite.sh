#!/bin/bash

rsync --delete -avzPe ssh /Library/WebServer/Documents/wiki/data houshuan@reganmian.net:~/public_html/wiki
ssh houshuan@reganmian.net 'chmod -R 755 ~/public_html/wiki/data/*; chmod 755 ~/public_html/wiki/data;touch ~/public_html/wiki/conf/local.php'
