<?php
/**
 * @package nxcTranslations
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    20 Sep 2011
 **/

$http = eZHTTPTool::instance();
nxcTranslationsHelper::setTranslationsEditor(
	 $http->hasSessionVariable( 'NXCTranlsationsEditor' )
	 	? !(bool) $http->sessionVariable( 'NXCTranlsationsEditor' )
		: true
);
eZCache::clearByTag( 'template' );
eZCache::clearByTag( 'content' );

$referer = eZSys::serverVariable( 'HTTP_REFERER' );
return $Params['Module']->redirectTo( $referer ? $referer : '/' );
?>
