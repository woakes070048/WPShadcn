<?php

namespace Shadcn\BlockSettings\HoverSettings;

use Shadcn\Traits\SingletonTrait;

class Caller {
	use SingletonTrait;

	/**
	 * Track if frontend styles already enqueued.
	 *
	 * @var bool
	 */
	private $frontend_styles_enqueued = false;

	protected function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_settings_scripts' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_styles' ) ); //Enqueuing frontend styles in editor.
		add_filter( 'render_block_woocommerce/product-button', array( $this, 'apply_hover_settings_to_product_button_block' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'apply_hover_settings_to_blocks' ), 10, 3 );
	}

	private function is_block_support_text_color( $settings ) {
		return ! empty( $settings['supports']['color']['text'] ) || ! empty( $settings['supports']['color']['__experimentalDefaultControls']['text'] );
	}

	private function is_block_support_background_color( $settings ) {
		return ! empty( $settings['supports']['color']['background'] ) || ! empty( $settings['supports']['color']['__experimentalDefaultControls']['background'] );
	}

	private function is_block_support_border( $settings ) {
		return ! empty( $settings['supports']['__experimentalBorder'] );
	}

	private function is_block_need_overflow_hidden( $settings ) {
		return ! empty( $settings['supports']['__experimentalBorder'] ) && isset( $settings['name'] ) && in_array( $settings['name'], array( 'core/group', 'core/columns' ) );
	}

	public function enqueue_settings_scripts() {
		wp_enqueue_script( 'shadcn/hover-settings-controls', get_template_directory_uri() . '/inc/BlockSettings/HoverSettings/script.js', array( 'wp-edit-post' ), wp_get_theme()->get( 'Version' ), true );
		wp_enqueue_style( 'shadcn/hover-settings-controls', get_template_directory_uri() . '/inc/BlockSettings/HoverSettings/style.css', array(), wp_get_theme()->get( 'Version' ) );
	}

	public function enqueue_editor_styles() {
		$this->maybe_enqueue_frontend_styles( true );
	}

	/**
	 * Enqueue frontend styles only when needed.
	 */
	public function maybe_enqueue_frontend_styles( $admin_enqueue = false ) {

		if ( ! $admin_enqueue && is_admin() ) {
			return;
		}

		if ( $admin_enqueue && ! is_admin() ) {
			return;
		}

		if ( $this->frontend_styles_enqueued ) {
			return;
		}
		$this->frontend_styles_enqueued = true;
		wp_enqueue_style( 'shadcn/hover-settings-style', get_template_directory_uri() . '/inc/BlockSettings/HoverSettings/style.css', array(), wp_get_theme()->get( 'Version' ) );
	}

	public function apply_hover_settings_to_product_button_block( $html, $block ) {

		$tag = new \WP_HTML_Tag_Processor( $html );

		if ( $tag->next_tag() ) {
			// Get hover-related attributes
			if ( $tag->get_attribute( 'data-hover-background-color' ) ) {
				$hover_background_color = $tag->get_attribute( 'data-hover-background-color' );
			}
			if ( $tag->get_attribute( 'data-hover-text-color' ) ) {
				$hover_text_color = $tag->get_attribute( 'data-hover-text-color' );
			}

			if ( $tag->get_attribute( 'data-hover-border' ) ) {
				$hover_border = json_decode( $tag->get_attribute( 'data-hover-border' ), true );
			}

			$escape_styles = array();

			if ( ! empty( $block['attrs']['style']['hoverBackgroundColor'] ) ) {
				$hover_background_color = $block['attrs']['style']['hoverBackgroundColor'];
			}
			if ( ! empty( $block['attrs']['style']['hoverTextColor'] ) ) {
				$hover_text_color = $block['attrs']['style']['hoverTextColor'];
			}

			// Add hover border styles
			if ( isset( $hover_border ) ) {
				foreach ( array( 'top', 'bottom', 'left', 'right' ) as $aspect ) {
					if ( isset( $hover_border[ $aspect ]['color'] ) ) {
						$escape_styles[] = '--hover-border-' . $aspect . '-c:' . $hover_border[ $aspect ]['color'];
					}
					if ( isset( $hover_border[ $aspect ]['width'] ) ) {
						$escape_styles[] = '--hover-border-' . $aspect . '-w:' . $hover_border[ $aspect ]['width'];
					}
					if ( isset( $hover_border[ $aspect ]['style'] ) ) {
						$escape_styles[] = '--hover-border-' . $aspect . '-s:' . $hover_border[ $aspect ]['style'];
					}
				}
				if ( isset( $hover_border['color'] ) ) {
					$escape_styles[] = '--hover-border-c:' . $hover_border['color'];
				}
				if ( isset( $hover_border['width'] ) ) {
					$escape_styles[] = '--hover-border-w:' . $hover_border['width'];
				}
				if ( isset( $hover_border['style'] ) ) {
					$escape_styles[] = '--hover-border-s:' . $hover_border['style'];
				}
			}

			// Add hover background color
			if ( isset( $hover_background_color ) ) {
				$escape_styles[] = '--hover-background-color:' . $hover_background_color;
			}

			// Add hover text color
			if ( isset( $hover_text_color ) ) {
				$escape_styles[] = '--hover-color:' . $hover_text_color;
			}

			// Update the style attribute
			$existing_style = $tag->get_attribute( 'style' );
			$updated_style  = '';

			if ( empty( $existing_style ) ) {
				$existing_style = '';
			} elseif ( ! str_ends_with( $existing_style, ';' ) ) {
				$existing_style .= ';';
			}

			$updated_style  = $existing_style;
			$updated_style .= implode( ';', $escape_styles );

			if ( ! empty( $escape_styles ) ) {
				$updated_style .= ';';
				$this->maybe_enqueue_frontend_styles();
			}
			
			$tag->set_attribute( 'style', $updated_style );

			$html = $tag->get_updated_html();
		}

		return $html;
	}

	public function apply_hover_settings_to_blocks( $html, $_, $block ) {
		$tag = new \WP_HTML_Tag_Processor( $html );

		if ( $tag->next_tag() ) {
			// Get hover-related attributes
			if ( $this->is_block_support_background_color( (array) $block->block_type ) && ! empty( $block->attributes['style']['hoverBackgroundColor'] ) ) {
				$hover_background_color = $block->attributes['style']['hoverBackgroundColor'];
			}
			if ( $this->is_block_support_text_color( (array) $block->block_type ) && ! empty( $block->attributes['style']['hoverTextColor'] ) ) {
				$hover_text_color = $block->attributes['style']['hoverTextColor'];
			}

			if ( $this->is_block_support_border( (array) $block->block_type ) && ! empty( $block->attributes['style']['hoverBorder'] ) ) {
				$hover_border = $block->attributes['style']['hoverBorder'];
			}

			$overflow = null;

			if ( $this->is_block_need_overflow_hidden( (array) $block->block_type ) && ! empty( $block->attributes['style']['border']['radius'] ) ) {
				$overflow = 'hidden';
			}

			$escape_styles = array();

			if ( 'hidden' === $overflow ) {
				$escape_styles[] = 'overflow: hidden';
			}

			if ( isset( $hover_border ) ) {
				foreach ( array( 'top', 'bottom', 'left', 'right' ) as $aspect ) {
					if ( isset( $hover_border[ $aspect ]['color'] ) ) {
						$escape_styles[] = '--hover-border-' . $aspect . '-c:' . $hover_border[ $aspect ]['color'];
					}
					if ( isset( $hover_border[ $aspect ]['width'] ) ) {
						$escape_styles[] = '--hover-border-' . $aspect . '-w:' . $hover_border[ $aspect ]['width'];
					}
					if ( isset( $hover_border[ $aspect ]['style'] ) ) {
						$escape_styles[] = '--hover-border-' . $aspect . '-s:' . $hover_border[ $aspect ]['style'];
					}
				}

				if ( isset( $hover_border['color'] ) ) {
					$escape_styles[] = '--hover-border-c:' . $hover_border['color'];
				}
				if ( isset( $hover_border['width'] ) ) {
					$escape_styles[] = '--hover-border-w:' . $hover_border['width'];
				}
				if ( isset( $hover_border['style'] ) ) {
					$escape_styles[] = '--hover-border-s:' . $hover_border['style'];
				}
			}

			// Add hover background color
			if ( isset( $hover_background_color ) ) {
				$escape_styles[] = '--hover-background-color:' . $hover_background_color;
			}

			// Add hover text color
			if ( isset( $hover_text_color ) ) {
				$escape_styles[] = '--hover-color:' . $hover_text_color;
			}

			// Update the style attribute
			$existing_style = $tag->get_attribute( 'style' );
			$updated_style  = '';

			if ( empty( $existing_style ) ) {
				$existing_style = '';
			} elseif ( ! str_ends_with( $existing_style, ';' ) ) {
				$existing_style .= ';';
			}

			$updated_style  = $existing_style;
			$updated_style .= implode( ';', $escape_styles );

			if ( ! empty( $escape_styles ) ) {
				$updated_style .= ';';
				$this->maybe_enqueue_frontend_styles();
			}

			$tag->set_attribute( 'style', $updated_style );

			$html = $tag->get_updated_html();
		}

		return $html;
	}
}

Caller::get_instance();
