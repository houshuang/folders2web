def header(title)
  return '<html>
  <head>
  
  <script src="http://reganmian.net/alloy/build/aui/aui-min.js" type="text/javascript"></script>

  <link rel="stylesheet" href="http://reganmian.net/alloy/build/aui-skin-classic/css/aui-skin-classic-all-min.css" type="text/css" media="screen" />
  </head>

  <style type="text/css" media="screen">

  </style>

  <body>
  <h1>' + title + '</h1>

  <div id="markupBoundingBox">
  <ul id="markupContentBox">'
end

def footer
  return '</li></ul></div>

  <script type="text/javascript" charset="utf-8">

  AUI().ready("aui-tree-view", function(A) {

    var treeView = new A.TreeView({
      boundingBox: "#markupBoundingBox",
      contentBox: "#markupContentBox"
      })
      .render();

      });

      </script>

      </body>
      </html>'
end