<?php
/**
 * @package nxcTranslations
 * @class   nxcTranslationsHelper
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    21 Sep 2011
 **/

class nxcTranslationsHelper
{
	public static function isTranslationsEditorEnabled() {
		$http = eZHTTPTool::instance();
		return $http->hasSessionVariable( 'NXCTranlsationsEditor' )
			? (bool) $http->sessionVariable( 'NXCTranlsationsEditor' )
			: false;
	}

	public static function setTranslationsEditor( $mode = true ) {
		$http = eZHTTPTool::instance();
		$http->setSessionVariable( 'NXCTranlsationsEditor', (bool) $mode );
	}

	public static function handleOutput( $output ) {
		if( self::isTranslationsEditorEnabled() === false ) {
			return $output;
		}

		// Prevent caching
		eZCache::clearByTag( 'template' );
		eZCache::clearByTag( 'content' );

		$quotes       = array( '"', '\'' );
		$attributes   = array( 'value', 'alt', 'title', 'summary', 'rel' );
		$skipElements = array( 'title' );

		// Get all buttons value
		$defaultPattern = '/(<input[^>]*type=Q(button|submit)Q[^>]*value=Q[^Q]*)(\[nxc-tr\][0-9a-f]{32}\[nxc-tr\])+([^Q]*Q[^>]*\/>)/im';
		foreach( $quotes as $quote ) {
			$pattern = str_replace( 'Q', $quote, $defaultPattern );
			$l = 0;
			do {
				preg_match_all( $pattern, $output, $matches );
				$output = preg_replace( $pattern, '$1$4$3', $output );
				$l++;
			} while(
				isset( $matches[2] )
				&& count( $matches[2] ) > 0
				&& $l < 10
			);
		}

		// Remove translation hashes from skip elements
		$defaultPattern = '/(<[^>]*ELEMENT[^>]*>[^<]*)(\[nxc-tr\][0-9a-f]{32}\[nxc-tr\])+([^<]*<\/[^>]*ELEMENT[^>]*>)/im';
		foreach( $skipElements as $element ) {
			$pattern = str_replace( 'ELEMENT', $element, $defaultPattern );
			$l = 0;
			do {
				preg_match_all( $pattern, $output, $matches );
				$output = preg_replace( $pattern, '$1$3', $output );
				$l++;
			} while(
				isset( $matches[2] )
				&& count( $matches[2] ) > 0
				&& $l < 10
			);
		}

		// Remove translation hashes from element`s attributes
		$defaultPattern = '/(ATTRIBUTE=Q[^Q]*)(\[nxc-tr\][0-9a-f]{32}\[nxc-tr\])+([^Q]*Q)/im';
		foreach( $attributes as $attribute ) {
			$attributePattern = str_replace( 'ATTRIBUTE', $attribute, $defaultPattern );
			foreach( $quotes as $quote ) {
				$pattern = str_replace( 'Q', $quote, $attributePattern );
				$l = 0;
				do {
					preg_match_all( $pattern, $output, $matches );
					$output = preg_replace( $pattern, '$1$3', $output );
					$l++;
				} while(
					isset( $matches[2] )
					&& count( $matches[2] ) > 0
					&& $l < 10
				);
			}
		}

		// Get image URL
		$image      = 'nxc-translations-translate.png';
		$bases      = eZTemplateDesignResource::allDesignBases();
		$triedFiles = array();
		$fileInfo   = eZTemplateDesignResource::fileMatch( $bases, 'images', $image, $triedFiles );

		if( $fileInfo === false ) {
			$siteDesign = eZTemplateDesignResource::designSetting( 'site' );
			$imgPath    = 'design/' . $siteDesign . '/images/' . $image;
		} else {
			$imgPath = $fileInfo['path'];
		}
		$imgPath = htmlspecialchars( eZSys::wwwDir() . '/' . $imgPath );

		$url = '/nxc_translations/edit_message/TRANSLATION_HASH';
		eZURI::transformURI( $url );

		// Replace translation hashes with elements
		// We store hash to the class, because they maybe the same hashes on the page
		$pattern = '/\[nxc-tr\]([0-9a-f]{32})\[nxc-tr\]/';
		$output = preg_replace(
			$pattern,
			'<img class="nxc-translations-icon nxc-translations-translatable nxc-translations-message-id-$1" src="'. $imgPath . '" alt="' . $url . '" />',
			$output
		);

		return $output;
	}

	public static function escapeXPathSubExpression( $s ) {
		$specials         = array('"', "'");
		$specialsReplaced = array('\'"\'', '"\'"');
		$isFound          = false;

		foreach( $specials as $sp ) {
			if( mb_strpos( $s, $sp, 0, 'utf-8' ) !== false ) {
				$isFound = true;
				break;
			}
		}

		if( $isFound === false ) {
			return '"' .$s. '"';
		}

		$substs = array();
		foreach( $specials as $i => $sp ) {
			$substs[] = "##$i##";
		}

		$s = str_replace( $specials, $substs, $s );
		$substrings = preg_split( '/(##\d+##)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE );

		foreach( $substrings as $i => $substr ) {
			$substr = str_replace( $substs, $specials, $substr );

			if( ( $idx = array_search( $substr, $specials ) ) === false ) {
				$substrings[$i] = '"' . $substr . '"';
			} else {
				$substrings[$i] = $specialsReplaced[$idx];
			}
		}

		return 'concat(' . implode( ',', $substrings ) . ')';
	}
}
?>
