/**
 * JSX to Gutenberg Converter - Admin JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		const $input = $('#jsx-input');
		const $output = $('#jsx-output');
		const $convertBtn = $('#jsx-convert-btn');
		const $loading = $('.jsx-converter-loading');
		const $clearBtn = $('.jsx-clear-input');
		const $copyBtn = $('.jsx-copy-output');

		/**
		 * Convert JSX to Gutenberg
		 */
		$convertBtn.on('click', function() {
			const jsx = $input.val().trim();

			if (!jsx) {
				showMessage('Please enter some JSX code to convert.', 'error');
				return;
			}

			// Show loading state
			$convertBtn.prop('disabled', true);
			$loading.show();
			$output.val('');

			// Make AJAX request
			$.ajax({
				url: jsxConverterData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'convert_jsx_to_gutenberg',
					nonce: jsxConverterData.nonce,
					jsx: jsx
				},
				success: function(response) {
					if (response.success) {
						$output.val(response.data.output);
						showMessage('Conversion successful! Your Gutenberg blocks are ready.', 'success');
						
						// Auto-scroll to output
						$('html, body').animate({
							scrollTop: $output.offset().top - 100
						}, 500);
					} else {
						showMessage(response.data.message || 'Conversion failed. Please check your JSX syntax.', 'error');
					}
				},
				error: function(xhr, status, error) {
					console.error('Conversion error:', error);
					showMessage('An error occurred during conversion. Please try again.', 'error');
				},
				complete: function() {
					$convertBtn.prop('disabled', false);
					$loading.hide();
				}
			});
		});

		/**
		 * Clear input
		 */
		$clearBtn.on('click', function() {
			if ($input.val().trim()) {
				if (confirm('Are you sure you want to clear the input?')) {
					$input.val('');
					$input.focus();
				}
			}
		});

		/**
		 * Copy output to clipboard
		 */
		$copyBtn.on('click', function() {
			const output = $output.val();

			if (!output) {
				showMessage('Nothing to copy. Please convert some JSX first.', 'error');
				return;
			}

			// Copy to clipboard
			$output.select();
			
			try {
				const successful = document.execCommand('copy');
				if (successful) {
					showMessage('Gutenberg blocks copied to clipboard!', 'success');
					
					// Update button text temporarily
					const originalText = $copyBtn.text();
					$copyBtn.text('✓ Copied!');
					setTimeout(function() {
						$copyBtn.text(originalText);
					}, 2000);
				} else {
					fallbackCopy(output);
				}
			} catch (err) {
				fallbackCopy(output);
			}

			// Remove selection
			window.getSelection().removeAllRanges();
		});

		/**
		 * Fallback copy method for modern browsers
		 */
		function fallbackCopy(text) {
			if (navigator.clipboard) {
				navigator.clipboard.writeText(text).then(function() {
					showMessage('Gutenberg blocks copied to clipboard!', 'success');
				}).catch(function(err) {
					console.error('Copy failed:', err);
					showMessage('Failed to copy. Please copy manually.', 'error');
				});
			} else {
				showMessage('Copy not supported. Please copy manually.', 'error');
			}
		}

		/**
		 * Show notification message
		 */
		function showMessage(message, type) {
			// Remove existing messages
			$('.jsx-converter-message').remove();

			// Create message element
			const $message = $('<div>')
				.addClass('jsx-converter-message')
				.addClass(type === 'error' ? 'error' : '')
				.html('<p>' + message + '</p>')
				.appendTo('body');

			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$message.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
		}

		/**
		 * Enable keyboard shortcuts
		 */
		$(document).on('keydown', function(e) {
			// Ctrl/Cmd + Enter to convert
			if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
				e.preventDefault();
				if ($input.is(':focus')) {
					$convertBtn.click();
				}
			}

			// Escape to clear input focus
			if (e.key === 'Escape') {
				if ($input.is(':focus') || $output.is(':focus')) {
					$(document.activeElement).blur();
				}
			}
		});

		/**
		 * Auto-resize textareas
		 */
		function autoResize($textarea) {
			$textarea.css('height', 'auto');
			$textarea.css('height', $textarea[0].scrollHeight + 'px');
		}

		$input.on('input', function() {
			// Optional: auto-resize as user types
			// autoResize($(this));
		});

		/**
		 * Add helpful tooltips
		 */
		$convertBtn.attr('title', 'Convert JSX to Gutenberg blocks (Ctrl/Cmd + Enter)');
		$clearBtn.attr('title', 'Clear input textarea');
		$copyBtn.attr('title', 'Copy output to clipboard');

		/**
		 * Sample data for testing (optional)
		 */
		if (window.location.hash === '#demo') {
			const demoJSX = `<section
  className="bg-primary section-padding-y"
  aria-labelledby="cta-heading"
>
	<div className="container-padding-x container mx-auto">
	<div className="mx-auto flex max-w-xl flex-col items-center gap-8 md:gap-10">
		<div className="section-title-gap-lg mx-auto flex max-w-xl flex-col items-center text-center">
		<Tagline className="text-primary-foreground/80">
			CTA Section
		</Tagline>
		<h2 id="cta-heading" className="heading-lg text-primary-foreground">
			Action-driving headline that creates urgency
		</h2>
		<p className="text-primary-foreground/80">
			Add one or two compelling sentences that reinforce your main value
			proposition and overcome final objections.
		</p>
		</div>
		<Button
		className="bg-primary-foreground text-primary hover:bg-primary-foreground/80"
		aria-label="Get started with our service"
		>
		Get started
		<ArrowRight />
		</Button>
	</div>
	</div>
</section>`;
			$input.val(demoJSX);
			showMessage('Demo JSX loaded! Click "Convert to Gutenberg" to see it in action.', 'success');
		}

		// Focus input on load
		$input.focus();
	});

})(jQuery);
