<?php
/**
 * @package nxcTranslations
 * @class   nxcMakeTranslationScript
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    03 Jun 2011
 **/

class nxcMakeTranslationScript
{
	private $cli                  = null;
	private $debugFile            = array( 'var/log/', 'translations.log' );
	private $debug                = null;
	private $script               = null;
	private $googleAPIKey         = false;
	private $translationInfo      = array();
	private $translationPath      = null;
	private $locale               = null;
	private $untranslatedMessages = array();

	private static $regExpressions = array(
		'php' => '/(ezi18n|ezpI18n::tr)\s*\(\s*Q1([^Q1]+)Q1\s*,\s*Q2([^Q2]+)Q2/i',
		'tpl' => '/Q1([^Q1]*)Q1\s*\|\s*i18n\s*\(\s*Q2([^Q2]*)Q2/i'
	);
	private static $quotes = array( '\'', '"' );

	public function __construct() {
		$this->cli = eZCLI::instance();
		$this->cli->setUseStyles( true );

		@unlink( $this->debugFile[0] . $this->debugFile[1] );
		$this->debug = eZDebug::instance();

		$scriptSettings = array(
			'description'    => 'Creates transaltion files',
			'use-session'    => false,
			'use-modules'    => false,
			'use-extensions' => true
		);
		$this->script = eZScript::instance( $scriptSettings );
	}

	public function run() {
		eZCache::clearByTag( 'Content' );

		$this->script->startup();
		$this->script->initialize();

		$options = $this->script->getOptions(
			'[extensions:][google_api_key:][include_kernel_files:]',
			'[locale]',
			array(
				'locale'               => 'Translation`s locale',
				'include_kernel_files' => 'kernel/*.php, include/*.php and design/*.tpl files will be parserd if this option is set',
				'extensions'           => 'Extensions list (separeted by comma), for which php files and templates will be parsed for translations (files in all extensions will be parsed if this options is empty)',
				'google_api_key'       => 'Google Translate API key'
			)
		);

		if( $options['google_api_key'] !== null ) {
			$this->googleAPIKey = $options['google_api_key'];
		}
		$filterExtensions = array();
		if( $options['extensions'] !== null ) {
			$extensions = explode( ',', $options['extensions'] );
			foreach( $extensions as $extension ) {
				$filterExtensions[] = trim( $extension );
			}
		}

		if( count( $options['arguments'] ) < 1 ) {
			$this->cli->error( 'Specify locale' );
			$this->script->shutdown( 1 );
		}

		$this->locale = $options['arguments'][0];
		$this->translationPath = eZExtension::baseDirectory() . '/nxc_translations/translations';
		if( is_writable( $this->translationPath ) === false ) {
			$this->cli->error( 'Directory "' . $this->translationPath . '" isn`t writable' );
			$this->script->shutdown( 1 );
		}
		$GLOBALS['eZLocaleStringDefault'] = $this->locale;
		eZTranslatorManager::enableDynamicTranslations( true );

		$this->translationPath .= '/' . $this->locale;
		if( is_dir( $this->translationPath ) === false ) {
			mkdir( $this->translationPath, 0755 );
		}

		$file = $this->translationPath . '/translation.ts';
		if( file_exists( $file ) ) {
			@unlink( $file );
		}

		$extensions = eZExtension::activeExtensions();
		foreach( $extensions as $extension ) {
			if(
				count( $filterExtensions ) > 0
				&& in_array( $extension, $filterExtensions ) === false
			) {
				continue;
			}
			$path = eZExtension::baseDirectory() . '/' . $extension;
			$this->cli->output(
				$this->cli->stylize( 'yellow', 'Searching for translatiable texts in "' . $extension . '" extension' )
			);

			$files = $this->findPHPFiles( $path );
			$this->parseFiles( $files, 'php' );

			$files = $this->findTPLFiles( $path );
			$this->parseFiles( $files, 'tpl' );
		}

		if( $options['include_kernel_files'] !== null ) {
			$this->cli->output(
				$this->cli->stylize( 'yellow', 'Searching for translatiable texts in "kernel" files' )
			);

			$files = $this->findPHPFiles( 'kernel' );
			$this->parseFiles( $files, 'php' );
			$files = $this->findPHPFiles( 'lib' );
			$this->parseFiles( $files, 'php' );
			$files = $this->findTPLFiles( 'design' );
			$this->parseFiles( $files, 'tpl' );
		}

		if( $this->googleAPIKey !== false ) {
			$this->translateUntranslatedMessages();
		}

		$this->makeTranslationFile();

		$this->script->shutdown( 0 );
	}

