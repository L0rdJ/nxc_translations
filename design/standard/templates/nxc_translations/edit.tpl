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

<form method="post" action="{'/nxc_translations/edit'|ezurl( 'no' )}">

	<div class="context-block"><div class="box-bc"><div class="box-ml"><div class="box-content">
		<table class="list" cellspacing="0">

			<thead>
				<tr>
					<th width="61%">{'Select language'|i18n( 'extension/nxc_translation' )}</th>
					<th width="39%"></th>
				</tr>
			</thead>

			<tbody>
				<tr class="bgdark">
					<td width="60%">
						<select name="nxc_translations_language">
							<option value="not_selected">{'- Select language -'|i18n( 'extension/nxc_translation' )}</option>
							{foreach $languages as $language}
							<option value="{$language.id}" {if and( $current_language, eq( $language.id, $current_language.id ) )}selected="selected"{/if}>{$language.name}</option>
							{/foreach}
						</select>
					</td>
					<td width="40%">
						<input class="button" type="submit" name="SelectLanguage" value="{'Select'|i18n( 'extension/nxc_translation' )}" />
					</td>
				</tr>
			</tbody>

		</table>
	</div></div></div></div>

	{if gt( $contextes|count, 0 )}
	<div class="context-block"><div class="box-bc"><div class="box-ml"><div class="box-content">
		<table class="list" cellspacing="0">

			<thead>
				<tr>
					<th width="61%">{'Select context'|i18n( 'extension/nxc_translation' )}</th>
					<th width="39%"></th>
				</tr>
			</thead>

			<tbody>
				<tr class="bgdark">
					<td width="60%">
						<select name="nxc_translations_context">
							<option value="not_selected">{'- Select context -'|i18n( 'extension/nxc_translation' )}</option>
							{foreach $contextes as $context}
							<option value="{$context}" {if eq( $context, $current_context )}selected="selected"{/if}>{$context}</option>
							{/foreach}
						</select>
					</td>
					<td width="40%">
						<input class="button" type="submit" name="SelectContext" value="{'Select'|i18n( 'extension/nxc_translation' )}" />
					</td>
				</tr>
			</tbody>

		</table>
	</div></div></div></div>
	{/if}

	{if gt( $messages|count, 0 )}
	<div class="context-block">

		<div class="box-header">
			<h2 class="context-title">{'Messages (%count)'|i18n( 'extension/nxc_translation', '', hash( '%count', $messages|count ))}</h2>
		</div>

		<div class="box-bc"><div class="box-ml"><div class="box-content">
			<table class="list" cellspacing="0">

				<thead>
					<tr>
						<th width="40%">{'Message'|i18n( 'extension/nxc_translation' )}</th>
						<th width="60%">{'Translation'|i18n( 'extension/nxc_translation' )}</th>
					</tr>
				</thead>

				<tbody>

					{foreach $messages as $key => $message sequence array( 'bgdark', 'bglight' ) as $style}
					<tr class="{$style}">
						<td width="40%">
							{$message['source']} {if $message['comment']}({'comment'|i18n( 'extension/nxc_translation' )}: {$message['comment']}){/if}
						</td>
						<td width="60%">
							<textarea name="nxc_translations_messages_translation[{$key}]" rows="6" cols="64">{$message['translation']}</textarea>
							<input type="hidden" name="nxc_translations_messages_source[{$key}]" value="{$message['source']}" />
							<input type="hidden" name="nxc_translations_messages_comment[{$key}]" value="{$message['comment']}" />
						</td>
					</tr>
					{/foreach}

				</tbody>

			</table>
		</div></div></div>

	</div>

	<div class="button-left">
		<div class="block">
			<input class="button" type="submit" name="Update" value="{'Update'|i18n( 'extension/nxc_translation' )}" />
		</div>
	</div>
	{/if}

</form>