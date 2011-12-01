CompleteMenuSolution.prototype.theme.DokuWiki = {
  /*
  *  List of available transitions
  *
  *  @type array
  */
  menuOptions : {
    'transitions' : {},
    'modifiers' : ['followlinkcontent'],
    maxOpenDepth : 1,
    toggleMenuOnClick : 1,
    flagOpenClass : 'open',
    stripCssClasses : {
      'root' : ['indexmenu'],
      'ul' : ['indexmenu'],
      'li' : ['open', 'level1','level2','level3','level4']//,
//      'a' : ['wikilink1','wikilink2']
    }
  }
}