<?php
/**
 * Plugin Name: Mihdan: AMP Syntax Highlighter
 * GitHub Plugin URI: https://github.com/mihdan/mihdan-amp-syntax-highlighting
 * Version 1.0.0
 *
 * @link https://portalzine.de/dev/html5/google-amp/make-syntax-highlighting-work-in-amp-wp-using-geshi/amp/
 */

define( 'MIHDAN_AMP_PATH', plugin_dir_path( __FILE__ ) );
define( 'MIHDAN_AMP_DIR', plugin_dir_url( __FILE__ ) );

require_once ( MIHDAN_AMP_PATH . 'vendor/geshi/geshi.php' ) ;

function mihdan_amp_s_post_template_css( AMP_Post_Template $amp ) {

	$languages = $amp->get( 'languages' );

	// Если есть блоки с кодом внутри поста
	if ( ! empty( $languages ) && is_array( $languages ) && count( $languages ) ) {

		foreach ( $languages as $language ) {

			// Выводим CSS для подсветки нужного языка
			echo ( new GeSHi( '', $language ) )->get_stylesheet();
		}

		?>
		.code-container {
			overflow-x: scroll;
			background: #fafbfc url(<?php echo MIHDAN_AMP_DIR; ?>assets/images/pre-bg.png);
			position: relative;
			padding-left: 16px;
			padding-right: 16px;
			border-left: 5px solid #558abb;
			margin-bottom: 16px;
			-webkit-overflow-scrolling: touch;
			white-space: pre-wrap;
			white-space: -moz-pre-wrap;
			white-space: -pre-wrap;
			white-space: -o-pre-wrap;
			word-wrap: break-word;
		}

		p > code {
			font-family: Lekton, Monaco, Menlo, Consolas, 'Andale Mono', Courier, 'Courier New', 'DejaVu Sans Mono', 'Bitstream Vera Sans Mono', 'Liberation Mono', monospace;
			background-color: #f7f7f9;
			padding: 1px 5px;
			white-space: nowrap;
			border: 1px solid #e1e1e8;
			-webkit-border-radius: 2px;
			-moz-border-radius: 2px;
			border-radius: 2px;
			/*box-shadow: 0 1px 0 #C7C4C1;*/
			font-size: 12px;
			text-shadow: 1px 1px 2px #fff;
			color: #c25;
		}
		<?php
	}
}
add_action( 'amp_post_template_css', 'mihdan_amp_s_post_template_css' );

add_filter( 'no_texturize_shortcodes', 'shortcodes_to_exempt_from_wptexturize' );
function shortcodes_to_exempt_from_wptexturize( $shortcodes ) {
	$shortcodes[] = 'php';
	return $shortcodes;
}

function mihdan_amp_syntax_highlighting( $data ) {

//	global $this; print_r( $this);

	$dom = AMP_DOM_Utils::get_dom_from_content($data['post_amp_content']);
	$pre = $dom->getElementsByTagName('pre');
	$pre_length = $pre->length;

	if ( $pre_length ) {

		$languages = array();

		for ( $i = $pre_length - 1; $i >= 0; $i-- ) {
			$node = $pre->item( $i );

			$node_attributes = AMP_DOM_Utils::get_node_attributes_as_assoc_array( $node );

			if ( ! empty( $node_attributes['class'] ) ) {
				$classes = $node_attributes['class'];
				if ( strpos( $classes, 'brush: ' )  !== false ) {
					$classes  = explode( ';', $classes );
					$language = str_replace( 'brush: ', '', $classes[0] );

					$languages[] = $language;

					$wrapper = AMP_DOM_Utils::create_node( $dom, 'div', [
						'class' => 'code-container'
					]);

					$highlight = new GeSHi( trim( $node->textContent ) , $language );
					// Отключить подсветку в тегах,
					// так как в АМР нельзя юзать inline-style
					$highlight->enable_classes();
					$highlight->set_header_type( GESHI_HEADER_PRE);
					//$highlight->enable_line_numbers( GESHI_NORMAL_LINE_NUMBERS );

					$code = $highlight->parse_code();
					$code = str_replace( '&nbsp;', '&#32;', $code );

					$fragment = $dom->createDocumentFragment();
					$fragment->appendXML( $code );
					$wrapper->appendChild($fragment);


					$node->parentNode->replaceChild($wrapper, $node);

				}
			}
		}

		$data['post_amp_content'] = AMP_DOM_Utils::get_content_from_dom( $dom );
		$data['languages'] = array_unique( $languages );

		return $data;
	}

	return $data;

}
add_filter( 'amp_post_template_data', 'mihdan_amp_syntax_highlighting' );

// eof;
