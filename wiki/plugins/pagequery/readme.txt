Insert the pagequery markup wherever you want your list to appear.
--------
E.g.{{pagequery>[query];fulltext;sort=key:direction,key2:direction;group;limit=??;cols=?;inwords;proper}}
--------
Parameters as follows:
1. query: any expression directly after the >; can use all Dokuwiki search options (see manual)
Limit to namespace: @namespace.  Page name searches accept regex
2. fulltext: use a full-text search, instead of page_id only [default]
3. sort: keys to sort by, in order of sorting. Each key can be followed by prefered sorting order
E.g. sort=name:asc -or- sort=a,name [default direction = asc]
Available keys:
* a, ab, abc          by 1st letter, 2 letters, or 3 letters
* name                by page name (no namespace) [not grouped]
* page|id             by full page id, including namespace [not grouped]
* ns                  by namespace (without page name)
* mdate, cdate        by modified|created dates (full) [not grouped]
* m[year][month][day] by modified [year][month][day]; any combination accepted
* c[year][month][day] by created [year][month][day]; any combination accepted
* creator             by page author
Sort Directions:
* asc => ascending (A-Z); desc => descending (Z-A)
Note: date sort default to descending, string sorts to ascending
4. group: show group headers for each change in sort keys. Note: keys with no duplicated cannot be grouped (i.e. name, page|id, mdate, cdate)
5. limit: maximum number of results to return
6. inwords: use real month and day names instead of just numbers
7. cols: number of columns in displayed list (max = 4) e.g cols=3
8. proper: display page names and namespace in Proper Case; =header =name =both
9. nostart: ignore any start pages in the given namespace
10. fullregex: search full page id using regular expressions (power-user option)
11. title: use page 'title' rather than 'name' in links
12. abstract: show preview of page coontent; =tootip (as popup); =<limit> (as snippet below link, up to <limit> links)

----
All options are optional, and the list will default to a boring long 1-column list...