	private function findPHPFiles( $path ) {
		$this->cli->output(
			$this->cli->stylize( 'white', 'Parsing php files...' )
		);
		return ezcBaseFile::findRecursive( $path, array( '@.*.php$@' ) );
	}

	private function findTPLFiles( $path ) {
		$this->cli->output(
			$this->cli->stylize( 'white', 'Parsing template files...' )
		);
		return ezcBaseFile::findRecursive( $path, array( '@.*.tpl$@' ) );
	}

	private function parseFiles( $files, $type ) {
		if( isset( self::$regExpressions[ $type ] ) === false ) {
			return false;
		} else {
			$regEx = self::$regExpressions[ $type ];
		}

		foreach( $files as $file ) {
			$message = 'Parsing "' . $file . '" file';
			$this->debug->writeFile(
				$this->debugFile,
				$message,
				eZDebug::LEVEL_DEBUG,
				true
			);

			$fileContent = @file_get_contents( $file );
			$fileContent = str_replace(
				array( '\\\'', '\\"' ),
				array( 'escaped_single_quote', 'escaped_double_qoute' ),
				$fileContent
			);
			foreach( self::$quotes as $firstArgumentQuote ) {
				foreach( self::$quotes as $secondArgumentQuote ) {
					$matches    = array();
					$expression = str_replace( 'Q1', $firstArgumentQuote, $regEx );
					$expression = str_replace( 'Q2', $secondArgumentQuote, $expression );
					preg_match_all(
						$expression,
						$fileContent,
						$matches
					);

					if( count( $matches[1] ) > 0 ) {
						if( $type == 'php' ) {
							$this->processFileTranslations( $matches[2], $matches[3] );
						} else {
							$this->processFileTranslations( $matches[2], $matches[1] );
						}
					}
				}
			}
		}
	}

	private function processFileTranslations( array $contexts, array $messages ) {
		foreach( $contexts as $index => $context ) {
			$context = str_replace(
				array( 'escaped_single_quote', 'escaped_double_qoute' ),
				array( '\'', '"' ),
				$context
			);
			if( isset( $this->translationInfo[ $context ] ) === false ) {
				$this->translationInfo[ $context ] = array();
			}

			$message = $messages[ $index ];
			$message = str_replace(
				array( 'escaped_single_quote', 'escaped_double_qoute' ),
				array( '\'', '"' ),
				$message
			);
			// fix for google
			$message = str_replace(
				array( '&nbsp;', '&copy;' ),
				array( ' ', '&amp;copy;' ),
				$message
			);
			if( in_array( $message, $this->translationInfo[ $context ] ) === false ) {
				$this->translationInfo[ $context ][] = $message;

				$debugMessage = 'Context "' . $context . '", message "' . $message . '"';
				$translation = ezpI18n::tr( $context, $message );
				if( $translation !== $message ) {
					$debugMessage .= ', available translation: "' . $translation . '"';
				} else {
					if( isset( $this->untranslatedMessages[ $context ] ) === false ) {
						$this->untranslatedMessages[ $context ] = array();
					}
					if( isset( $this->untranslatedMessages[ $context ][ $message ] ) === false ) {
						$this->untranslatedMessages[ $context ][ $message ] = $message;
					}
				}

				$this->debug->writeFile(
					$this->debugFile,
					$debugMessage,
					eZDebug::LEVEL_DEBUG,
					true
				);
			}
		}
	}

