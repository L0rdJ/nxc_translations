{if ne( $feedback, false() )}
<div class="message-feedback">
	<h2>{$feedback}</h2>
</div>
{/if}

{if ne( $error, false() )}
<div class="message-error">
	<h2>{$error}</h2>
</div>
{/if}

{if $message}
<form class="nxc-translations-edit-message" method="post" action="{concat( '/nxc_translations/edit_message/', $message_hash )|ezurl( 'no' )}">

	<table class="nxc-translations-edit-message" cellspacing="0" cellpadding="0">
		<tbody>

			<tr>
				<td><label>{'Context'|i18n( 'extension/nxc_translation' )}:<label></td>
				<td>{$message.context}</td>
			</tr>
			<tr>
				<td><label>{'Source'|i18n( 'extension/nxc_translation' )}:<label></td>
				<td>{$message.source}</td>
			</tr>
			{if $message.comment}
			<tr>
				<td><label>{'Comment'|i18n( 'extension/nxc_translation' )}:<label></td>
				<td>{$message.comment}</td>
			</tr>
			{/if}
			<tr>
				<td><label>{'Translation'|i18n( 'extension/nxc_translation' )}:<label></td>
				<td><input size="64" type="text" name="translation" value="{$message.translation}" /></td>
			</tr>
			<tr>
				<td></td>
				<td><input class="button" type="submit" name="Update" value="{'Update'|i18n( 'extension/nxc_translation' )}" /></td>
			</tr>

		</tbody>
	</table>

</form>
{/if}