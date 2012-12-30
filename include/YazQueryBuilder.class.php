<?php
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2009 Kyung IL Tech, Inc.                          |
// +----------------------------------------------------------------------+
// |                                                                      |
// +----------------------------------------------------------------------+
// | Author: Dionylon Briones <deform_perspective@yahoo.co.uk>           |
// +----------------------------------------------------------------------+
//
// $Id: YazQueryBuilder.class.php,v 1.1 2006/09/04 07:51:18 lon.briones Exp $

class YazQueryBuilder {
    var $fullquery=array();
	var $format=array();
	var $term=array();
	var $date=array();
	var $field=array();
	var $boolean=array();
	var $zserver=array();
	var $aWordOpt = '@attr 3=1';
	//chung
	var $aMtypeOpt = array();
	var $locationOpt = '';

	function YazQueryBuilder(){
	    
	}	
	function buildQuery(){
		$index=0;
		$aQry = array();	
		$aOpr = array();
		$aAtr = array();
		
		$fullquery="";
		$this->fieldmap = Language::getLocalMonoSearchFields();
		
		foreach ($this->term as $key => $term){
			if(!empty($term)) {
				$term = $this->remove_trash($term);
				if ($this->field[$index]=="ISBN")
				  $cISBN = $term;
				$aQry[] = $term;
				$aOpr[] = $this->boolean[$index];
				$aAtr[] = $this->fieldmap[$this->field[$index]];
				$index++;
			}
		}

		foreach ($this->date as $key => $date){
			if(!empty($date)) {
				$date = $this->remove_trash($date);
				$aQry[] = $date;
				$aOpr[] = "@and";
				$aAtr[] = $this->fieldmap["Date"].' '.$this->fieldmap["Greater_or_Equal"];
			}
		}

        /*
		if(!empty($this->language)) {
			if ($this->language!='Any')
			{
			   $aQry[] = $this->language;
			   $aOpr[] = "@and";
			   $aAtr[] = $this->fieldmap["Language"];
			}
		}
		*/

		
		/*
		foreach ($this->format as $key => $format){
			if(!empty($format))
				if ($format!="zzArticle"){
					$format = $this->remove_trash($format);
					$aQry[] = $format;
					$aOpr[] = "@or";
					$aAtr[] = $this->fieldmap["MaterialType"];
				}
		}
        */

        /*
		for ($i = count($aQry)-1; $i >=0 ; $i--) {
		   if ($i==count($aQry)-1)
				$fullquery = $aAtr[$i] . ' '.$this->aWordOpt.' "' . $aQry[$i] . '" '.$fullquery;
		   else
			{
			   if ($i<3)
					$fullquery = $aOpr[$i].' '.$aAtr[$i] . ' "' . $aQry[$i] . '" '.$fullquery;
			   else
					$fullquery = $aOpr[$i+1].' '.$aAtr[$i] . ' "' . $aQry[$i] . '" '.$fullquery;
			}
		}
		*/

        
		for ($i = count($aQry)-1; $i >=0  ; $i--) {
			if ( $i==count($aQry)-1 ) {
               if (empty($this->aMtypeOpt[0])) 
 				 $fullquery = $aAtr[$i] . ' '.$this->aWordOpt.' "' . $aQry[$i] . '" '.$fullquery;
			   else
 				 $fullquery ='@and'.' '. $aAtr[$i] . ' '.$this->aWordOpt.' "' . $aQry[$i] . '" '.$fullquery.' @attr 1=1031 '.' "' .$this->aMtypeOpt[0].' "' ;
			}
			else
 			  $fullquery = $aOpr[$i].' '.$aAtr[$i] . ' '.$this->aWordOpt.' "' . $aQry[$i] . '" '.$fullquery;
		}


        if ($this->locationOpt!='All' && !empty($this->locationOpt)) {
           $fullquery = '@and @attr 1=1019 '.'"'.$this->locationOpt.'" '.$fullquery;
		}


//        print $this->aWordOpt;

//print  $this->aMtypeOpt[0].'<br>';
//        if(empty($format))
//  		   print $fullquery;

        
		return $this->fullquery=$fullquery;	    
	}
	
	function remove_trash($term)
	{
		$term = stripslashes($term);
		$term = ereg_replace('"', "", $term);
		$term = ereg_replace("\?$", "",  $term);
		return $term;
	}	  
	function getQueryString() {
	    return $this->fullquery;
	}  
}
?>