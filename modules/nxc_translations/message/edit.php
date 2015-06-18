<?php
/**
 * @package nxcTranslations
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    20 Sep 2011
 **/

$isEnabled = nxcTranslationsHelper::isTranslationsEditorEnabled();
nxcTranslationsHelper::setTranslationsEditor( false );

$module  = $Params['Module'];
$http    = eZHTTPTool::instance();
$hash    = $Params['MessageHash'];
$message = array();

$errorMessage    = false;
$feedbackMessage = false;

try{
	$translationFile = new nxcTranslationsFile();
	$message = $translationFile->getMessageByHash( $hash );
} catch( Exception $e ) {
	$errorMessage = $e->getMessage();
}

if(
	$errorMessage === false
	&& $module->isCurrentAction( 'Update' )
	&& $http->hasPostVariable( 'translation' )
) {
	try{
		$message['translation'] = $http->postVariable( 'translation' );

		$translationFile->updateMessage( $message );
		$translationFile->store();
		$feedbackMessage = ezpI18n::tr(
			'extension/nxc_translations',
			'Translation file is updated. Clear caches and reload the page to apply the changes.'
		);
	} catch( Exception $e ) {
		$errorMessage = $e->getMessage();
	}
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'error', $errorMessage );
$tpl->setVariable( 'feedback', $feedbackMessage );
$tpl->setVariable( 'message_hash', $hash );
$tpl->setVariable( 'message', $message );

echo $tpl->fetch( 'design:nxc_translations/message/edit.tpl' );

nxcTranslationsHelper::setTranslationsEditor( $isEnabled );
eZExecution::cleanExit();
?>
