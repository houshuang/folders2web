<?php
/**
 * Changes Plugin: List the most recent changes of the wiki
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_changes extends DokuWiki_Syntax_Plugin {

    /**
     * Return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What type of XHTML do we create?
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 105;
    }
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('\{\{changes>[^}]*\}\}',$mode,'plugin_changes');
    }
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        $match = substr($match,10,-2);

        $data = array(
            'ns' => array(),
            'count' => 10,
            'type' => array(),
            'render' => 'list',
            'render-flags' => array(),
        );

        $match = explode('&',$match);
        foreach($match as $m){
            if(is_numeric($m)){
                $data['count'] = (int) $m;
            }else{
                if(preg_match('/(\w+)\s*=(.+)/', $m, $temp) == 1){
                    $this->handleNamedParameter($temp[1], trim($temp[2]), $data);
                }else{
                    $this->addNamespace($data, trim($m));
                }
            }
        }

        return $data;
    }

    /**
     * Handle parameters that are specified uing <name>=<value> syntax
     */
    function handleNamedParameter($name, $value, &$data) {
        static $types = array('edit' => 'E', 'create' => 'C', 'delete' => 'D', 'minor' => 'e');
        static $renderers = array('list', 'pagelist');
        switch($name){
            case 'count': $data[$name] = intval($value); break;
            case 'ns':
                foreach(preg_split('/\s*,\s*/', $value) as $value){
                    $this->addNamespace($data, $value);
                }
                break;
            case 'type':
                foreach(preg_split('/\s*,\s*/', $value) as $value){
                    if(array_key_exists($value, $types)){
                        $data[$name][] = $types[$value];
                    }
                }
                break;
            case 'render':
                // parse "name(flag1, flag2)" syntax
                if(preg_match('/(\w+)(?:\((.*)\))?/', $value, $match) == 1){
                    if(in_array($match[1], $renderers)){
                        $data[$name] = $match[1];
                        $flags = trim($match[2]);
                        if($flags != ''){
                            $data['render-flags'] = preg_split('/\s*,\s*/', $flags);
                        }
                    }
                }
                break;
           case 'user':
               foreach(preg_split('/\s*,\s*/', $value) as $value){
                   $data[$name][] = $value;
               }
               break;
        }
    }

    /**
     * Clean-up the namespace name and add it (if valid) into the $data array
     */
    function addNamespace(&$data, $namespace) {
        $action = ($namespace{0} == '-')?'exclude':'include';
        $namespace = cleanID(preg_replace('/^[+-]/', '', $namespace));
        if(!empty($namespace)){
            $data['ns'][$action][] = $namespace;
        }
    }

    /**
     * Create output
     */
    function render($mode, &$R, $data) {
        if($mode == 'xhtml'){
            $changes = $this->getChanges($data['count'], $data['ns'], $data['type'], $data['user']);
            if(!count($changes)) return true;

            switch($data['render']){
                case 'list': $this->renderSimpleList($changes, $R, $data['render-flags']); break;
                case 'pagelist': $this->renderPageList($changes, $R, $data['render-flags']); break;
            }
            return true;
        }elseif($mode == 'metadata'){
            global $conf;
            $R->meta['relation']['depends']['rendering'][$conf['changelog']] = true;
            return true;
        }
        return false;
    }

    /**
     * Based on getRecents() from inc/changelog.php
     */
    function getChanges($num, $ns, $type, $user) {
        global $conf;
        $changes = array();
        $seen = array();
        $count = 0;
        $lines = @file($conf['changelog']);

        for($i = count($lines)-1; $i >= 0; $i--){
            $change = $this->handleChangelogLine($lines[$i], $ns, $type, $user, $seen);
			$expl = explode(":", $change['id']);
			$firstpart = $expl[0];
			$dontwant = array("clip","kindle","skimg","notes");
			
            if($change !== false && page_exists($change['id']) && !in_array($firstpart, $dontwant) ){
                $changes[] = $change;
                // break when we have enough entries
                if(++$count >= $num) break;
            }
        }
        return $changes;
    }

    /**
     * Based on _handleRecent() from inc/changelog.php
     */
    function handleChangelogLine($line, $ns, $type, $user, &$seen) {
        // split the line into parts
        $change = parseChangelogLine($line);
        if($change===false) return false;

        // skip seen ones
        if(isset($seen[$change['id']])) return false;

        // filter type
        if(!empty($type) && !in_array($change['type'], $type)) return false;

        // filter user
        if(!empty($user) && (empty($change['user']) ||
                            !in_array($change['user'], $user))) return false;


        // remember in seen to skip additional sights
        $seen[$change['id']] = 1;

        // check if it's a hidden page
        if(isHiddenPage($change['id'])) return false;

        // filter included namespaces
        if(isset($ns['include'])){
            if(!$this->isInNamespace($ns['include'], $change['id'])) return false;
        }

        // filter excluded namespaces
        if(isset($ns['exclude'])){
            if($this->isInNamespace($ns['exclude'], $change['id'])) return false;
        }

        // check ACL
        $change['perms'] = auth_quickaclcheck($change['id']);
        if ($change['perms'] < AUTH_READ) return false;

        return $change;
    }

    /**
     * Check if page belongs to one of namespaces in the list
     */
    function isInNamespace($namespaces, $id) {
        foreach($namespaces as $ns){
            if((strpos($id, $ns . ':') === 0)) return true;
        }
        return false;
    }

    /**
     *
     */
    function renderPageList($changes, &$R, $flags) {
        $pagelist = @plugin_load('helper', 'pagelist');
        if($pagelist){
            $pagelist->setFlags($flags);
            $pagelist->startList();
            foreach($changes as $change){
                $page['id'] = $change['id'];
                $page['date'] = $change['date'];
                $page['user'] = $this->getUserName($change);
                $pagelist->addPage($page);
            }
            $R->doc .= $pagelist->finishList();
        }else{
            // Fallback to the simple list renderer
            $this->renderSimpleList($changes, $R);
        }
    }

    /**
     * Render the day header
     */
    function dayheader(&$R,$date){
        if($R->getFormat() == 'xhtml'){
            $R->doc .= '<h3 class="changes">';
            $R->cdata(dformat($date,$this->getConf('dayheaderfmt')));
            $R->doc .= '</h3>';
        }else{
            $R->header(dformat($date,$this->getConf('dayheaderfmt')),3,0);
        }
    }

    /**
     *
     */
    function renderSimpleList($changes, &$R, $flags = null) {
        global $conf;
        $flags = $this->parseSimpleListFlags($flags);

        if($flags['dayheaders']){
            $date = date('Ymd',$changes[0]['date']);
            $this->dayheader($R,$changes[0]['date']);
        }

        $R->listu_open();
        foreach($changes as $change){
            if($flags['dayheaders']){
                $tdate = date('Ymd',$change['date']);
                if($tdate != $date){
                    $R->listu_close(); // break list to insert new header
                    $this->dayheader($R,$change['date']);
                    $R->listu_open();
                    $date = $tdate;
                }
            }

            $R->listitem_open(1);
            $R->listcontent_open();
            $R->internallink(':'.$change['id'],null,null,false,'navigation');
            // if($flags['summary']){
            //     $R->cdata(' '.$change['sum']);
            // }
            if($flags['signature']){
                $user = $this->getUserName($change);
                $date = strftime($conf['dformat'], $change['date']);
                $R->cdata(' ');
                $R->entity('---');
                $R->cdata(' ');
                $R->emphasis_open();
                $R->cdata($user.' '.$date);
                $R->emphasis_close();
            }
            $R->listcontent_close();
            $R->listitem_close();
        }
        $R->listu_close();
    }

    /**
     *
     */
    function parseSimpleListFlags($flags) {
        $outFlags = array('summary' => true, 'signature' => false, 'dayheaders' => false);
        if(!empty($flags)){
            foreach($flags as $flag){
                if(array_key_exists($flag, $outFlags)){
                    $outFlags[$flag] = true;
                }elseif(substr($flag, 0, 2) == 'no'){
                    $flag = substr($flag, 2);
                    if(array_key_exists($flag, $outFlags)){
                        $outFlags[$flag] = false;
                    }
                }
            }
        }
        return $outFlags;
    }

    /**
     *
     */
    function getUserName($change) {
        global $auth;
        if (!empty($change['user'])){
            $user = $auth->getUserData($change['user']);
            return $user['name'];
        }else{
            return $change['ip'];
        }
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
