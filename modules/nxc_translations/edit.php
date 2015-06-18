<?php
/**
 * @package nxcTranslations
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    12 Sep 2011
 **/

$module = $Params['Module'];
$http   = eZHTTPTool::instance();

$errorMessage    = false;
$feedbackMessage = false;
$languages       = eZContentLanguage::fetchList();

if( $module->isCurrentAction( 'SelectLanguage' ) ) {
	if( $http->hasPostVariable( 'nxc_translations_language' ) ) {
		$languageID = $http->postVariable( 'nxc_translations_language' );
		$http->setSessionVariable(
			'nxc_translations_current_language',
			(int) $languageID
		);
		if( $languageID !== 'not_selected' ) {
			$feedbackMessage = ezpI18n::tr( 'extension/nxc_translations', 'Language is selected' );
		}
	}
}
if( $module->isCurrentAction( 'SelectContext' ) ) {
	if( $http->hasPostVariable( 'nxc_translations_context' ) ) {
		$context = $http->postVariable( 'nxc_translations_context' );
		$http->setSessionVariable(
			'nxc_translations_current_context',
			$context
		);
		if( $context !== 'not_selected' ) {
			$feedbackMessage = ezpI18n::tr( 'extension/nxc_translations', 'Context is selected' );
		}
	}
}

$currentLanguage = false;
if( $http->hasSessionVariable( 'nxc_translations_current_language' ) ) {
	$currentLanguageID = $http->sessionVariable( 'nxc_translations_current_language' );
	if( isset( $languages[ $currentLanguageID ] ) ) {
		$currentLanguage = $languages[ $currentLanguageID ];
	}
}

$translationFile = false;
if( $currentLanguage instanceof eZContentLanguage ) {
	try{
		$translationFile = new nxcTranslationsFile( $currentLanguage->attribute( 'locale' ) );
	} catch( Exception $e ) {
		$errorMessage = $e->getMessage();
	}
}

$currentContext = false;
$contextes      = array();
if(
	$currentLanguage instanceof eZContentLanguage
	&& $translationFile instanceof nxcTranslationsFile
) {
	$contextes = $translationFile->getContextes();

	if( $http->hasSessionVariable( 'nxc_translations_current_context' ) ) {
		$context = $http->sessionVariable( 'nxc_translations_current_context' );
		if( in_array( $context, $contextes ) ) {
			$currentContext = $context;
		}
	}
}

$messages = array();
if(
	$translationFile instanceof nxcTranslationsFile
	&& $currentContext !== false
) {
	if( $module->isCurrentAction( 'Update' ) ) {
		if(
			$http->hasPostVariable( 'nxc_translations_messages_source' )
			&& $http->hasPostVariable( 'nxc_translations_messages_comment' )
			&& $http->hasPostVariable( 'nxc_translations_messages_translation' )
		) {
			$sources      = $http->postVariable( 'nxc_translations_messages_source' );
			$comments     = $http->postVariable( 'nxc_translations_messages_comment' );
			$translations = $http->postVariable( 'nxc_translations_messages_translation' );
			foreach( $sources as $key => $source ) {
				$hasComment = isset( $comments[ $key ] ) && strlen( $comments[ $key ] ) > 0;
				$message = array(
					'context'     => $currentContext,
					'source'      => $source,
					'comment'     => isset( $comments[ $key ] ) ? $comments[ $key ] : null,
					'translation' => isset( $translations[ $key ] ) ? $translations[ $key ] : null
				);
				$translationFile->updateMessage( $message );
			}

			try{
				if( $translationFile->store() === true ) {
					$feedbackMessage = ezpI18n::tr( 'extension/nxc_translations', 'Translation file is updated' );
				}
			} catch( Exception $e ) {
				$errorMessage = $e->getMessage();
			}
		}
	}

	$messages = $translationFile->getMessages( $currentContext );
	if( count( $messages ) === 0 ) {
		$errorMessage = ezpI18n::tr(
			'extension/nxc_translations',
			'Context "%context" has no translation messages',
			null,
			array(
				'%context' => $currentContext
			)
		);
	}
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'error', $errorMessage );
$tpl->setVariable( 'feedback', $feedbackMessage );
$tpl->setVariable( 'languages', $languages );
$tpl->setVariable( 'current_language', $currentLanguage );
$tpl->setVariable( 'contextes', $contextes );
$tpl->setVariable( 'current_context', $currentContext );
$tpl->setVariable( 'messages', $messages );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:nxc_translations/edit.tpl' );
$Result['path'] = array(
	array(
		'url' => false,
		'text' => ezpI18n::tr( 'extension/nxc_translations', 'Translation' )
	)
);

?>
