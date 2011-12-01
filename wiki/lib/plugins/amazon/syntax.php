<?php
/**
 * Amazon Plugin: pulls Bookinfo from amazon.com
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/HTTPClient.php');


if(!defined('AMAZON_APIKEY')) define('AMAZON_APIKEY','0R9FK149P6SYHXZZDZ82');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_amazon extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 160;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{amazon>[\w:\\- =]+\}\}',$mode,'plugin_amazon');
        $this->Lexer->addSpecialPattern('\{\{wishlist>[\w:\\- =]+\}\}',$mode,'plugin_amazon');
        $this->Lexer->addSpecialPattern('\{\{amazonlist>[\w:\\- =]+\}\}',$mode,'plugin_amazon');
    }

    /**
     * Do all the API work, fetch the data, parse it and return it for the renderer
     */
    function handle($match, $state, $pos, &$handler){
        // check type and remove markup
        if(substr($match,2,8) == 'wishlist'){
            $match = substr($match,11,-2);
            $type = 'wishlist';
        }elseif(substr($match,2,10) == 'amazonlist'){
            $match = substr($match,13,-2);
            $type = 'amazonlist';
        }else{
            $match = substr($match,9,-2);
            $type = 'product';
        }
        list($ctry,$asin) = explode(':',$match,2);

        // default parameters...
        $params = array(
            'type'      => $type,
            'imgw'      => $this->getConf('imgw'),
            'imgh'      => $this->getConf('imgh'),
            'maxlen'    => $this->getConf('maxlen'),
            'price'     => $this->getConf('showprice'),
            'purchased' => $this->getConf('showpurchased'),
            'sort'      => $this->getConf('sort'),
        );
        // ...can be overridden
        list($asin,$more) = explode(' ',$asin,2);
        if(preg_match('/(\d+)x(\d+)/i',$more,$match)){
            $params['imgw'] = $match[1];
            $params['imgh'] = $match[2];
        }
        if(preg_match('/=(\d+)/',$more,$match)){
            $params['maxlen'] = $match[1];
        }
        if(preg_match('/noprice/i',$more,$match)){
            $params['price'] = false;
        }elseif(preg_match('/(show)?price/i',$more,$match)){
            $params['price'] = true;
        }
        if(preg_match('/nopurchased/i',$more,$match)){
            $params['purchased'] = false;
        }elseif(preg_match('/(show)?purchased/i',$more,$match)){
            $params['purchased'] = true;
        }
        if(preg_match('/sortprice/i',$more,$match)){
            $params['sort'] = 'Price';
        }elseif(preg_match('/sortpriority/i',$more,$match)){
            $params['sort'] = 'Priority';
        }elseif(preg_match('/sortadded/i',$more,$match)){
            $params['sort'] = 'DateAdded';
        }

        // no country given?
        if(empty($asin)){
            $asin = $ctry;
            $ctry = 'us';
        }

        // correct country given?
        if(!preg_match('/^(us|uk|jp|de|fr|ca)$/',$ctry)){
            $ctry = 'us';
        }

        // get partner id
        $partner = $this->getConf('partner_'.$ctry);

        // correct domains
        if($ctry == 'us') $ctry = 'com';
        if($ctry == 'uk') $ctry = 'co.uk';

        // basic API parameters
        $opts = array();
        $opts['Service']        = 'AWSECommerceService';
        $opts['AWSAccessKeyId'] = AMAZON_APIKEY;
        $opts['AssociateTag']   = $partner;
        if($type == 'product'){
            // parameters for querying a single product
            $opts['Operation']      = 'ItemLookup';
            $opts['ResponseGroup']  = 'Medium,OfferSummary';
            if(strlen($asin)<13){
                $opts['IdType'] = 'ASIN';
                $opts['ItemId'] = $asin;
            }else{
                $opts['SearchIndex'] = 'Books';
                $opts['IdType']      = 'ISBN';
                $opts['ItemId']      = $asin;
            }
        }else{
            // parameters to query a wishlist
            $opts['Operation']            = 'ListLookup';
            $opts['ResponseGroup']        = 'ListItems,Medium,OfferSummary';
            $opts['ListId']               = $asin;
            $opts['Sort']                 = $params['sort'];
            $opts['IsIncludeUniversal']   = 'True';
            $opts['IsOmitPurchasedItems'] = ($params['purchased'] ? 'False' : 'True');
            if($type == 'wishlist'){
                $opts['ListType']   = 'WishList';
            }else{
                $opts['ListType']   = 'Listmania';
            }
        }

        // support paged results
        $result = array();
        $pages = 1;
        for($page=1; $page <= $pages; $page++){
            $opts['ProductPage'] = $page;

            // fetch it
            $http = new DokuHTTPClient();
            $url = $this->_signedRequestURI($ctry,$opts,$this->getConf('publickey'),$this->getConf('privatekey'));
            $xml  = $http->get($url);
            if(empty($xml)){
                if($http->error) return $http->error;
                if($http->status == 403) return 'Signature check failed, did you set your Access Keys in config?';
                return 'unkown error';
            }

            // parse it
            require_once(dirname(__FILE__).'/XMLParser.php');
            $xmlp = new XMLParser($xml);
            $data = $xmlp->getTree();

            //dbg($data);

            // check for errors and return the item(s)
            if($type == 'product'){
                // error?
                if($data['ITEMLOOKUPRESPONSE'][0]['ITEMS'][0]['REQUEST'][0]['ERRORS']){
                    return $data['ITEMLOOKUPRESPONSE'][0]['ITEMS'][0]['REQUEST'][0]
                                ['ERRORS'][0]['ERROR'][0]['MESSAGE'][0]['VALUE'];
                }
                // return item
                $result = array_merge($result, (array)
                              $data['ITEMLOOKUPRESPONSE'][0]['ITEMS'][0]['ITEM']);
            }else{
                // error?
                if($data['LISTLOOKUPRESPONSE'][0]['LISTS'][0]['REQUEST'][0]['ERRORS']){
                    return $data['LISTLOOKUPRESPONSE'][0]['LISTS'][0]['REQUEST'][0]
                                ['ERRORS'][0]['ERROR'][0]['MESSAGE'][0]['VALUE'];
                }
                // multiple pages?
                $pages = (int) $data['LISTLOOKUPRESPONSE'][0]['LISTS'][0]['LIST'][0]
                                        ['TOTALPAGES'][0]['VALUE'];

                // return items
                $result = array_merge($result, (array)
                              $data['LISTLOOKUPRESPONSE'][0]['LISTS'][0]['LIST'][0]['LISTITEM']);
            }
        }

        return array($result,$params);
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;
        if(is_array($data)){
            foreach($data[0] as $item){
                $renderer->doc .= $this->_format($item,$data[1]);
            }
        }else{
            $renderer->doc .= '<p>failed to fetch data: <code>'.hsc($data).'</code></p>';
        }
        return true;
    }

    /**
     * Create a signed Request URI
     *
     * Original copyright notice:
     *
     *   Copyright (c) 2009 Ulrich Mierendorff
     *
     *   Permission is hereby granted, free of charge, to any person obtaining a
     *   copy of this software and associated documentation files (the "Software"),
     *   to deal in the Software without restriction, including without limitation
     *   the rights to use, copy, modify, merge, publish, distribute, sublicense,
     *   and/or sell copies of the Software, and to permit persons to whom the
     *   Software is furnished to do so, subject to the following conditions:
     *
     *   The above copyright notice and this permission notice shall be included in
     *   all copies or substantial portions of the Software.
     *
     * @author Ulrich Mierendorff <ulrich.mierendorff@gmx.net>
     * @link   http://mierendo.com/software/aws_signed_query/
     */
    function _signedRequestURI($region, $params, $public_key, $private_key){
        $method = "GET";
        $host = "ecs.amazonaws.".$region;
        $uri = "/onca/xml";

        // additional parameters
        $params["Service"] = "AWSECommerceService";
        $params["AWSAccessKeyId"] = $public_key;
        // GMT timestamp
        $params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
        // API version
        $params["Version"] = "2009-11-01";

        // sort the parameters
        ksort($params);

        // create the canonicalized query
        $canonicalized_query = array();
        foreach ($params as $param=>$value)
        {
            $param = str_replace("%7E", "~", rawurlencode($param));
            $value = str_replace("%7E", "~", rawurlencode($value));
            $canonicalized_query[] = $param."=".$value;
        }
        $canonicalized_query = implode("&", $canonicalized_query);

        // create the string to sign
        $string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;

        // calculate HMAC with SHA256 and base64-encoding
        if(function_exists('hash_hmac')){
            $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, true));
        }elseif(function_exists('mhash')){
            $signature = base64_encode(mhash(MHASH_SHA256, $string_to_sign, $private_key));
        }else{
            msg('missing crypto function, can\'t sign request',-1);
        }

        // encode the signature for the request
        $signature = str_replace("%7E", "~", rawurlencode($signature));

        // create request
        return "http://".$host.$uri."?".$canonicalized_query."&Signature=".$signature;
    }

    /**
     * Output a single item
     */
    function _format($item,$param){
        if(isset($item['ITEM'])) $item = $item['ITEM'][0]; // sub item?
        $attr = $item['ITEMATTRIBUTES'][0];
        if(!$attr) $attr = $item['UNIVERSALLISTITEM'][0];
        if(!$attr) return ''; // happens on list items no longer in catalogue

//        dbg($item);
//        dbg($attr);

        $img = '';
        if(!$img) $img = $item['UNIVERSALLISTITEM'][0]['IMAGEURL'][0]['VALUE'];
        if(!$img) $img = $item['MEDIUMIMAGE'][0]['URL'][0]['VALUE'];
        if(!$img) $img = $item['IMAGESETS'][0]['IMAGESET'][0]['MEDIUMIMAGE'][0]['URL'][0]['VALUE'];
        if(!$img) $img = $item['LARGEIMAGE'][0]['URL'][0]['VALUE'];
        if(!$img) $img = $item['IMAGESETS'][0]['IMAGESET'][0]['LARGEIMAGE'][0]['URL'][0]['VALUE'];
        if(!$img) $img = $item['SMALLIMAGE'][0]['URL'][0]['VALUE'];
        if(!$img) $img = $item['IMAGESETS'][0]['IMAGESET'][0]['SMALLIMAGE'][0]['URL'][0]['VALUE'];
        if(!$img) $img = 'http://images.amazon.com/images/P/01.MZZZZZZZ.gif'; // transparent pixel

        $img = ml($img,array('w'=>$param['imgw'],'h'=>$param['imgh']));

        $link = $item['DETAILPAGEURL'][0]['VALUE'];
        if(!$link) $link = $item['UNIVERSALLISTITEM'][0]['PRODUCTURL'][0]['VALUE'];

        ob_start();
        print '<div class="amazon">';
        print '<a href="'.$link.'"';
        if($conf['target']['extern']) print ' target="'.$conf['target']['extern'].'"';
        print '>';
        print '<img src="'.$img.'" width="'.$param['imgw'].'" height="'.$param['imgh'].'" alt="" />';
        print '</a>';


        print '<div class="amazon_author">';
        if($attr['AUTHOR']){
            $this->display($attr['AUTHOR'],$param['maxlen']);
        }elseif($attr['DIRECTOR']){
            $this->display($attr['DIRECTOR'],$param['maxlen']);
        }elseif($attr['ARTIST']){
            $this->display($attr['ARTIST'],$param['maxlen']);
        }elseif($attr['STUDIO']){
            $this->display($attr['STUDIO'],$param['maxlen']);
        }elseif($attr['LABEL']){
            $this->display($attr['LABEL'],$param['maxlen']);
        }elseif($attr['BRAND']){
            $this->display($attr['BRAND'],$param['maxlen']);
        }elseif($attr['SOLDBY'][0]['VALUE']){
            $this->display($attr['SOLDBY'][0]['VALUE'],$param['maxlen']);
        }
        print '</div>';

        print '<div class="amazon_title">';
        print '<a href="'.$link.'"';
        if($conf['target']['extern']) print ' target="'.$conf['target']['extern'].'"';
        print '>';
        $this->display($attr['TITLE'][0]['VALUE'],$param['maxlen']);
        print '</a>';
        print '</div>';



        print '<div class="amazon_isbn">';
        if($attr['ISBN']){
            print 'ISBN ';
            $this->display($attr['ISBN'][0]['VALUE'],$param['maxlen']);
        }elseif($attr['RUNNINGTIME']){
            $this->display($attr['RUNNINGTIME'][0]['VALUE'].' ',$param['maxlen']);
            $this->display($attr['RUNNINGTIME'][0]['ATTRIBUTES']['UNITS'],$param['maxlen']);
        }elseif($attr['PLATFORM']){
            $this->display($attr['PLATFORM'][0]['VALUE'],$param['maxlen']);
        }
        print '</div>';

        if($param['price']){
            $price = $item['OFFERSUMMARY'][0]['LOWESTNEWPRICE'][0]['FORMATTEDPRICE'][0]['VALUE'];
            if(!$price) $price = $item['OFFERSUMMARY'][0]['LOWESTUSEDPRICE'][0]['FORMATTEDPRICE'][0]['VALUE'];
            if(!$price) $price = $attr['SAVEDPRICE'][0]['FORMATTEDPRICE'][0]['VALUE'];
            if($price){
                print '<div class="amazon_price">'.hsc($price).'</div>';
            }
        }
        print '</div>';
        $out = ob_get_contents();
        ob_end_clean();

        return $out;
    }

    function display($input,$maxlen){
        $string = '';
        if(is_array($input)){
            foreach($input as $opt){
                if(is_array($opt) && $opt['VALUE']){
                    $string .= $opt['VALUE'].', ';
                }
            }
            $string = rtrim($string,', ');
        }else{
            $string = $input;
        }

        if($maxlen && utf8_strlen($string) > $maxlen){
            print '<span title="'.htmlspecialchars($string).'">';
            $string = utf8_substr($string,0,$maxlen - 3);
            print htmlspecialchars($string);
            print '&hellip;</span>';
        }else{
            print htmlspecialchars($string);
        }
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
