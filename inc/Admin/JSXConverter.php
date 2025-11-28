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

		// Remove self-closing component tags like <ArrowRight />
		$jsx = preg_replace( '/<([A-Z][a-zA-Z]*)\s*\/>/', '', $jsx );

		// Remove component imports and declarations
		$jsx = preg_replace_callback( '/<([A-Z][a-zA-Z]*)[^>]*>.*?<\/\1>/s', function( $matches ) {
			return $this->convert_component_to_html( $matches[0] );
		}, $jsx );

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
			// Remove any remaining component tags inside
			$content = preg_replace( '/<[A-Z][a-zA-Z]*\s*\/>/', '', $content );
			return '<button' . $attributes . '>' . trim( $content ) . '</button>';
		}

		// Handle Tagline component
		if ( preg_match( '/<Tagline([^>]*)>(.*?)<\/Tagline>/s', $component, $matches ) ) {
			return '<p' . $matches[1] . ' data-tagline="true">' . $matches[2] . '</p>';
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
			$output .= $this->create_group_block( $element, $depth );
		} elseif ( in_array( $tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] ) ) {
			$output .= $this->create_heading_block( $element, $depth );
		} elseif ( $tag === 'p' ) {
			$output .= $this->create_paragraph_block( $element, $depth );
		} elseif ( $tag === 'button' ) {
			$output .= $this->create_button_block( $element, $depth );
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

		$block_attrs = $this->parse_classes_to_attributes( $classes );

		// Build attributes
		$attrs = array();
		
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
	 * Create a button block
	 */
	private function create_button_block( $element, $depth = 0 ) {
		$indent = str_repeat( "\t", $depth );
		$classes = $element->getAttribute( 'class' );
		$content = $this->get_element_text_content( $element );

		$block_attrs = $this->parse_classes_to_attributes( $classes );

		// Buttons need a wrapper
		$output = $indent . '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n";
		$output .= $indent . '<div class="wp-block-buttons">' . "\n";

		// Build button attributes
		$attrs = array();
		if ( ! empty( $block_attrs['backgroundColor'] ) ) {
			$attrs[] = '"backgroundColor":"' . esc_attr( $block_attrs['backgroundColor'] ) . '"';
		}
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$attrs[] = '"textColor":"' . esc_attr( $block_attrs['textColor'] ) . '"';
		}

		$attrs_string = ! empty( $attrs ) ? ' {' . implode( ',', $attrs ) . '}' : '';

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
		$output .= $indent . "\t" . '<div class="wp-block-button">';
		$output .= '<a class="' . implode( ' ', $link_classes ) . '">';
		$output .= esc_html( $content );
		$output .= '</a></div>' . "\n";
		$output .= $indent . "\t" . '<!-- /wp:button -->' . "\n";

		$output .= $indent . '</div>' . "\n";
		$output .= $indent . '<!-- /wp:buttons -->' . "\n\n";

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

			// Padding classes
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

			// Individual padding
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
			if ( $node->nodeType === XML_TEXT_NODE ) {
				$content .= ' ' . $node->nodeValue;
			}
		}
		
		// Normalize whitespace: collapse multiple spaces/newlines into single space
		$content = preg_replace( '/\s+/', ' ', $content );
		
		// Trim leading and trailing whitespace
		$content = trim( $content );
		
		return $content;
	}
}

JSXConverter::get_instance();
