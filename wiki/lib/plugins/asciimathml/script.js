/*
 This script installs the ASCIIMathML JavaScript
 to be used through "asciimath" plugin in DokuWiki
  Mohammad Rahmani
  Rev. 0: Trial
  Date: Friday, 25 Jul. 2008  10:14:40

  Rev. 0.20: Some bugs fixed
  Date: Thursday, June 16, 2011

  Rev. 0.21: Some bugs fixed
  Date: Thursday, June 23, 2011
   - all function in the previos script.js was deleted!
   - support for the latest version of Dokuwiki (2011-5-25a)



    * This plugin uses ASCIIMathML.js version 1.4.8 Aug 30, 2007, (c) Peter Jipsen http://www.chapman.edu/~jipsen
    * Latest version at http://www.chapman.edu/~jipsen/mathml/ASCIIMathML.js
    * For changes see http://www.chapman.edu/~jipsen/mathml/asciimathchanges.txt
    * If you use it on a webpage, please send the URL to jipsen@chapman.edu
    * Note: This plugin ONLY SUPPORTS version 1.4.8 of ASCIIMathML.js


*/
// full address to ASCIIMathML installation


document.write('<script type="text/javascript" src="' + DOKU_BASE + 'lib/plugins/asciimathml/ASCIIMathML148.js' + '"></script>');
