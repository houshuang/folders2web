<?php

/**
 * Plugin RefNotes: Namespace heplers
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

/**
 * Returns canonic name for a namespace
 */
function refnotes_canonizeNamespace($name) {
    return preg_replace('/:{2,}/', ':', ':' . $name . ':');
}

/**
 * Returns name of the parent namespace
 */
function refnotes_getParentNamespace($name) {
    return preg_replace('/\w*:$/', '', $name);
}

/**
 * Splits full note name into namespace and name components
 */
function refnotes_parseName($name) {
    $pos = strrpos($name, ':');
    if ($pos !== false) {
        $namespace = refnotes_canonizeNamespace(substr($name, 0, $pos));
        $name = substr($name, $pos + 1);
    }
    else {
        $namespace = ':';
    }

    return array($namespace, $name);
}
