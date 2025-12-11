<?php
/**
 * JSX to Gutenberg Blocks Converter
 *
 * @package WP_Shadcn
 * @since 1.0.0
 */

namespace Shadcn\Admin;

use Shadcn\Traits\SingletonTrait;

class JSXConverter {
	use SingletonTrait;

	/**
	 * Theme.json colors mapping
	 */
	private $colors = [
		'background', 'foreground', 'card', 'card-foreground',
		'popover', 'popover-foreground', 'primary', 'primary-foreground',
		'secondary', 'secondary-foreground', 'muted', 'muted-foreground',
		'accent', 'accent-foreground', 'destructive', 'destructive-foreground',
		'code', 'code-foreground'
	];

	/**
	 * Theme.json spacing mapping (1-10)
	 */
	private $spacing_map = [
		'1' => '0.25rem',
		'2' => '0.5rem',
		'3' => '0.75rem',
		'4' => '1rem',
		'5' => '1.25rem',
		'6' => '1.5rem',
		'7' => '2rem',
		'8' => '2.5rem',
		'9' => '3rem',
		'10' => '4rem',
	];

	/**
	 * Font size mapping
	 */
	private $font_sizes = [
		'xs', 'sm', 'base', 'lg', 'xl', '2-xl', '3-xl', '4-xl',
		'5-xl', '6-xl', '7-xl', 'fluid-4-xl', 'fluid-5-xl', 'fluid-7-xl'
	];

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_convert_jsx_to_gutenberg', array( $this, 'ajax_convert' ) );
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu() {
		add_theme_page(
			__( 'JSX to Gutenberg Converter', 'shadcn' ),
			__( 'JSX Converter', 'shadcn' ),
			'edit_theme_options',
			'jsx-converter',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'appearance_page_jsx-converter' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'jsx-converter-admin',
			get_template_directory_uri() . '/assets/css/jsx-converter-admin.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'jsx-converter-admin',
			get_template_directory_uri() . '/assets/js/jsx-converter-admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'jsx-converter-admin',
			'jsxConverterData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'jsx_converter_nonce' ),
			)
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		?>
		<div class="wrap jsx-converter-wrap">
			<h1><?php esc_html_e( 'JSX to Gutenberg Blocks Converter', 'shadcn' ); ?></h1>
			
			<div class="jsx-converter-intro">
				<p><?php esc_html_e( 'Convert Shadcn React sections to WordPress Gutenberg blocks. Paste your JSX code below and click "Convert" to generate the Gutenberg block markup.', 'shadcn' ); ?></p>
				<p><strong><?php esc_html_e( 'Features:', 'shadcn' ); ?></strong></p>
				<ul>
					<li><?php esc_html_e( 'Automatically maps Tailwind/Shadcn classes to theme.json variables', 'shadcn' ); ?></li>
					<li><?php esc_html_e( 'Converts React components to native Gutenberg blocks', 'shadcn' ); ?></li>
					<li><?php esc_html_e( 'Preserves colors, spacing, and typography from theme.json', 'shadcn' ); ?></li>
					<li><?php esc_html_e( 'Smart layout conversion (flex, grid, etc.)', 'shadcn' ); ?></li>
				</ul>
			</div>

			<div class="jsx-converter-container">
				<div class="jsx-converter-input-section">
					<div class="jsx-converter-header">
						<h2><?php esc_html_e( 'Input (JSX)', 'shadcn' ); ?></h2>
						<button type="button" class="button jsx-clear-input"><?php esc_html_e( 'Clear', 'shadcn' ); ?></button>
					</div>
					<textarea 
						id="jsx-input" 
						class="jsx-converter-textarea" 
						placeholder="<?php esc_attr_e( 'Paste your JSX code here...', 'shadcn' ); ?>"
					></textarea>
				</div>

				<div class="jsx-converter-output-section">
					<div class="jsx-converter-header">
						<h2><?php esc_html_e( 'Output (Gutenberg)', 'shadcn' ); ?></h2>
						<button type="button" class="button jsx-copy-output"><?php esc_html_e( 'Copy to Clipboard', 'shadcn' ); ?></button>
					</div>
					<textarea 
						id="jsx-output" 
						class="jsx-converter-textarea" 
						readonly
						placeholder="<?php esc_attr_e( 'Converted Gutenberg blocks will appear here...', 'shadcn' ); ?>"
					></textarea>
				</div>

				<div class="jsx-converter-actions">
					<button type="button" id="jsx-convert-btn" class="button button-primary button-hero">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Convert to Gutenberg', 'shadcn' ); ?>
					</button>
					<div class="jsx-converter-loading" style="display: none;">
						<span class="spinner is-active"></span>
						<span><?php esc_html_e( 'Converting...', 'shadcn' ); ?></span>
					</div>
				</div>
			</div>

			<div class="jsx-converter-footer">
				<p>
					<?php esc_html_e( 'Tip: You can save the output directly as a block pattern in the', 'shadcn' ); ?>
					<code>patterns/</code>
					<?php esc_html_e( 'directory for reuse.', 'shadcn' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for conversion
	 */
	public function ajax_convert() {
		check_ajax_referer( 'jsx_converter_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'shadcn' ) ) );
		}

		$jsx = isset( $_POST['jsx'] ) ? wp_unslash( $_POST['jsx'] ) : '';

		if ( empty( $jsx ) ) {
			wp_send_json_error( array( 'message' => __( 'No JSX provided', 'shadcn' ) ) );
		}

		try {
			$gutenberg = $this->convert_jsx_to_gutenberg( $jsx );
			wp_send_json_success( array( 'output' => $gutenberg ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Main conversion function
	 */
	private function convert_jsx_to_gutenberg( $jsx ) {
		// Clean up the JSX
		$jsx = trim( $jsx );

		// Parse the JSX structure
		$dom = $this->parse_jsx_to_dom( $jsx );

		// Convert DOM to Gutenberg blocks
		$gutenberg = $this->dom_to_gutenberg( $dom );

		return $gutenberg;
	}

	/**
	 * Parse JSX to a simple DOM structure
	 */
	private function parse_jsx_to_dom( $jsx ) {
		// Simple XML-like parsing
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;

		// Convert JSX attributes to XML-compatible format
		$jsx = $this->normalize_jsx_attributes( $jsx );

		// Wrap in root element if needed
		if ( ! preg_match( '/^<[^>]+>/', trim( $jsx ) ) ) {
			$jsx = '<root>' . $jsx . '</root>';
		}

		// Load the JSX as XML
		libxml_use_internal_errors( true );
		$loaded = $dom->loadXML( $jsx );
		libxml_clear_errors();

		if ( ! $loaded ) {
			// Fallback: try as HTML
			$dom->loadHTML( '<?xml encoding="UTF-8">' . $jsx, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		}

		return $dom;
	}

	/**
	 * Normalize JSX attributes to XML format
	 */
	private function normalize_jsx_attributes( $jsx ) {
		// Remove JSX comments first
		$jsx = preg_replace( '/\{\/\*.*?\*\/\}/s', '', $jsx );

		// Remove import statements
		$jsx = preg_replace( '/^import\s+.*?;\s*$/m', '', $jsx );

		// Remove interface/type declarations
		$jsx = preg_replace( '/^interface\s+\w+\s*\{[\s\S]*?\}\s*$/m', '', $jsx );

		// Extract default values from component props before processing
		$defaults = $this->extract_default_values( $jsx );

		// Remove const component declarations - extract just the JSX return
		if ( preg_match( '/return\s*\(\s*(<[\s\S]*>)\s*\)\s*;?\s*\}\s*;?/s', $jsx, $matches ) ) {
			$jsx = $matches[1];
		}

		// Replace JSX variables with extracted default values
		$jsx = $this->replace_jsx_variables( $jsx, $defaults );

		// Convert star ratings to text (★★★★★)
		// Match patterns like: {[...Array(5)].map(...<Star.../>...)}
		$jsx = preg_replace_callback(
			'/\{\s*\[\s*\.\.\.\s*Array\s*\(\s*(\d+)\s*\)\s*\]\s*\.map\s*\([^)]*\)\s*\}/s',
			function ( $matches ) {
				$count = intval( $matches[1] );
				return str_repeat( '★', $count );
			},
			$jsx
		);

		// Also handle simpler star patterns
		$jsx = preg_replace( '/<Star[^>]*\/>/s', '★', $jsx );

		// Convert rating display patterns like {reviews.rating?.toFixed(1)}
		$jsx = preg_replace( '/\{\s*reviews\.rating\?\s*\.toFixed\s*\(\s*1\s*\)\s*\}/', '5.0', $jsx );
		$jsx = preg_replace( '/\{\s*reviews\.count\s*\}/', '200', $jsx );

		// Expand avatar array maps - convert {reviews.avatars.map(...)} to multiple Avatar components
		// This pattern handles: {reviews.avatars.map((avatar, index) => (<Avatar...><AvatarImage .../></Avatar>))}
		$jsx = preg_replace_callback(
			'/\{\s*reviews\.avatars\.map\s*\(\s*\([^)]+\)\s*=>\s*\(\s*(<Avatar[^>]*>[\s\S]*?<\/Avatar>)\s*\)\s*\)\s*\}/s',
			function ( $matches ) {
				$avatar_template = $matches[1];
				// Default avatars (can be customized)
				$avatars = array(
					array( 'src' => 'https://deifkwefumgah.cloudfront.net/shadcnblocks/block/avatar-1.webp', 'alt' => 'Avatar 1' ),
					array( 'src' => 'https://deifkwefumgah.cloudfront.net/shadcnblocks/block/avatar-2.webp', 'alt' => 'Avatar 2' ),
					array( 'src' => 'https://deifkwefumgah.cloudfront.net/shadcnblocks/block/avatar-3.webp', 'alt' => 'Avatar 3' ),
					array( 'src' => 'https://deifkwefumgah.cloudfront.net/shadcnblocks/block/avatar-4.webp', 'alt' => 'Avatar 4' ),
					array( 'src' => 'https://deifkwefumgah.cloudfront.net/shadcnblocks/block/avatar-5.webp', 'alt' => 'Avatar 5' ),
				);

				$result = '';
				foreach ( $avatars as $avatar ) {
					$expanded = $avatar_template;
					// Replace {avatar.src} or avatar.src patterns
					$expanded = preg_replace( '/\{\s*avatar\.src\s*\}/', $avatar['src'], $expanded );
					$expanded = preg_replace( '/src=\{\s*avatar\.src\s*\}/', 'src="' . $avatar['src'] . '"', $expanded );
					// Replace {avatar.alt} or avatar.alt patterns
					$expanded = preg_replace( '/\{\s*avatar\.alt\s*\}/', $avatar['alt'], $expanded );
					$expanded = preg_replace( '/alt=\{\s*avatar\.alt\s*\}/', 'alt="' . $avatar['alt'] . '"', $expanded );
					// Remove key attribute
					$expanded = preg_replace( '/\s*key=\{\s*index\s*\}/', '', $expanded );
					$result .= $expanded;
				}
				return $result;
			},
			$jsx
		);

		// Normalize whitespace in opening tags (collapse multiline to single line)
		$jsx = preg_replace_callback( '/<(\w+)([^>]*?)>/s', function( $matches ) {
			$tag = $matches[1];
			$attrs = $matches[2];
			// Normalize whitespace in attributes
			$attrs = preg_replace( '/\s+/', ' ', $attrs );
			return '<' . $tag . $attrs . '>';
		}, $jsx );

		// Convert className to class
		$jsx = preg_replace( '/\bclassName\s*=/', 'class=', $jsx );

		// Convert aria-labelledby and other kebab-case attributes
		$jsx = preg_replace( '/\baria-labelledby\s*=/', 'arialabelledby=', $jsx );
		$jsx = preg_replace( '/\baria-label\s*=/', 'arialabel=', $jsx );

		// Remove self-closing component tags like <ArrowRight />, <ArrowDownRight />, etc.
		$jsx = preg_replace( '/<([A-Z][a-zA-Z]*)\s*\/>/', '', $jsx );

		// Remove component imports and declarations
		$jsx = preg_replace_callback( '/<([A-Z][a-zA-Z]*)[^>]*>.*?<\/\1>/s', function( $matches ) {
			return $this->convert_component_to_html( $matches[0] );
		}, $jsx );

		// Clean up any remaining unresolved JSX expressions
		$jsx = preg_replace( '/\{\s*`[^`]*`\s*\}/', '', $jsx );

		return $jsx;
	}

	/**
	 * Extract default values from component props
	 */
	private function extract_default_values( $jsx ) {
		$defaults = array();

		// Match the destructuring assignment pattern with defaults
		// e.g., heading = "Default heading text"
		if ( preg_match_all( '/(\w+)\s*=\s*"([^"]+)"/', $jsx, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$defaults[ $match[1] ] = $match[2];
			}
		}

		// Extract buttons object - handle multiline nested objects
		// Match from "buttons = {" to the closing "}" at same nesting level

		if ( preg_match( '/button\s*=\s*\{/s', $jsx, $start_match, PREG_OFFSET_CAPTURE ) ) {
			$start_pos = $start_match[0][1] + strlen( $start_match[0][0] );
			$button_content = $this->extract_balanced_braces( $jsx, $start_pos );

			// Extract primary button values
			if ( preg_match( '/text\s*:\s*"([^"]+)"/', $button_content, $text_match ) ) {
				$defaults['button.text'] = $text_match[1];
			}

			if ( preg_match( '/url\s*:\s*"([^"]+)"/', $button_content, $url_match ) ) {
				$defaults['button.url'] = $url_match[1];
			}
		}

		if ( preg_match( '/buttons\s*=\s*\{/s', $jsx, $start_match, PREG_OFFSET_CAPTURE ) ) {
			$start_pos = $start_match[0][1] + strlen( $start_match[0][0] );
			$buttons_content = $this->extract_balanced_braces( $jsx, $start_pos );

			// Extract primary button values
			if ( preg_match( '/primary\s*:\s*\{([^}]+)\}/s', $buttons_content, $primary_match ) ) {
				$primary_content = $primary_match[1];
				if ( preg_match( '/text\s*:\s*"([^"]+)"/', $primary_content, $text_match ) ) {
					$defaults['buttons.primary.text'] = $text_match[1];
				}
				if ( preg_match( '/url\s*:\s*"([^"]+)"/', $primary_content, $url_match ) ) {
					$defaults['buttons.primary.url'] = $url_match[1];
				}
			}

			// Extract secondary button values
			if ( preg_match( '/secondary\s*:\s*\{([^}]+)\}/s', $buttons_content, $secondary_match ) ) {
				$secondary_content = $secondary_match[1];
				if ( preg_match( '/text\s*:\s*"([^"]+)"/', $secondary_content, $text_match ) ) {
					$defaults['buttons.secondary.text'] = $text_match[1];
				}
				if ( preg_match( '/url\s*:\s*"([^"]+)"/', $secondary_content, $url_match ) ) {
					$defaults['buttons.secondary.url'] = $url_match[1];
				}
			}
		}

		// Extract image object
		if ( preg_match( '/image\s*=\s*\{/s', $jsx, $start_match, PREG_OFFSET_CAPTURE ) ) {
			$start_pos = $start_match[0][1] + strlen( $start_match[0][0] );
			$image_content = $this->extract_balanced_braces( $jsx, $start_pos );

			if ( preg_match( '/src\s*:\s*"([^"]+)"/', $image_content, $src_match ) ) {
				$defaults['image.src'] = $src_match[1];
			}
			if ( preg_match( '/alt\s*:\s*"([^"]+)"/', $image_content, $alt_match ) ) {
				$defaults['image.alt'] = $alt_match[1];
			}
		}

		// Match reviews defaults
		if ( preg_match( '/reviews\s*=\s*\{/s', $jsx, $start_match, PREG_OFFSET_CAPTURE ) ) {
			$start_pos = $start_match[0][1] + strlen( $start_match[0][0] );
			$reviews_content = $this->extract_balanced_braces( $jsx, $start_pos );

			if ( preg_match( '/count\s*:\s*(\d+)/', $reviews_content, $count_match ) ) {
				$defaults['reviews.count'] = $count_match[1];
			}
			if ( preg_match( '/rating\s*:\s*([\d.]+)/', $reviews_content, $rating_match ) ) {
				$defaults['reviews.rating'] = $rating_match[1];
			}
		}

		return $defaults;
	}

	/**
	 * Extract content within balanced braces starting at position
	 */
	private function extract_balanced_braces( $str, $start_pos ) {
		$depth = 1;
		$content = '';
		$len = strlen( $str );

		for ( $i = $start_pos; $i < $len && $depth > 0; $i++ ) {
			$char = $str[ $i ];
			if ( $char === '{' ) {
				$depth++;
			} elseif ( $char === '}' ) {
				$depth--;
				if ( $depth === 0 ) {
					break;
				}
			}
			$content .= $char;
		}

		return $content;
	}

	/**
	 * Replace JSX variables with their default values
	 */
	private function replace_jsx_variables( $jsx, $defaults ) {
		// Replace simple variables like {heading}, {description}, {badge}
		foreach ( $defaults as $key => $value ) {
			// // Skip nested keys (contain dots) - handled separately
			// if ( strpos( $key, '.' ) !== false ) {
			// 	continue;
			// }
			// Handle simple variables: {heading}
			$jsx = preg_replace( '/\{\s*' . preg_quote( $key, '/' ) . '\s*\}/', $value, $jsx );
		}
		return $jsx;

		// Replace nested button text patterns like {buttons.primary.text}
		$jsx = preg_replace_callback(
			'/\{\s*buttons\.(\w+)\.(\w+)\s*\}/',
			function ( $matches ) use ( $defaults ) {
				$key = 'buttons.' . $matches[1] . '.' . $matches[2];
				return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
			},
			$jsx
		);

		// Replace button URL in href: href={buttons.primary.url}
		$jsx = preg_replace_callback(
			'/href=\{\s*buttons\.(\w+)\.url\s*\}/',
			function ( $matches ) use ( $defaults ) {
				$key = 'buttons.' . $matches[1] . '.url';
				$url = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '#';
				return 'href="' . $url . '"';
			},
			$jsx
		);

		// Replace image src: src={image.src}
		$jsx = preg_replace_callback(
			'/href=\{\s*button\.url\s*\}/',
			function ( $matches ) use ( $defaults ) {
				$url = isset( $defaults['button.url'] ) ? $defaults['button.url'] : '#';
				return 'href="' . $url . '"';
			},
			$jsx
		);

		// Replace image src: src={image.src}
		$jsx = preg_replace_callback(
			'/src=\{\s*image\.src\s*\}/',
			function ( $matches ) use ( $defaults ) {
				$src = isset( $defaults['image.src'] ) ? $defaults['image.src'] : '';
				return 'src="' . $src . '"';
			},
			$jsx
		);

		// Replace image alt: alt={image.alt}
		$jsx = preg_replace_callback(
			'/alt=\{\s*image\.alt\s*\}/',
			function ( $matches ) use ( $defaults ) {
				$alt = isset( $defaults['image.alt'] ) ? $defaults['image.alt'] : '';
				return 'alt="' . $alt . '"';
			},
			$jsx
		);

		// Replace reviews patterns
		$jsx = preg_replace_callback(
			'/\{\s*reviews\.(\w+)\??\s*(?:\.toFixed\s*\(\s*\d+\s*\))?\s*\}/',
			function ( $matches ) use ( $defaults ) {
				$key = 'reviews.' . $matches[1];
				return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
			},
			$jsx
		);

		// Handle template literal patterns like: from {reviews.count}+ reviews
		$jsx = preg_replace_callback(
			'/from\s*\{\s*reviews\.count\s*\}\+?\s*reviews/',
			function ( $matches ) use ( $defaults ) {
				$count = isset( $defaults['reviews.count'] ) ? $defaults['reviews.count'] : '200';
				return 'from ' . $count . '+ reviews';
			},
			$jsx
		);

		// Clean up any remaining unresolved simple variables (but not nested ones with dots)
		$jsx = preg_replace( '/\{\s*\w+\s*\}/', '', $jsx );

		return $jsx;
	}

	/**
	 * Convert React component to HTML element
	 */
	private function convert_component_to_html( $component ) {
		// Handle Button component
		if ( preg_match( '/<Button([^>]*)>(.*?)<\/Button>/s', $component, $matches ) ) {
			$attributes = $matches[1];
			$content = $matches[2];

			// Extract variant attribute
			$variant = '';
			if ( preg_match( '/variant=["\']([^"\']+)["\']/', $attributes, $variant_match ) ) {
				$variant = $variant_match[1];
				// Remove variant from attributes (it's not a valid HTML attribute)
				$attributes = preg_replace( '/\s*variant=["\'][^"\']*["\']/', '', $attributes );
			}

			// Extract size attribute
			$size = '';
			if ( preg_match( '/size=["\']([^"\']+)["\']/', $attributes, $size_match ) ) {
				$size = $size_match[1];
				$attributes = preg_replace( '/\s*size=["\'][^"\']*["\']/', '', $attributes );
			}

			// Add variant as data attribute for later processing
			if ( ! empty( $variant ) ) {
				$attributes .= ' data-variant="' . $variant . '"';
			}
			if ( ! empty( $size ) ) {
				$attributes .= ' data-size="' . $size . '"';
			}

			// Remove any remaining component tags inside
			$content = preg_replace( '/<[A-Z][a-zA-Z]*\s*\/>/', '', $content );
			return '<button' . $attributes . '>' . trim( $content ) . '</button>';
		}

		// Handle Tagline component
		if ( preg_match( '/<Tagline([^>]*)>(.*?)<\/Tagline>/s', $component, $matches ) ) {
			return '<p' . $matches[1] . ' data-tagline="true">' . $matches[2] . '</p>';
		}

		// Handle Badge component
		if ( preg_match( '/<Badge([^>]*)>(.*?)<\/Badge>/s', $component, $matches ) ) {
			$attributes = $matches[1];
			$content = $matches[2];

			// Extract variant attribute
			$variant = 'default';
			if ( preg_match( '/variant=["\']([^"\']+)["\']/', $attributes, $variant_match ) ) {
				$variant = $variant_match[1];
				$attributes = preg_replace( '/\s*variant=["\'][^"\']*["\']/', '', $attributes );
			}

			// Remove any icon components inside
			$content = preg_replace( '/<[A-Z][a-zA-Z]*\s*[^>]*\/>/', '', $content );

			return '<p' . $attributes . ' data-badge="true" data-variant="' . $variant . '">' . trim( $content ) . '</p>';
		}

		// Handle Avatar component with nested AvatarImage
		if ( preg_match( '/<Avatar([^>]*)>(.*?)<\/Avatar>/s', $component, $matches ) ) {
			$avatar_attributes = $matches[1];
			$inner_content = $matches[2];

			// Extract className from Avatar
			$avatar_class = '';
			if ( preg_match( '/class=["\']([^"\']+)["\']/', $avatar_attributes, $class_match ) ) {
				$avatar_class = $class_match[1];
			}

			// Extract src and alt from AvatarImage inside
			$src = '';
			$alt = '';
			if ( preg_match( '/<AvatarImage[^>]*src=["\']([^"\']+)["\'][^>]*>/s', $inner_content, $src_match ) ) {
				$src = $src_match[1];
			}
			if ( preg_match( '/<AvatarImage[^>]*alt=["\']([^"\']+)["\'][^>]*>/s', $inner_content, $alt_match ) ) {
				$alt = $alt_match[1];
			}

			// Build the img tag with avatar data attribute
			$class_attr = ! empty( $avatar_class ) ? ' class="' . $avatar_class . '"' : '';
			return '<img src="' . $src . '" alt="' . $alt . '"' . $class_attr . ' data-avatar="true"/>';
		}

		// Handle standalone AvatarImage component
		if ( preg_match( '/<AvatarImage([^>]*)(\/?)>/s', $component, $matches ) ) {
			$attributes = $matches[1];

			$src = '';
			$alt = '';
			$className = '';

			if ( preg_match( '/src=["\']([^"\']+)["\']/', $attributes, $src_match ) ) {
				$src = $src_match[1];
			}
			if ( preg_match( '/alt=["\']([^"\']+)["\']/', $attributes, $alt_match ) ) {
				$alt = $alt_match[1];
			}
			if ( preg_match( '/class=["\']([^"\']+)["\']/', $attributes, $class_match ) ) {
				$className = ' class="' . $class_match[1] . '"';
			}

			return '<img src="' . $src . '" alt="' . $alt . '"' . $className . ' data-avatar="true"/>';
		}

		// Handle Image component (Next.js style)
		if ( preg_match( '/<Image([^>]*)(\/?)>/s', $component, $matches ) ) {
			$attributes = $matches[1];
			$is_self_closing = ! empty( $matches[2] );

			// Extract src and alt from attributes
			$src = '';
			$alt = '';
			$className = '';

			if ( preg_match( '/src=["\']([^"\']+)["\']/', $attributes, $src_match ) ) {
				$src = $src_match[1];
			}
			if ( preg_match( '/alt=["\']([^"\']+)["\']/', $attributes, $alt_match ) ) {
				$alt = $alt_match[1];
			}
			if ( preg_match( '/class=["\']([^"\']+)["\']/', $attributes, $class_match ) ) {
				$className = ' class="' . $class_match[1] . '"';
			}

			return '<img src="' . $src . '" alt="' . $alt . '"' . $className . '/>';
		}

		// Handle AspectRatio component - extract inner content
		if ( preg_match( '/<AspectRatio([^>]*)>(.*?)<\/AspectRatio>/s', $component, $matches ) ) {
			// Return just the inner content (usually an Image)
			return $matches[2];
		}

		return $component;
	}

	/**
	 * Convert DOM to Gutenberg blocks
	 */
	private function dom_to_gutenberg( $dom ) {
		$output = '';

		// Get root element
		$root = $dom->documentElement;

		// Check if root is a wrapper element we added, or the actual JSX root
		if ( $root->tagName === 'root' ) {
			// If we wrapped it in a <root> element, convert its children
			foreach ( $root->childNodes as $node ) {
				if ( $node->nodeType === XML_ELEMENT_NODE ) {
					$output .= $this->convert_element_to_block( $node );
				}
			}
		} else {
			// Convert the root element itself (it's the actual JSX root like <section>)
			$output = $this->convert_element_to_block( $root );
		}

		return $output;
	}

	/**
	 * Convert a DOM element to Gutenberg block
	 */
	private function convert_element_to_block( $element, $depth = 0 ) {
		$tag = strtolower( $element->tagName );
		$classes = $element->hasAttribute( 'class' ) ? $element->getAttribute( 'class' ) : '';
		$output = '';
		$indent = str_repeat( "\t", $depth );

		// Determine block type based on tag and classes
		if ( $tag === 'section' ) {
			$output .= $this->create_group_block( $element, $depth );
		} elseif ( $tag === 'div' ) {
			// Check if this div contains multiple buttons (button container)
			if ( $this->is_buttons_container( $element ) ) {
				$output .= $this->create_buttons_group_block( $element, $depth );
			} else {
				$output .= $this->create_group_block( $element, $depth );
			}
		} elseif ( $tag === 'span' ) {
			// Handle span elements - check if it's an avatar container or flex container
			if ( $this->is_avatar_container( $element ) ) {
				$output .= $this->create_avatar_stack_block( $element, $depth );
			} else {
				$output .= $this->create_group_block( $element, $depth );
			}
		} elseif ( in_array( $tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] ) ) {
			$output .= $this->create_heading_block( $element, $depth );
		} elseif ( $tag === 'p' ) {
			$output .= $this->create_paragraph_block( $element, $depth );
		} elseif ( $tag === 'button' ) {
			$output .= $this->create_button_block( $element, $depth );
		} elseif ( $tag === 'img' ) {
			$output .= $this->create_image_block( $element, $depth );
		} elseif ( $tag === 'a' ) {
			// Handle anchor tags - extract content and pass to children
			foreach ( $element->childNodes as $child ) {
				if ( $child->nodeType === XML_ELEMENT_NODE ) {
					$output .= $this->convert_element_to_block( $child, $depth );
				}
			}
		} else {
			// Fallback: process children
			foreach ( $element->childNodes as $child ) {
				if ( $child->nodeType === XML_ELEMENT_NODE ) {
					$output .= $this->convert_element_to_block( $child, $depth );
				}
			}
		}

		return $output;
	}

	/**
	 * Check if element is an avatar container (contains multiple avatar images)
	 */
	private function is_avatar_container( $element ) {
		$classes = $element->hasAttribute( 'class' ) ? $element->getAttribute( 'class' ) : '';

		// Check for common avatar container patterns
		if ( strpos( $classes, '-space-x' ) !== false || strpos( $classes, 'inline-flex' ) !== false ) {
			// Count img children with data-avatar
			$avatar_count = 0;
			foreach ( $element->childNodes as $child ) {
				if ( $child->nodeType === XML_ELEMENT_NODE ) {
					$child_tag = strtolower( $child->tagName );
					if ( $child_tag === 'img' && $child->hasAttribute( 'data-avatar' ) ) {
						$avatar_count++;
					}
				}
			}
			return $avatar_count >= 2;
		}

		return false;
	}

	/**
	 * Create avatar stack block (group with overlapping images)
	 */
	private function create_avatar_stack_block( $element, $depth = 0 ) {
		$indent = str_repeat( "\t", $depth );
		$classes = $element->getAttribute( 'class' );

		// Parse classes for overlap margin
		$overlap_margin = '-16px'; // Default overlap
		if ( preg_match( '/-space-x-(\d+)/', $classes, $matches ) ) {
			$overlap_margin = '-' . ( intval( $matches[1] ) * 4 ) . 'px';
		}

		// Build group block for avatar container
		$output = $indent . '<!-- wp:group {"className":"mx-auto mt-10 w-fit sm:flex-row","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->' . "\n";
		$output .= $indent . '<div class="wp-block-group mx-auto mt-10 w-fit sm:flex-row">';

		$is_first = true;
		foreach ( $element->childNodes as $child ) {
			if ( $child->nodeType === XML_ELEMENT_NODE ) {
				$child_tag = strtolower( $child->tagName );

				if ( $child_tag === 'img' ) {
					$src = $child->hasAttribute( 'src' ) ? $child->getAttribute( 'src' ) : '';
					$alt = $child->hasAttribute( 'alt' ) ? $child->getAttribute( 'alt' ) : '';
					$child_classes = $child->hasAttribute( 'class' ) ? $child->getAttribute( 'class' ) : '';

					// Extract size from classes (size-12 = 48px, size-14 = 56px)
					$size = '54px'; // Default avatar size
					if ( preg_match( '/size-(\d+)/', $child_classes, $size_match ) ) {
						$size = ( intval( $size_match[1] ) * 4 ) . 'px';
					}

					// Build image block attributes
					$img_attrs = array(
						'"width":"' . $size . '"',
						'"height":"' . $size . '"',
						'"scale":"cover"',
						'"sizeSlug":"large"',
						'"style":{"border":{"radius":"100px","width":"1px"}' . ( ! $is_first ? ',"spacing":{"margin":{"left":"' . $overlap_margin . '"}}' : '' ) . '}',
						'"borderColor":"muted"',
					);

					$margin_style = ! $is_first ? ' style="margin-left:' . $overlap_margin . '"' : '';

					$output .= '<!-- wp:image {' . implode( ',', $img_attrs ) . '} -->' . "\n";
					$output .= '<figure class="wp-block-image size-large is-resized has-custom-border"' . $margin_style . '>';
					$output .= '<img src="' . esc_attr( $src ) . '" alt="' . esc_attr( $alt ) . '" class="has-border-color has-muted-border-color" style="border-width:1px;border-radius:100px;object-fit:cover;width:' . $size . ';height:' . $size . '"/>';
					$output .= '</figure>' . "\n";
					$output .= '<!-- /wp:image -->' . "\n\n";

					$is_first = false;
				}
			}
		}

		$output .= '</div>' . "\n";
		$output .= $indent . '<!-- /wp:group -->' . "\n\n";

		return $output;
	}

	/**
	 * Check if element is a container for multiple buttons
	 */
	private function is_buttons_container( $element ) {
		$button_count = 0;
		
		// Count direct button children
		foreach ( $element->childNodes as $child ) {
			if ( $child->nodeType === XML_ELEMENT_NODE ) {
				$child_tag = strtolower( $child->tagName );
				if ( $child_tag === 'button' ) {
					$button_count++;
				}
			}
		}
		
		// If has 2 or more buttons, it's a buttons container
		return $button_count >= 2;
	}

	/**
	 * Create a Gutenberg group block
	 */
	private function create_group_block( $element, $depth = 0 ) {
		$indent = str_repeat( "\t", $depth );
		$classes = $element->getAttribute( 'class' );
		$tag = strtolower( $element->tagName );

		// Parse classes and extract styling information
		$block_attrs = $this->parse_classes_to_attributes( $classes );
		
		// Check if this is a grid layout that should use columns
		$is_grid = isset( $block_attrs['layout']['type'] ) && $block_attrs['layout']['type'] === 'grid';
		
		if ( $is_grid && ! empty( $block_attrs['layout']['columnCount'] ) ) {
			return $this->create_columns_block( $element, $depth, $block_attrs );
		}
		
		// Add metadata
		$metadata = array();
		if ( $element->hasAttribute( 'arialabelledby' ) ) {
			$metadata['name'] = 'Section';
		}

		// Build block comment attributes
		$attrs = array();
		if ( ! empty( $metadata ) ) {
			$attrs[] = '"metadata":' . wp_json_encode( $metadata );
		}
		if ( ! empty( $block_attrs['className'] ) ) {
			$attrs[] = '"className":"' . esc_attr( $block_attrs['className'] ) . '"';
		}
		if ( ! empty( $block_attrs['style'] ) ) {
			$attrs[] = '"style":' . wp_json_encode( $block_attrs['style'] );
		}
		if ( ! empty( $block_attrs['backgroundColor'] ) ) {
			$attrs[] = '"backgroundColor":"' . esc_attr( $block_attrs['backgroundColor'] ) . '"';
		}
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$attrs[] = '"textColor":"' . esc_attr( $block_attrs['textColor'] ) . '"';
		}
		if ( ! empty( $block_attrs['layout'] ) && ! $is_grid ) {
			$attrs[] = '"layout":' . wp_json_encode( $block_attrs['layout'] );
		}

		$attrs_string = ! empty( $attrs ) ? ' {' . implode( ',', $attrs ) . '}' : '';

		// Build CSS classes for the HTML element
		$html_classes = array( 'wp-block-group' );
		
		// Add custom classes
		if ( ! empty( $block_attrs['className'] ) ) {
			$html_classes[] = esc_attr( $block_attrs['className'] );
		}
		
		// Add color classes for Gutenberg
		if ( ! empty( $block_attrs['backgroundColor'] ) ) {
			$html_classes[] = 'has-' . $block_attrs['backgroundColor'] . '-background-color';
			$html_classes[] = 'has-background';
		}
		
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$html_classes[] = 'has-' . $block_attrs['textColor'] . '-color';
			$html_classes[] = 'has-text-color';
		}

		// Start group block
		$output = $indent . '<!-- wp:group' . $attrs_string . ' -->' . "\n";
		$output .= $indent . '<div class="' . implode( ' ', $html_classes ) . '"';
		
		// Add inline styles
		if ( ! empty( $block_attrs['inlineStyle'] ) ) {
			$output .= ' style="' . esc_attr( $block_attrs['inlineStyle'] ) . '"';
		}
		
		$output .= '>' . "\n";

		// Process children
		foreach ( $element->childNodes as $child ) {
			if ( $child->nodeType === XML_ELEMENT_NODE ) {
				$output .= $this->convert_element_to_block( $child, $depth + 1 );
			}
		}

		$output .= $indent . '</div>' . "\n";
		$output .= $indent . '<!-- /wp:group -->' . "\n\n";

		return $output;
	}

	/**
	 * Create a Gutenberg columns block for grid layouts
	 */
	private function create_columns_block( $element, $depth, $block_attrs ) {
		$indent = str_repeat( "\t", $depth );
		$column_count = $block_attrs['layout']['columnCount'];
		
		// Build columns block attributes
		$attrs = array();
		$attrs[] = '"className":"' . ( ! empty( $block_attrs['className'] ) ? esc_attr( $block_attrs['className'] ) : '' ) . '"';
		
		if ( ! empty( $block_attrs['style'] ) ) {
			$attrs[] = '"style":' . wp_json_encode( $block_attrs['style'] );
		}
		
		$attrs_string = '{' . implode( ',', $attrs ) . '}';
		
		// Build CSS classes for columns
		$html_classes = array( 'wp-block-columns' );
		if ( ! empty( $block_attrs['className'] ) ) {
			$html_classes[] = esc_attr( $block_attrs['className'] );
		}
		
		// Start columns block
		$output = $indent . '<!-- wp:columns ' . $attrs_string . ' -->' . "\n";
		$output .= $indent . '<div class="' . implode( ' ', $html_classes ) . '">' . "\n";
		
		// Process each child as a column
		foreach ( $element->childNodes as $child ) {
			if ( $child->nodeType === XML_ELEMENT_NODE ) {
				$output .= $indent . "\t" . '<!-- wp:column -->' . "\n";
				$output .= $indent . "\t" . '<div class="wp-block-column">' . "\n";
				
				// Convert child content
				$child_classes = $child->hasAttribute( 'class' ) ? $child->getAttribute( 'class' ) : '';
				$child_attrs = $this->parse_classes_to_attributes( $child_classes );
				
				// Add child as group if it has styling
				if ( ! empty( $child_attrs['backgroundColor'] ) || ! empty( $child_attrs['style'] ) ) {
					$child_block_attrs = array();
					if ( ! empty( $child_attrs['className'] ) ) {
						$child_block_attrs[] = '"className":"' . esc_attr( $child_attrs['className'] ) . '"';
					}
					if ( ! empty( $child_attrs['backgroundColor'] ) ) {
						$child_block_attrs[] = '"backgroundColor":"' . esc_attr( $child_attrs['backgroundColor'] ) . '"';
					}
					if ( ! empty( $child_attrs['textColor'] ) ) {
						$child_block_attrs[] = '"textColor":"' . esc_attr( $child_attrs['textColor'] ) . '"';
					}
					if ( ! empty( $child_attrs['style'] ) ) {
						$child_block_attrs[] = '"style":' . wp_json_encode( $child_attrs['style'] );
					}
					
					$child_attrs_string = ! empty( $child_block_attrs ) ? ' {' . implode( ',', $child_block_attrs ) . '}' : '';
					
					// Build CSS classes for child group
					$child_html_classes = array( 'wp-block-group' );
					if ( ! empty( $child_attrs['className'] ) ) {
						$child_html_classes[] = esc_attr( $child_attrs['className'] );
					}
					if ( ! empty( $child_attrs['backgroundColor'] ) ) {
						$child_html_classes[] = 'has-' . $child_attrs['backgroundColor'] . '-background-color';
						$child_html_classes[] = 'has-background';
					}
					if ( ! empty( $child_attrs['textColor'] ) ) {
						$child_html_classes[] = 'has-' . $child_attrs['textColor'] . '-color';
						$child_html_classes[] = 'has-text-color';
					}
					
					$output .= $indent . "\t\t" . '<!-- wp:group' . $child_attrs_string . ' -->' . "\n";
					$output .= $indent . "\t\t" . '<div class="' . implode( ' ', $child_html_classes ) . '">' . "\n";
					
					// Process grandchildren
					foreach ( $child->childNodes as $grandchild ) {
						if ( $grandchild->nodeType === XML_ELEMENT_NODE ) {
							$output .= $this->convert_element_to_block( $grandchild, $depth + 3 );
						}
					}
					
					$output .= $indent . "\t\t" . '</div>' . "\n";
					$output .= $indent . "\t\t" . '<!-- /wp:group -->' . "\n";
				} else {
					// Process children directly
					foreach ( $child->childNodes as $grandchild ) {
						if ( $grandchild->nodeType === XML_ELEMENT_NODE ) {
							$output .= $this->convert_element_to_block( $grandchild, $depth + 2 );
						}
					}
				}
				
				$output .= $indent . "\t" . '</div>' . "\n";
				$output .= $indent . "\t" . '<!-- /wp:column -->' . "\n\n";
			}
		}
		
		$output .= $indent . '</div>' . "\n";
		$output .= $indent . '<!-- /wp:columns -->' . "\n\n";
		
		return $output;
	}

	/**
	 * Create a heading block
	 */
	private function create_heading_block( $element, $depth = 0 ) {
		$indent = str_repeat( "\t", $depth );
		$tag = strtolower( $element->tagName );
		$level = (int) substr( $tag, 1 );
		$classes = $element->getAttribute( 'class' );
		$content = $this->get_element_text_content( $element );

		$block_attrs = $this->parse_classes_to_attributes( $classes );

		// Build attributes
		$attrs = array();
		$attrs[] = '"level":' . $level;
		
		if ( ! empty( $block_attrs['metadata'] ) ) {
			$attrs[] = '"metadata":' . wp_json_encode( $block_attrs['metadata'] );
		}
		if ( ! empty( $block_attrs['className'] ) ) {
			$attrs[] = '"className":"' . esc_attr( $block_attrs['className'] ) . '"';
		}
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$attrs[] = '"textColor":"' . esc_attr( $block_attrs['textColor'] ) . '"';
		}

		$attrs_string = '{' . implode( ',', $attrs ) . '}';

		// Build CSS classes for heading
		$html_classes = array( 'wp-block-heading' );
		
		if ( ! empty( $block_attrs['className'] ) ) {
			$html_classes[] = esc_attr( $block_attrs['className'] );
		}
		
		// Add color classes
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$html_classes[] = 'has-' . $block_attrs['textColor'] . '-color';
			$html_classes[] = 'has-text-color';
		}

		$output = $indent . '<!-- wp:heading ' . $attrs_string . ' -->' . "\n";
		$output .= $indent . '<' . $tag . ' class="' . implode( ' ', $html_classes ) . '">';
		$output .= esc_html( $content );
		$output .= '</' . $tag . '>' . "\n";
		$output .= $indent . '<!-- /wp:heading -->' . "\n\n";

		return $output;
	}

	/**
	 * Create a paragraph block
	 */
	private function create_paragraph_block( $element, $depth = 0 ) {
		$indent = str_repeat( "\t", $depth );
		$classes = $element->getAttribute( 'class' );
		$content = $this->get_element_text_content( $element );
		$is_tagline = $element->hasAttribute( 'data-tagline' );
		$is_badge = $element->hasAttribute( 'data-badge' );
		$badge_variant = $element->hasAttribute( 'data-variant' ) ? $element->getAttribute( 'data-variant' ) : 'default';

		$block_attrs = $this->parse_classes_to_attributes( $classes );

		// Build attributes
		$attrs = array();

		// Handle Badge component
		if ( $is_badge ) {
			$style_class = 'is-style-badge';
			if ( $badge_variant === 'outline' ) {
				$style_class = 'is-style-badge-outline';
			}
			if ( empty( $block_attrs['className'] ) ) {
				$block_attrs['className'] = $style_class;
			} else {
				$block_attrs['className'] .= ' ' . $style_class;
			}
			if ( empty( $block_attrs['fontSize'] ) ) {
				$block_attrs['fontSize'] = 'sm';
			}
		}

		if ( $is_tagline ) {
			$attrs[] = '"metadata":{"name":"Subtitle"}';
			if ( empty( $block_attrs['className'] ) ) {
				$block_attrs['className'] = 'tagline';
			} else {
				$block_attrs['className'] .= ' tagline';
			}
			if ( empty( $block_attrs['fontSize'] ) ) {
				$block_attrs['fontSize'] = 'sm';
			}
		}

		if ( ! empty( $block_attrs['className'] ) ) {
			$attrs[] = '"className":"' . esc_attr( $block_attrs['className'] ) . '"';
		}
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$attrs[] = '"textColor":"' . esc_attr( $block_attrs['textColor'] ) . '"';
		}
		if ( ! empty( $block_attrs['fontSize'] ) ) {
			$attrs[] = '"fontSize":"' . esc_attr( $block_attrs['fontSize'] ) . '"';
		}
		if ( ! empty( $block_attrs['style'] ) ) {
			$attrs[] = '"style":' . wp_json_encode( $block_attrs['style'] );
		}

		$attrs_string = ! empty( $attrs ) ? ' {' . implode( ',', $attrs ) . '}' : '';

		// Build CSS classes for paragraph
		$html_classes = array();
		
		if ( ! empty( $block_attrs['className'] ) ) {
			$html_classes[] = esc_attr( $block_attrs['className'] );
		}
		
		// Add color classes
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$html_classes[] = 'has-' . $block_attrs['textColor'] . '-color';
			$html_classes[] = 'has-text-color';
		}
		
		// Add font size class
		if ( ! empty( $block_attrs['fontSize'] ) ) {
			$html_classes[] = 'has-' . $block_attrs['fontSize'] . '-font-size';
		}

		$output = $indent . '<!-- wp:paragraph' . $attrs_string . ' -->' . "\n";
		$output .= $indent . '<p class="' . implode( ' ', $html_classes ) . '">';
		$output .= esc_html( $content );
		$output .= '</p>' . "\n";
		$output .= $indent . '<!-- /wp:paragraph -->' . "\n\n";

		return $output;
	}

