<?php

/**
 * Plugin RefNotes: Configuration
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

class refnotes_configuration {

    private static $section = array();
    private static $setting = array(
        'replace-footnotes' => array('general', false),
        'reference-db-enable' => array('general', false),
        'reference-db-namespace' => array('general', ':refnotes:')
    );

    /**
     *
     */
    public static function getSetting($name) {
        $result = null;

        if (array_key_exists($name, self::$setting)) {
            $sectionName = self::$setting[$name][0];
            $result = self::$setting[$name][1];

            if (!array_key_exists($sectionName, self::$section)) {
                self::$section[$sectionName] = self::load($sectionName);
            }

            if (array_key_exists($name, self::$section[$sectionName])) {
                $result = self::$section[$sectionName][$name];
            }
        }

        return $result;
    }

    /**
     *
     */
    public static function load($sectionName) {
        $pluginRoot = DOKU_PLUGIN . 'refnotes/';
        $fileName = $pluginRoot . $sectionName . '.local.dat';

        if (!file_exists($fileName)) {
            $fileName = $pluginRoot . $sectionName . '.dat';
            if (!file_exists($fileName)) {
                $fileName = '';
            }
        }

        if ($fileName != '') {
            $result = unserialize(io_readFile($fileName, false));
        }
        else {
            $result = array();
        }

        return $result;
    }

    /**
     *
     */
    public static function save($sectionName, $config) {
        $pluginRoot = DOKU_PLUGIN . 'refnotes/';
        $fileName = $pluginRoot . $sectionName . '.local.dat';

        return io_saveFile($fileName, serialize($config));
    }
}
