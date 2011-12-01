/**************************************************
 *
 *  Extensions for the RegExp object
 *
 *  @author Ilya Lebedev <ilya@lebedev.net>
 *  @modified $Date: 2006-11-12 15:20:46 +0300 (Вск, 12 Ноя 2006) $
 *  @version $Rev: 101 $
 *  @license LGPL 2.1 or later
 **************************************************/
RegExp.escape=function(i){if(!arguments.callee.sRE){var I=['/','.','*','+','?','|','(',')','[',']','{','}','$','^','\\'];arguments.callee.sRE=new RegExp('(\\'+I.join('|\\')+')','g');}return isString(i)?i.replace(arguments.callee.sRE,'\\$1'):(isArray(i)?i.map(RegExp.escape).join("|"):"");};
