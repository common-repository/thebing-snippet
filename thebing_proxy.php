<?php

require($_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/thebing-snippet/tc/class.snippet.php");

$sUrl = '{FIDELO_URL}';

if(
	!empty($_GET['type'])
) {

	$sSubUrl = '';

	if($_GET['type'] === 'menue') {
		
		$sSubUrl = '/wdmvc/ta-frontend/menue/structure';
		
	} else if($_GET['type'] === 'search') {
				
		$sSubUrl = '/wdmvc/ta-frontend-search/api/search/'.$_GET['task'];		
		
	} else if($_GET['type'] === 'shopping_cart') {	
		
		$sSubUrl = '/wdmvc/ta-frontend-shoppingcard/products/'.$_GET['task'];
		
	} else if($_GET['type'] === 'product_favorites') {	
		
		$sSubUrl = '/wdmvc/ta-frontend-favorites/products/'.$_GET['task'];
		
	} else if($_GET['type'] === 'product_comparison') {	
		
		$sSubUrl = '/wdmvc/ta-frontend-comparison/products/'.$_GET['task'];	
		
	} else if($_GET['type'] === 'products') {	
		
		$sSubUrl = '/wdmvc/ta-frontend/product/'.$_GET['task'];	
		
	} else if($_GET['type'] === 'file') {	
		
		$sSubUrl = '/wdmvc/ta-frontend/file/'.$_GET['task'];
		
	} else if(
		$_GET['type'] === 'registration' &&
		!empty($_GET['task'])
	) {
		
		if($_GET['task'] === 'loadCombinations') {
			$sSubUrl = '/wdmvc/ta-frontend/registration/combination';
		} else if($_GET['task'] === 'loadServiceCombinations') {
			$sSubUrl = '/wdmvc/ta-frontend/registration/servicecombination';
		} else if($_GET['task'] === 'loadPrices') {
			$sSubUrl = '/wdmvc/ta-frontend/registration/prices';
		} else if($_GET['task'] === 'loadReferrerData') {
			$sSubUrl = '/wdmvc/ta-frontend/registration/referrer';
		} else if($_GET['task'] === 'loadAdditionalServices') {
			$sSubUrl = '/wdmvc/ta-frontend/registration/additionalservices';
		} else if($_GET['task'] === 'deleteForm') {
			$sSubUrl = '/wdmvc/ta-frontend/registration/delete';
		} else if($_GET['task'] === 'getFile') {
			$sSubUrl = '/wdmvc/ta-frontend/file/get';
		} else if($_GET['task'] === 'loadPdf') {
			$sSubUrl = '/wdmvc/ta-frontend/registration/pdf';
		} else if($_GET['task'] === 'showPdf') {
			$sSubUrl = '/wdmvc/ta-frontend/pdf/registration';			
		} else {
			$sSubUrl = '/wdmvc/ta-frontend/registration/'.$_GET['task'];
		}
		
	}

	if(!empty($sSubUrl)) {
		$sUrl .= $sSubUrl;

		$oThebing = new Thebing_Snippet($sUrl, $_GET['frontend_key']);
		$oThebing->execute();
	
		echo $oThebing->getContent();
	}
}