	/**
	 * Create a button block (single button with wrapper)
	 */
	private function create_button_block( $element, $depth = 0 ) {
		$indent = str_repeat( "\t", $depth );
		$classes = $element->getAttribute( 'class' );
		$content = $this->get_element_text_content( $element );

		$url = '';
		foreach ( $element->childNodes as $node ) {
			if ( $node->tagName === 'a' ) {
				$url = $node->getAttribute( 'href' );
			}
		}
		
		// Get variant from data attribute
		$variant = $element->hasAttribute( 'data-variant' ) ? $element->getAttribute( 'data-variant' ) : '';

		$block_attrs = $this->parse_classes_to_attributes( $classes );

		// Buttons need a wrapper
		$output = $indent . '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n";
		$output .= $indent . '<div class="wp-block-buttons">' . "\n";

		$size = 'md';
		// Build button attributes
		$attrs = array( '"size":"' . esc_attr( $size ) . '"' );
		
		// Add variant as className for block styles
		if ( ! empty( $variant ) && $this->is_valid_button_variant( $variant ) ) {
			$attrs[] = '"className":"is-style-' . esc_attr( $variant ) . '"';
		}
		
		if ( ! empty( $block_attrs['backgroundColor'] ) ) {
			$attrs[] = '"backgroundColor":"' . esc_attr( $block_attrs['backgroundColor'] ) . '"';
		}
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$attrs[] = '"textColor":"' . esc_attr( $block_attrs['textColor'] ) . '"';
		}

