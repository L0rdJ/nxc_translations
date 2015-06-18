<?php
/**
 * @package nxcTranslations
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    12 Sep 2011
 **/

$Module = array(
	'name'            => 'NXC Translations',
 	'variable_params' => true
);

$ViewList = array(
	'edit' => array(
		'functions'               => array( 'edit' ),
		'script'                  => 'edit.php',
		'params'                  => array(),
		'default_navigation_part' => 'ezsetupnavigationpart',
		'single_post_actions' => array(
			'SelectLanguage' => 'SelectLanguage',
			'SelectContext'  => 'SelectContext',
			'Update'         => 'Update'
		)
	),
	'edit_message' => array(
		'functions'           => array( 'edit' ),
		'script'              => 'message/edit.php',
		'params'              => array( 'MessageHash' ),
		'single_post_actions' => array(
			'Update' => 'Update'
		)
	),
	'toggle_editor' => array(
		'functions' => array( 'edit' ),
		'script'    => 'toggle_editor.php',
		'params'    => array()
	)
);


$FunctionList = array(
	'edit' => array()
);
?>
