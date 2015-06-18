<?php
/**
 * @package nxcTranslations
 * @class   nxcTranslationsHelper
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    21 Sep 2011
 **/

class nxcTranslationsFile
{
	private $DOMDocument = null;
	private $XPath       = null;
	private $filepath    = null;
	private $needsUpdate = false;

	public function __construct( $locale = null ) {
		if( $locale === null ) {
			$locale = eZLocale::instance()->localeFullCode();
		}
		$this->filepath = eZExtension::baseDirectory() . '/nxc_translations/translations/'
			. $locale . '/translation.ts';

		$this->loadXML();
	}

	private function loadXML() {
		if( file_exists( $this->filepath ) === false ) {
			throw new Exception(
				ezpI18n::tr(
					'extension/nxc_translations',
					'Translations file <br /> %filepath <br /> isn`t available. <br /> To create it, check out <br /> %command',
					null,
					array(
						'%filepath' => $this->filepath,
						'%command'  => 'php extension/nxc_translations/bin/php/make_translation.php -h'
					)
				)
			);
		}

		$this->DOMDocument = @DOMDocument::load( $this->filepath );
		if( $this->DOMDocument === false ) {
			throw new Exception(
				ezpI18n::tr(
					'extension/nxc_translations',
					'Translations file <br /> %filepath <br /> isn`t valid XML',
					null,
					array(
						'%filepath' => $this->filepath
					)
				)
			);
		}
		$this->XPath = new DOMXPath( $this->DOMDocument );
	}

	public function getContextes() {
		$contextes = array();

		$nodes = $this->XPath->query( '//context/name' );
		foreach( $nodes as $node ) {
			$context = (string) $node->nodeValue;
			if( in_array( $context, $contextes ) === false ) {
				$contextes[] = $context;
			}
		}
		asort( $contextes );

		return $contextes;
	}

	public function getMessages( $context = null ) {
		$messages = array();

		$XPathQuery = '//context/message';
		if( $context !== null ) {
			$XPathContext = nxcTranslationsHelper::escapeXPathSubExpression( $context );
			$XPathQuery   = '//context/name[.=' . $XPathContext . ']/parent::context/message';
		}

		$nodes = $this->XPath->query( $XPathQuery );
		foreach( $nodes as $node ) {
			$context = $node->parentNode->getElementsByTagName( 'name' )->item( 0 )->nodeValue;

			$tmp    = $node->getElementsByTagName( 'source' );
			$source = ( (int) $tmp->length > 0 )
				? (string) $tmp->item( 0 )->nodeValue
				: null;

			$tmp     = $node->getElementsByTagName( 'comment' );
			$comment = ( (int) $tmp->length > 0 )
				? (string) $tmp->item( 0 )->nodeValue
				: null;

			$tmp         = $node->getElementsByTagName( 'translation' );
			$translation = ( (int) $tmp->length > 0 )
				? (string) $tmp->item( 0 )->nodeValue
				: null;

			if(
				$source !== false
				&& $translation !== false
			) {
				$messages[] = array(
					'context'     => $context,
					'source'      => $source,
					'comment'     => $comment,
					'translation' => $translation
				);
			}
		}

		return $messages;
	}

	public function updateMessage( array $message ) {
		if(
			isset( $message['context'] ) === false
			|| isset( $message['source'] ) === false
			|| isset( $message['translation'] ) === false
		) {
			throw new Exception(
				ezpI18n::tr( 'extension/nxc_translations', 'Not valid message' )
			);
		}

		$XPathContext = nxcTranslationsHelper::escapeXPathSubExpression( $message['context'] );
		$XPathSource  = nxcTranslationsHelper::escapeXPathSubExpression( $message['source'] );
		$XPathQuery   = '//context/name[.=' . $XPathContext . ']/parent::context/message/source[.=' . $XPathSource . ']/parent::message';
		if( isset( $message['comment'] ) && strlen( $message['comment'] ) > 0 ) {
			$XPathComment = nxcTranslationsHelper::escapeXPathSubExpression( $message['comment'] );
			$XPathQuery .= '/comment[.=' . $XPathComment . ']/parent::message';
		} else {
			$XPathQuery .= '[not(comment)]';
		}

		$nodes = $this->XPath->query( $XPathQuery );
		if( (int) $nodes->length > 0 ) {
			$node = $nodes->item( 0 );

			$translation     = $message['translation'];
			$translationNode = $node->getElementsByTagName( 'translation' );
			if( (int) $translationNode->length > 0 ) {
				$translationNode = $translationNode->item( 0 );
				if( (string) $translationNode->nodeValue != $translation ) {
					$this->needsUpdate = true;

					if( $translationNode->firstChild ) {
						$translationNode->removeChild( $translationNode->firstChild );
					}
					if( strlen( $translation ) > 0 ) {
						$translationNode->appendChild( $this->DOMDocument->createTextNode( $translation ) );
						if(
							$translationNode->hasAttribute( 'type' )
							&& $translationNode->getAttribute( 'type' ) == 'unfinished'
						) {
							$translationNode->removeAttribute ( 'type' );
						}
					} else {
						$translationNode->setAttribute( 'type', 'unfinished' );
					}

					return true;
				}
			}
		}

		return false;
	}

	public function store() {
		if( $this->needsUpdate === true ) {
			if( is_writable( $this->filepath ) === false ) {
				$errorMessage = ezpI18n::tr(
					'extension/nxc_translations',
					'Translations file <br /> %filepath <br /> isn`t writable',
					null,
					array(
						'%filepath' => $filepath
					)
				);
			}

			$this->DOMDocument->formatOutput = true;
			$this->DOMDocument->save( $this->filepath );
			return true;
		}
	}

	public function getMessageByHash( $hash ) {
		$messages = $this->getMessages();
		foreach( $messages as $message ) {
			$messageHash = self::getMessageHash(
				$message['context'],
				$message['source'],
				$message['comment']
			);
			$messageHash = str_replace( '[nxc-tr]', '', $messageHash );
			if( $hash == $messageHash ) {
				return $message;
			}
		}

		throw new Exception(
			ezpI18n::tr( 'extension/nxc_translations', 'Message has no translation' )
		);
	}

	public static function getMessageHash( $context, $source, $comment = null ) {
		return '[nxc-tr]' . md5( $context . '|' . $source . '|' . $comment ) . '[nxc-tr]';
	}
}
?>