	private function translateUntranslatedMessages() {
		$data = array();
		foreach( $this->untranslatedMessages as $context => $messages ) {
			foreach( $messages as $message ) {
				$data[] = $message;
			}
		}
		//$data = array_splice( $data, 0, 20 );

		$locale = eZLocale::localeInformation( $this->locale );
		$translateAPIKey = 'AIzaSyDhRuIK6guzML_UsVabCm-iBwAqWKcivbk';
		$url = 'https://www.googleapis.com/language/translate/v2?key=' . $this->googleAPIKey;
		$url .= '&source=en';
		$url .= '&target=' . strtolower( $locale['country'] );
		$this->cli->output(
			$this->cli->stylize( 'yellow', 'Translating ' . count( $data ) . ' messages at ' . $url )
		);

		$i         = 0;
		$portion   = 20;
		$dataCount = count( $data );
		while( $i * $portion < $dataCount ) {
			$requestData = array_slice( $data, $i * $portion, $portion );

			$params = array();
			foreach( $requestData as $key => $message ) {
				// Google strips some spaces after arguments
				preg_match_all( '/%([a-zA-z-_]+)/i', $message, $matches );
				$params[ $key ] = array();
				foreach( $matches[1] as $paramKey => $param ) {
					$params[ $key ][ $paramKey ] = $param;
					$message = str_replace( '%' . $param, '%' . $paramKey . '%', $message );
				}
				//$message = preg_replace( '/%([a-zA-z-_]+)/i', '%\$1%', $message );
				$requestData[ $key ] = $message;
			}

			$requestURL  = $url;
			foreach( $requestData as $message ) {
				$requestURL .= '&q=' . urlencode( $message );
			}
			$response    = @file_get_contents( $requestURL );
			$translation = json_decode( $response, true );
			if(
				is_array( $translation ) === false
				|| isset( $translation['data'] ) === false
			) {
				$this->cli->output( $response );
				$this->cli->error( 'Google tanslating failed' );
				exit();
			}

			$messageIndex = 0;
			foreach( $this->untranslatedMessages as $context => $messages ) {
				foreach( $messages as $message ) {
					if( $messageIndex >= $portion * $i && $messageIndex < $portion * ( $i + 1 ) ) {
						$key = $messageIndex - $portion * $i;
						if( isset( $translation['data']['translations'][ $key ]['translatedText'] ) ) {
							$text = urldecode( $translation['data']['translations'][ $key ]['translatedText'] );
							$text = str_replace(
								array( '&quot;', '&#039;', '% ' ),
								array( '"', '\'', '%', ' ' ),
								$text
							);

							// Google strips some spaces after arguments
							if(
								isset( $params[ $key ] )
								&& count( $params[ $key ] ) > 0
							) {
								foreach( $params[ $key ] as $paramKey => $param ) {
									//$text = str_replace( '%' . (string) $paramKey . '%', '%' . $param . ' ', $text );
									$text = str_replace( '%' . (string) $paramKey . '%', '%' . $param, $text );
								}
							}

							$this->untranslatedMessages[ $context ][ $message ] = $text;
						}
					}
					$messageIndex++;
				}
			}
			$i++;
		}
	}

	private function makeTranslationFile() {
		$this->cli->output(
			$this->cli->stylize( 'yellow', 'Making translation file...' )
		);

		$implementation = new DOMImplementation();
		$dtd = $implementation->createDocumentType( 'TS' );
		$dom = $implementation->createDocument( '', '', $dtd );
		$dom->formatOutput = true;
		$rootNode = $dom->createElement( 'TS' );
		$dom->appendChild( $rootNode );

		foreach( $this->translationInfo as $contextName => $contextMessages ) {
			$contextNode = $dom->createElement( 'context' );
			$rootNode->appendChild( $contextNode );

			$contextNameNode = $dom->createElement( 'name', $contextName );
			$contextNode->appendChild( $contextNameNode );

			foreach( $contextMessages as $message ) {
				$messageNode = $dom->createElement( 'message' );
				$contextNode->appendChild( $messageNode );

				$sourceNode = $dom->createElement( 'source' );
				$sourceNode->appendChild( $dom->createTextNode( $message ) );
				$messageNode->appendChild( $sourceNode );

				$translation     = ezpI18n::tr( $contextName, $message );
				$translationNode = $dom->createElement( 'translation' );

				if( $translation === $message ) {
					$translation = $this->untranslatedMessages[ $contextName ][ $message ];

					if( $translation === $message ) {
						$translationNode->setAttribute( 'type', 'unfinished' );
					} else {
						$translationNode->setAttribute( 'type', 'TranslatedByGoogleAPI' );
					}
				}

				$translationNode->appendChild( $dom->createTextNode( $translation ) );
				$messageNode->appendChild( $translationNode );
			}
		}
		$translationFileContent = $dom->saveXML();

		$file = $this->translationPath . '/translation.ts';
		$fh   = fopen( $file, 'w' );
		fwrite( $fh, $translationFileContent );
		fclose( $fh );
		chmod( $file, 0777 );

		$this->cli->output(
			$this->cli->stylize( 'green', 'Translation file "' . $file . '" saved' )
		);
	}
}
?>
