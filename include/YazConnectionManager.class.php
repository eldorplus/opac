<?php
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2009 Kyung IL Tech, Inc.                          |
// +----------------------------------------------------------------------+
// |                                                                      |
// +----------------------------------------------------------------------+
// | Author: Dionylon Briones <deform_perspective@yahoo.co.uk>           |
// +----------------------------------------------------------------------+
//
// $Id: YazConnectionManager.class.php,v 1.1 2006/09/04 07:51:18 lon.briones Exp $

class YazConnectionManager {
    var $HostIds;
    var $YazIds;
    var $hits;
    var $ErrMsg;
    var $isDisplayMarc;
    var $marc_arr;
    
    function YazConnectionManager(){
        
    }
    function connect($hostid,$querystr) {
        $yazid = yaz_connect(YazConnectionManager::getHostAddr($hostid));
        
        if (!$this->isDisplayMarc) {
           yaz_syntax($yazid,'usmarc');
		} else {
           yaz_syntax($yazid,'sutrs');
		}
		
            
        yaz_element($yazid,"f"); 
		$arr_sortfield = array('title'=>'1=4','author'=>'1=1003','callno'=>'1=20','publisher'=>'1=1018','year'=>'1=30');
		if (isset($_REQUEST['sort'])){
			$sortfield = $arr_sortfield[$_REQUEST['sort']];
			$dir  = ($_REQUEST['sortdir']) ? $_REQUEST['sortdir'] : 'ia';
			$sort = $sortfield.' '.$dir;
			yaz_sort($yazid,$sort);
		}
		
        yaz_search($yazid, "rpn", $querystr);
		
    	yaz_wait();
    	
    	if (!$error = yaz_error($yazid)) {        
    	    $this->hits[$hostid] = yaz_hits($yazid);
            $this->YazIds[$hostid]=$yazid;
    	    return true;
    	} else {
            $this->ErrMsg[$hostid]=$error;
    	    return false;
    	}
    }
    function getSearchResults($hostid,$start,$limit,$yazSearchById=false){
        $i=0;
        $marc_arr = array();
        if (!$yazSearchById) {
        	unset($_SESSION['marc_saved_results']);
        }	
		
        while ($rec = yaz_record($this->YazIds[$hostid], $start, "raw")) {
            if ($i==$limit) break;
            if (empty($rec)) continue;
            //$marc_arr[$start] = $rec;
            $marc_arr[] = $rec;
			$_SESSION['marc_saved_results'][] =$rec;
            $start++; $i++;
        }
        $this->marc_arr = $marc_arr;
        return $this->marc_arr;
    }
    function getMarcArray() {
    	
    }
    function unsetMarc(){
    	unset($_SESSION['marc_saved_results']);
    }
    function getHostAddr($hostid) {
        $hosts = Language::getHosts();
        return $hosts[$hostid][0];
    }
    function getHostName($hostid){
        $hosts = Language::getHosts();
        return $hosts[$hostid][1];
    }
    function getHits($hostid){
        return $this->hits[$hostid];
    }
    
    /**
     * some servers does not support this query
     */
	function yazSearchById($identifier_standard,$hostid){
        $this->connect($hostid,sprintf('@attr 1=1007 "%s"',$identifier_standard));
        return $this->getSearchResults($hostid,1,1,true);
	}
	
	function yazSearchByDateRange($r1,$r2,$hostid){
      $query1 = '@and @attr 1=32 @attr 2=2 "'.$r2.'"
      @attr 1=32 @attr 2=4  "'.$r1.'"';
		
		$this->connect($hostid,$query1);
        return $this->getSearchResults($hostid,1,1,false);
	}
}
?>