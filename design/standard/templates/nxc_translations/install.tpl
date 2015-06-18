{ezcss_require(
	array(
		'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/base/jquery-ui.css',
		'nxc.translations.css'
	)
)}
{ezscript_require(
	array(
		'ezjsc::jquery',
		'jquery-ui-1.8.16.custom.min.js',
		'nxc.frontendtranslation.js'
	)
)}

{literal}
<script type="text/javascript">
jQuery( NXC.FrontEndTranslation.install );
</script>
{/literal}