		$attrs_string = ! empty( $attrs ) ? ' {' . implode( ',', $attrs ) . '}' : '';

		// Build CSS classes for button div
		$button_div_classes = array( 'wp-block-button', 'is-size-' . esc_attr( $size ) );
		if ( ! empty( $variant ) && $this->is_valid_button_variant( $variant ) ) {
			$button_div_classes[] = 'is-style-' . esc_attr( $variant );
		}
		
		// Build CSS classes for button link
		$link_classes = array( 'wp-block-button__link' );
		
		// Add color classes to the link element
		if ( ! empty( $block_attrs['backgroundColor'] ) ) {
			$link_classes[] = 'has-' . $block_attrs['backgroundColor'] . '-background-color';
			$link_classes[] = 'has-background';
		}
		
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$link_classes[] = 'has-' . $block_attrs['textColor'] . '-color';
			$link_classes[] = 'has-text-color';
		}
		
		$link_classes[] = 'wp-element-button';

		$output .= $indent . "\t" . '<!-- wp:button' . $attrs_string . ' -->' . "\n";
		$output .= $indent . "\t" . '<div class="' . implode( ' ', $button_div_classes ) . '">';
		$output .= '<a class="' . implode( ' ', $link_classes ) . '" href="' . esc_attr( $url ) . '">';
		$output .= esc_html( $content );
		$output .= '</a></div>' . "\n";
		$output .= $indent . "\t" . '<!-- /wp:button -->' . "\n";

		$output .= $indent . '</div>' . "\n";
		$output .= $indent . '<!-- /wp:buttons -->' . "\n\n";

		return $output;
	}

	/**
	 * Create a buttons group block (for multiple buttons in a container)
	 */
	private function create_buttons_group_block( $element, $depth = 0 ) {
		$indent = str_repeat( "\t", $depth );
		$classes = $element->getAttribute( 'class' );
		
		// Parse classes to determine layout
		$block_attrs = $this->parse_classes_to_attributes( $classes );
		
		// Determine justification from flex classes
		$justify = 'left';
		if ( strpos( $classes, 'justify-center' ) !== false || strpos( $classes, 'items-center' ) !== false ) {
			$justify = 'center';
		} elseif ( strpos( $classes, 'justify-end' ) !== false ) {
			$justify = 'right';
		}
		
		// Start buttons block
		$output = $indent . '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"' . $justify . '"}} -->' . "\n";
		$output .= $indent . '<div class="wp-block-buttons">' . "\n";
		
		// Process each button child
		foreach ( $element->childNodes as $child ) {
			if ( $child->nodeType === XML_ELEMENT_NODE ) {
				$child_tag = strtolower( $child->tagName );
				
				if ( $child_tag === 'button' ) {
					$button_classes = $child->getAttribute( 'class' );
					$button_content = $this->get_element_text_content( $child );
					$button_attrs = $this->parse_classes_to_attributes( $button_classes );
					
					// Get variant from data attribute
					$button_variant = $child->hasAttribute( 'data-variant' ) ? $child->getAttribute( 'data-variant' ) : '';
					
					// Build button attributes
					$attrs = array();
					
					// Add variant as className for block styles
					if ( ! empty( $button_variant ) && $this->is_valid_button_variant( $button_variant ) ) {
						$attrs[] = '"className":"is-style-' . esc_attr( $button_variant ) . '"';
					}
					
					if ( ! empty( $button_attrs['backgroundColor'] ) ) {
						$attrs[] = '"backgroundColor":"' . esc_attr( $button_attrs['backgroundColor'] ) . '"';
					}
					if ( ! empty( $button_attrs['textColor'] ) ) {
						$attrs[] = '"textColor":"' . esc_attr( $button_attrs['textColor'] ) . '"';
					}
					
					$attrs_string = ! empty( $attrs ) ? ' {' . implode( ',', $attrs ) . '}' : '';
					
					// Build CSS classes for button div
					$button_div_classes = array( 'wp-block-button' );
					if ( ! empty( $button_variant ) && $this->is_valid_button_variant( $button_variant ) ) {
						$button_div_classes[] = 'is-style-' . esc_attr( $button_variant );
					}
					
					// Build CSS classes for button link
					$link_classes = array( 'wp-block-button__link' );
					
					if ( ! empty( $button_attrs['backgroundColor'] ) ) {
						$link_classes[] = 'has-' . $button_attrs['backgroundColor'] . '-background-color';
						$link_classes[] = 'has-background';
					}
					
					if ( ! empty( $button_attrs['textColor'] ) ) {
						$link_classes[] = 'has-' . $button_attrs['textColor'] . '-color';
						$link_classes[] = 'has-text-color';
					}
					
					$link_classes[] = 'wp-element-button';
					
					// Output button
					$output .= $indent . "\t" . '<!-- wp:button' . $attrs_string . ' -->' . "\n";
					$output .= $indent . "\t" . '<div class="' . implode( ' ', $button_div_classes ) . '">';
					$output .= '<a class="' . implode( ' ', $link_classes ) . '">';
					$output .= esc_html( $button_content );
					$output .= '</a></div>' . "\n";
					$output .= $indent . "\t" . '<!-- /wp:button -->' . "\n\n";
				}
			}
		}
		
		$output .= $indent . '</div>' . "\n";
		$output .= $indent . '<!-- /wp:buttons -->' . "\n\n";
		
		return $output;
	}

	/**
	 * Create an image block
	 */
	private function create_image_block( $element, $depth = 0 ) {
		$indent = str_repeat( "\t", $depth );
		$classes = $element->getAttribute( 'class' );
		
		// Get image attributes
		$src = $element->hasAttribute( 'src' ) ? $element->getAttribute( 'src' ) : '';
		$alt = $element->hasAttribute( 'alt' ) ? $element->getAttribute( 'alt' ) : '';
		
		$block_attrs = $this->parse_classes_to_attributes( $classes );
		
		// Build block attributes
		$attrs = array();
		$attrs[] = '"sizeSlug":"large"';
		
		if ( ! empty( $block_attrs['className'] ) ) {
			$attrs[] = '"className":"' . esc_attr( $block_attrs['className'] ) . '"';
		}
		
		$attrs_string = '{' . implode( ',', $attrs ) . '}';
		
		// Build CSS classes for image
		$html_classes = array();
		if ( ! empty( $block_attrs['className'] ) ) {
			$html_classes[] = esc_attr( $block_attrs['className'] );
		}
		
		$class_string = ! empty( $html_classes ) ? ' class="' . implode( ' ', $html_classes ) . '"' : '';
		
		$output = $indent . '<!-- wp:image ' . $attrs_string . ' -->' . "\n";
		$output .= $indent . '<figure class="wp-block-image size-large">';
		$output .= '<img' . $class_string . ' src="' . esc_attr( $src ) . '" alt="' . esc_attr( $alt ) . '"/>';
		$output .= '</figure>' . "\n";
		$output .= $indent . '<!-- /wp:image -->' . "\n\n";
		
		return $output;
	}

	/**
	 * Parse Tailwind/Shadcn classes to Gutenberg attributes
	 */
	private function parse_classes_to_attributes( $classes ) {
		$attrs = array();
		$class_array = explode( ' ', $classes );
		$remaining_classes = array();
		$is_grid = false;
		$grid_cols = null;

		foreach ( $class_array as $class ) {
			$class = trim( $class );
			if ( empty( $class ) ) {
				continue;
			}

			// Background colors
			if ( preg_match( '/^bg-(.+)$/', $class, $matches ) ) {
				$color = $matches[1];
				if ( in_array( $color, $this->colors ) ) {
					$attrs['backgroundColor'] = $color;
					continue;
				}
			}

			// Text colors
			if ( preg_match( '/^text-(.+)$/', $class, $matches ) ) {
				$color = $matches[1];
				// Handle opacity variants like text-primary-foreground/80
				if ( strpos( $color, '/' ) !== false ) {
					list( $base_color, $opacity ) = explode( '/', $color );
					if ( in_array( $base_color, $this->colors ) ) {
						$attrs['textColor'] = 'muted-foreground'; // Fallback for opacity variants
						if ( ! isset( $attrs['style']['elements'] ) ) {
							$attrs['style']['elements'] = array();
						}
						$attrs['style']['elements']['link'] = array(
							'color' => array(
								'text' => 'var:preset|color|' . $base_color
							)
						);
						continue;
					}
				} elseif ( in_array( $color, $this->colors ) ) {
					$attrs['textColor'] = $color;
					continue;
				}
			}

			// Border colors
			if ( preg_match( '/^border-(.+)$/', $class, $matches ) ) {
				$color = $matches[1];
				if ( in_array( $color, $this->colors ) ) {
					if ( ! isset( $attrs['style']['border'] ) ) {
						$attrs['style']['border'] = array();
					}
					$attrs['style']['border']['color'] = 'var:preset|color|' . $color;
					$attrs['style']['border']['width'] = '1px';
					$attrs['style']['border']['style'] = 'solid';
					continue;
				}
			}

			// Border radius
			if ( preg_match( '/^rounded(-(.+))?$/', $class, $matches ) ) {
				$radius_map = array(
					'' => '0.5rem',
					'sm' => '0.375rem',
					'md' => '0.5rem',
					'lg' => '0.75rem',
					'xl' => '1rem',
					'2xl' => '1.5rem',
					'full' => '9999px',
				);
				$size = isset( $matches[2] ) ? $matches[2] : '';
				if ( isset( $radius_map[ $size ] ) ) {
					if ( ! isset( $attrs['style']['border'] ) ) {
						$attrs['style']['border'] = array();
					}
					$attrs['style']['border']['radius'] = $radius_map[ $size ];
					continue;
				}
			}

			// Grid detection
			if ( $class === 'grid' ) {
				$is_grid = true;
				continue;
			}

			// Grid columns
			if ( preg_match( '/^grid-cols-(\d+)$/', $class, $matches ) ) {
				$grid_cols = (int) $matches[1];
				continue;
			}

			// Responsive grid columns (md:grid-cols-3)
			if ( preg_match( '/^(sm|md|lg|xl):grid-cols-(\d+)$/', $class, $matches ) ) {
				// Keep for responsive behavior
				$remaining_classes[] = $class;
				continue;
			}

			// Padding classes - all directions (p-4)
			if ( preg_match( '/^p-(\d+)$/', $class, $matches ) ) {
				$spacing = $matches[1];
				if ( ! isset( $attrs['style']['spacing']['padding'] ) ) {
					$attrs['style']['spacing']['padding'] = array();
				}
				$attrs['style']['spacing']['padding'] = array(
					'top' => 'var:preset|spacing|' . $spacing,
					'right' => 'var:preset|spacing|' . $spacing,
					'bottom' => 'var:preset|spacing|' . $spacing,
					'left' => 'var:preset|spacing|' . $spacing,
				);
				continue;
			}

			// Padding classes - axis (py-4, px-4)
			if ( preg_match( '/^p([xy])-(\d+)$/', $class, $matches ) ) {
				$axis = $matches[1];
				$spacing = $matches[2];
				if ( ! isset( $attrs['style']['spacing']['padding'] ) ) {
					$attrs['style']['spacing']['padding'] = array();
				}
				if ( $axis === 'y' ) {
					$attrs['style']['spacing']['padding']['top'] = 'var:preset|spacing|' . $spacing;
					$attrs['style']['spacing']['padding']['bottom'] = 'var:preset|spacing|' . $spacing;
				} else { // x
					$attrs['style']['spacing']['padding']['left'] = 'var:preset|spacing|' . $spacing;
					$attrs['style']['spacing']['padding']['right'] = 'var:preset|spacing|' . $spacing;
				}
				continue;
			}

			// Individual padding (pt-4, pb-4, pl-4, pr-4)
			if ( preg_match( '/^p([tblr])-(\d+)$/', $class, $matches ) ) {
				$side_map = array( 't' => 'top', 'b' => 'bottom', 'l' => 'left', 'r' => 'right' );
				$side = $side_map[ $matches[1] ];
				$spacing = $matches[2];
				if ( ! isset( $attrs['style']['spacing']['padding'] ) ) {
					$attrs['style']['spacing']['padding'] = array();
				}
				$attrs['style']['spacing']['padding'][ $side ] = 'var:preset|spacing|' . $spacing;
				continue;
			}

			// Container and layout classes
			if ( in_array( $class, [ 'container', 'mx-auto', 'container-padding-x', 'section-padding-y', 'section-title-gap-lg', 'heading-lg', 'tagline' ] ) ) {
				$remaining_classes[] = $class;
				continue;
			}

			// Flex and layout
			if ( strpos( $class, 'flex' ) === 0 || strpos( $class, 'items-' ) === 0 || strpos( $class, 'justify-' ) === 0 ) {
				// Will be handled by layout attribute
				if ( ! isset( $attrs['layout'] ) ) {
					$attrs['layout'] = array( 'type' => 'constrained' );
				}
				continue;
			}

			// Max width
			if ( preg_match( '/^max-w-(.+)$/', $class, $matches ) ) {
				$width_map = array(
					'xl' => '576px',
					'2xl' => '672px',
					'3xl' => '768px',
					'4xl' => '896px',
					'5xl' => '1024px',
					'6xl' => '1152px',
					'7xl' => '1280px',
				);
				$width = $matches[1];
				if ( isset( $width_map[ $width ] ) ) {
					if ( ! isset( $attrs['layout'] ) ) {
						$attrs['layout'] = array();
					}
					$attrs['layout']['contentSize'] = $width_map[ $width ];
				}
				continue;
			}

			// Gap (for flex/grid)
			if ( preg_match( '/^gap-(\d+)$/', $class, $matches ) ) {
				$spacing = $matches[1];
				if ( ! isset( $attrs['style']['spacing'] ) ) {
					$attrs['style']['spacing'] = array();
				}
				$attrs['style']['spacing']['blockGap'] = 'var:preset|spacing|' . $spacing;
				continue;
			}

			// Text alignment
			if ( preg_match( '/^text-(left|center|right)$/', $class, $matches ) ) {
				$remaining_classes[] = 'has-text-align-' . $matches[1];
				continue;
			}

			// Font weight
			if ( preg_match( '/^font-(normal|medium|semibold|bold)$/', $class, $matches ) ) {
				if ( ! isset( $attrs['style']['typography'] ) ) {
					$attrs['style']['typography'] = array();
				}
				$weight_map = array(
					'normal' => '400',
					'medium' => '500',
					'semibold' => '600',
					'bold' => '700',
				);
				$attrs['style']['typography']['fontWeight'] = $weight_map[ $matches[1] ];
				continue;
			}

			// Font size
			if ( preg_match( '/^text-(xs|sm|base|lg|xl|2xl|3xl|4xl)$/', $class, $matches ) ) {
				$size = $matches[1];
				if ( in_array( $size, $this->font_sizes ) || in_array( str_replace( 'xl', '-xl', $size ), $this->font_sizes ) ) {
					$attrs['fontSize'] = str_replace( 'xl', '-xl', $size );
					continue;
				}
			}

			// Size utilities (size-12 = 48px, size-14 = 56px, etc.)
			if ( preg_match( '/^size-(\d+)$/', $class, $matches ) ) {
				$size_px = intval( $matches[1] ) * 4 . 'px';
				if ( ! isset( $attrs['style']['dimensions'] ) ) {
					$attrs['style']['dimensions'] = array();
				}
				$attrs['style']['dimensions']['width'] = $size_px;
				$attrs['style']['dimensions']['height'] = $size_px;
				continue;
			}

			// Negative space classes for overlap effect (-space-x-4)
			if ( preg_match( '/^-space-x-(\d+)$/', $class, $matches ) ) {
				$margin = '-' . ( intval( $matches[1] ) * 4 ) . 'px';
				$attrs['overlapMargin'] = $margin;
				$remaining_classes[] = $class; // Keep for reference
				continue;
			}

			// Margin classes - all directions (m-4)
			if ( preg_match( '/^m-(\d+)$/', $class, $matches ) ) {
				$spacing = $matches[1];
				if ( ! isset( $attrs['style']['spacing']['margin'] ) ) {
					$attrs['style']['spacing']['margin'] = array();
				}
				$attrs['style']['spacing']['margin'] = array(
					'top' => 'var:preset|spacing|' . $spacing,
					'right' => 'var:preset|spacing|' . $spacing,
					'bottom' => 'var:preset|spacing|' . $spacing,
					'left' => 'var:preset|spacing|' . $spacing,
				);
				continue;
			}

			// Margin classes - axis and individual (my-6, mx-4, mb-8, etc.)
			if ( preg_match( '/^m([tbylrx])-(\d+)$/', $class, $matches ) ) {
				$side_map = array(
					't' => array( 'top' ),
					'b' => array( 'bottom' ),
					'l' => array( 'left' ),
					'r' => array( 'right' ),
					'y' => array( 'top', 'bottom' ),
					'x' => array( 'left', 'right' ),
				);
				$sides = $side_map[ $matches[1] ];
				$spacing = $matches[2];
				if ( ! isset( $attrs['style']['spacing']['margin'] ) ) {
					$attrs['style']['spacing']['margin'] = array();
				}
				foreach ( $sides as $side ) {
					$attrs['style']['spacing']['margin'][ $side ] = 'var:preset|spacing|' . $spacing;
				}
				continue;
			}

			// All responsive classes (sm:, md:, lg:, xl:, 2xl:) - preserve as-is
			if ( preg_match( '/^(sm|md|lg|xl|2xl):/', $class ) ) {
				$remaining_classes[] = $class;
				continue;
			}

			// Text utility classes to preserve
			if ( in_array( $class, array( 'text-pretty', 'text-balance', 'text-wrap', 'truncate', 'whitespace-nowrap' ) ) ) {
				$remaining_classes[] = $class;
				continue;
			}

			// Keep other classes
			$remaining_classes[] = $class;
		}

		// Handle grid layout
		if ( $is_grid && $grid_cols ) {
			$attrs['layout'] = array(
				'type' => 'grid',
				'columnCount' => $grid_cols,
			);
		}

		// Add remaining classes
		if ( ! empty( $remaining_classes ) ) {
			$attrs['className'] = implode( ' ', $remaining_classes );
		}

		// Build inline styles for spacing
		$inline_styles = array();
		foreach ( $class_array as $class ) {
			// Section padding
			if ( $class === 'section-padding-y' ) {
				$inline_styles[] = 'padding-top:var(--wp--preset--spacing--9)';
				$inline_styles[] = 'padding-bottom:var(--wp--preset--spacing--9)';
				if ( ! isset( $attrs['style']['spacing']['padding'] ) ) {
					$attrs['style']['spacing']['padding'] = array(
						'top' => 'var:preset|spacing|9',
						'bottom' => 'var:preset|spacing|9',
					);
				}
			}
		}

		if ( ! empty( $inline_styles ) ) {
			$attrs['inlineStyle'] = implode( ';', $inline_styles );
		}

		return $attrs;
	}

	/**
	 * Get text content from element
	 */
	private function get_element_text_content( $element ) {
		$content = '';
		foreach ( $element->childNodes as $node ) {
			if ( $node->nodeType === XML_TEXT_NODE || ( $node->nodeType === XML_ELEMENT_NODE && $node->tagName === 'a' ) ) {
				$content .= ' ' . $node->nodeValue;
			}
		}
		
		// Normalize whitespace: collapse multiple spaces/newlines into single space
		$content = preg_replace( '/\s+/', ' ', $content );
		
		// Trim leading and trailing whitespace
		$content = trim( $content );
		
		return $content;
	}

	/**
	 * Check if button variant is valid (has corresponding block style)
	 */
	private function is_valid_button_variant( $variant ) {
		$valid_variants = array(
			'ghost',        // transparent background
			'outline',      // border with transparent background
			'secondary',    // secondary color
			'destructive',  // destructive/error color
		);
		
		return in_array( $variant, $valid_variants, true );
	}
}

JSXConverter::get_instance();
