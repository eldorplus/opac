<?php
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2009 Kyung IL Tech, Inc.                          |
// +----------------------------------------------------------------------+
// |                                                                      |
// +----------------------------------------------------------------------+
// | Author: Dionylon Briones <deform_perspective@yahoo.co.uk>           |
// +----------------------------------------------------------------------+
//
// $Id: detailview.php,v 1.1 2006/09/04 07:51:41 lon.briones Exp $


require(MODDIR.'search/DataGrid.class.php');
//require(INCDIR.'YazConnectionManager.class.php');
/*
print '<pre>';
print_r($_REQUEST);
print_r($_SESSION);
print '</pre>';
*/
require_once(INCDIR.'marc_parser.php');
$hostid = $_REQUEST['curhost'];
if ($hostid === 1) {
	$marctags = language::getArticleMarcTags();
} else {
	$marctags = language::getMarcTags();
}
/*
$yazconn2 =& new YazConnectionManager();
$hostname = $yazconn2->getHostName($hostid);
$yazconn2->isDisplayMarc = true;
$rec = $yazconn2->yazSearchById($id,$hostid);
print $marc = $rec[0];

$yazconn =& new YazConnectionManager();
$hostname = $yazconn->getHostName($hostid);
$rec = $yazconn->yazSearchById($id,$hostid);
$marc = $rec[0];
*/

$marc = $_SESSION['marc_saved_results'][$_REQUEST['id']];

if (isset($_REQUEST['save_marc'])) {
	$new_name = date("YmdHim").'.mrc';
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // some day in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Content-type: application/x-download");
	header("Content-Disposition: attachment; filename={$new_name}");
	header("Content-Transfer-Encoding: binary");
	print $marc;
	exit;
	//$content = THEMESDIR.THEME.'view_raw_marc.html';
} else {
	$smarty->assign('FID',$id);
	
	//$marc = $_SESSION['marc'][$id];
	$format = marcParse($marc,"998",SUBFLD.'a'); 
	
	if (isset($_REQUEST['marc_view']))
		$detail = DataGrid::getDetail($marc,true);
	else $detail = DataGrid::getDetail($marc);
	
	$titleauthor = explode('/',$detail[245][0]);
	$title = $titleauthor[0];
	$author = '';
	if (isset($titleauthor[1]))
		$author = $titleauthor[1];
	//$author = "<a href=\"?mode={$_REQUEST['mode']}&m=search&s=local&search=1&field%5B0%5D=Author&term%5B0%5D=$author&host%5B%5D=$hostid\">".$author.'</a>';
	$smarty->assign('title',$title);
	$smarty->assign('author',$author);
	$marc = array ();
	$i = 0;
	foreach ($detail as $tag => $vals) {
		
		if ( ($tag != 245) and ( substr($tag,0,1) !=9 ) ) {
			if (isset($_REQUEST['marc_view']))
				$marc[$i]['tag'] = $tag.":";
				else $marc[$i]['tag'] = isset($marctags[$tag]) ? $marctags[$tag] : $tag.":";
			
			foreach ($vals as $k => $v) {
				if ($tag == '260') {
					//$marc[$i]['value'] = "<a href=\"?mode={$_REQUEST['mode']}&m=search&s=local&search=1&field%5B0%5D=Publisher&term%5B0%5D=$v&host%5B%5D=$hostid\">$v</a>";
					$marc[$i]['value'] = $v;
				} elseif ($tag == '650') {
					//$marc[$i]['value'] = "<a href=\"?mode={$_REQUEST['mode']}&m=search&s=local&search=1&field%5B0%5D=Subject&term%5B0%5D=$v&host%5B%5D=$hostid\">$v</a>";
					$marc[$i]['value'] = $v;
				} elseif ($tag == '856'){
					$marc[$i]['value'] = '<a href="'.$v.'">'.$v.'</a>';
				} else {
				    
					$marc[$i]['value'] = $v;
				}

			    $i++;
			}

		}
		
	}
	
	$smarty->assign('marc',$marc);
	
	require(MODDIR.'search/moreinfo.php');
	if ($format == 'zzSerials') {
		require(MODDIR.'search/serial.php');
	}
	
	$content = THEMESDIR.THEME.'detailview.html';
}
?>