Subject Index plugin:
====================
Create subject index entries "anywhere" in your wiki pages, that will then be compiled into a Subject Index page, listed A-Z, by headings, then entries/links.  I personally find that this well replaces tagging... and generates a much more useful, readable index at the end, when compared with "tag-clouds".

Adding a subject index entry:
-----------------------------
Syntax: ::[index/heading/subheading/]entry[|display text or segments]:: , e.g. ::books/fiction/writing novels|1,2::, or ::1/book/binding|1::
 *[..] = optional
The first example above would create a new subject index entry as follows:
B => books => fiction => writing novels [page link]
The page link would point to the default index page (first one in config list) as no number was provided

Breakdown of entry elements:
----------------------------
index           : which Subject Index page should the entry be added to (defaults to first one in list)
heading         : the main heading in the subject index, under which entry will be shown, first letter is used for A-Z headings
subheading      : as above
entry           : the actual entry text, a meaning description of what this entry is about
display text    : what should be visible on the page; can be different text, or part of the heading/entry text

By default only a small magnifying glass icon is displayed on the page, but you can also show text next to the entry, or show part of the entry itself:
Use ...|{start}[,{length}] to display parts {start} up to {length} of the entry.
Again using the above example:
- the first two parts of the entry would be displayed: "books >> fiction" (start=1,length=2)
- length is optional, it defaults to 1 segment.
To display the whole entry text on the page use ...|:: or ...|*:: , both will work.

Entries are automatically indexed on each page save, and saved by default in the "../data/index/subject.idx" file, along with other Dokuwiki search indexes.  This location can be changed on the Admin configuration page, but this is not recommended.

Configuration:
==============
Viewing the Subject Index:
--------------------------
Syntax: {{subjectindex>[abstract;border;cols=?;index=?;proper;title]}}
 *[..] = optional
abstract : show abstract of page content as a tool-tip
border   : show borders around table columns
cols=?   : number of columns on the index page (max=4)
index=?  : [0-9] the Subject-Index page on which to list the entry
proper   : use proper-case for wiki page names and entries
title    : use title (1st heading) instead of name for links

Put this markup on a new page, save, and you should see a new Subject Index for your wiki.
- The default subject index page is ":subjectindex" (you can add/change this in configuration).
- Remember that the Subject Index pages are not created for you automatically; you'll need to create pages that correspond to the name you used in the config list.

Don't forget to put ~~NOCACHE~~ somewhere on the page if you want immediate updates!