Stars Plugin for DokuWiki
Use for difficulty, rating, etc

@License: GPL 2 (http://www.gnu.org/licenses/gpl.html)
@author: Collin "Keeyai" Green - http://keeyai.com

Original code from the Skill plugin by iDo - http://www.dokuwiki.org/plugin:skill

Modified by Keeyai to include some useful changes mentioned in the comments and other functionality
	- star limit by anon and kenc
	- span instead of div by anon
	- new star image instead of transparency
	- added classes for styling purposes ( span.starspan, img.starimage, img.halfstarimage, img.emptystarimage)
	- show half stars  (number is floored to the nearest half)
	- packaged to work with plugin manager


Usage:  [stars=num] where num is a number, eg:  5, or a ratio, eg: 5/7
				limits the number of stars to 10 -- ratios over ten, eg: 100/500,  will be reduced, eg: 2/10
				
Examples:
	show 2 stars:						[stars=2]
	show 3/10 stars:				[stars=3/10]
	show 4.5/5 stars:				[stars=4.5/5]

Note:
	to use custom images, just replace the star.png, halfstar.png, and emptystar.png files
	 	
TODO:  other image options?  control panel?


Change Log:

V 1.1 - 2008-10-28
	Added bug fixes and changes suggested by Martin Bast on Stars page, 
	including a fix for half stars, changing the pngs to gifs, and
	an attempt at making relative paths that will work no matter how
	deep the calling page is. Also tweaked the alt text for the span itself
	to be more informative.