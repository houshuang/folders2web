<?php
/**
 * DokuWiki Plugin sqlite (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'admin.php');

class admin_plugin_sqlite extends DokuWiki_Admin_Plugin {

    function getInfo() {
        return confToHash(dirname(__FILE__).'plugin.info.txt');
    }

    function getMenuSort() { return 500; }
    function forAdminOnly() { return true; }

    function handle() {
    }

    function html() {
        global $ID;

        echo $this->locale_xhtml('intro');

        if($_REQUEST['db'] && checkSecurityToken()){

            echo '<h2>'.$this->getLang('db').' '.hsc($_REQUEST['db']).'</h2>';
            echo '<div class="level2">';

            echo '<ul>';
            echo '<li><div class="li"><a href="'.
                    wl($ID,array('do'     => 'admin',
                                 'page'   => 'sqlite',
                                 'db'     => $_REQUEST['db'],
                                 'sql'    => 'SELECT name,sql FROM sqlite_master WHERE type=\'table\' ORDER BY name',
                                 'sectok' => getSecurityToken())).
                 '">'.$this->getLang('table').'</a></div></li>';
            echo '<li><div class="li"><a href="'.
                    wl($ID,array('do'     => 'admin',
                                 'page'   => 'sqlite',
                                 'db'     => $_REQUEST['db'],
                                 'sql'    => 'SELECT name,sql FROM sqlite_master WHERE type=\'index\' ORDER BY name',
                                 'sectok' => getSecurityToken())).
                 '">'.$this->getLang('index').'</a></div></li>';
            echo '</ul>';

            $form = new Doku_Form(array());
            $form->startFieldset('SQL Command');
            $form->addHidden('id',$ID);
            $form->addHidden('do','admin');
            $form->addHidden('page','sqlite');
            $form->addHidden('db',$_REQUEST['db']);
            $form->addElement('<textarea name="sql" class="edit">'.hsc($_REQUEST['sql']).'</textarea>');
            $form->addElement('<input type="submit" class="button" />');
            $form->endFieldset();
            $form->printForm();


            if($_REQUEST['sql']){

                $DBI =& plugin_load('helper', 'sqlite');
                if(!$DBI->init($_REQUEST['db'],'')) return;

                $sql = explode(";",$_REQUEST['sql']);
                foreach($sql as $s){
                    $s = preg_replace('!^\s*--.*$!m', '', $s);
                    $s = trim($s);
                    if(!$s) continue;
                    $res = $DBI->query("$s;");
                    if ($res === false) continue;

                    msg(sqlite_num_rows($res).' affected rows',1);
                    $result = $DBI->res2arr($res);
                    if(!count($result)) continue;

                    echo '<p>';
                    $ths = array_keys($result[0]);
                    echo '<table class="inline">';
                    echo '<tr>';
                    foreach($ths as $th){
                        echo '<th>'.hsc($th).'</th>';
                    }
                    echo '</tr>';
                    foreach($result as $row){
                        echo '<tr>';
                        $tds = array_values($row);
                        foreach($tds as $td){
                            echo '<td>'.hsc($td).'</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</table>';
                    echo '</p>';
                }

            }

            echo '</div>';
        }
    }

    function getTOC(){
        global $conf;
        global $ID;

        $toc = array();
        $dbfiles = glob($conf['metadir'].'/*.sqlite');


        if(is_array($dbfiles)) foreach($dbfiles as $file){
            $db = basename($file,'.sqlite');
            $toc[] = array(
                        'link'  => wl($ID,array('do'=>'admin','page'=>'sqlite','db'=>$db,'sectok'=>getSecurityToken())),
                        'title' => $this->getLang('db').' '.$db,
                        'level' => 1,
                        'type'  => 'ul',
                     );
        }

        return $toc;
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
