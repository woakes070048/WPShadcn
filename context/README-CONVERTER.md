# JSX to Gutenberg Blocks Converter

A powerful tool for converting Shadcn React sections to WordPress Gutenberg blocks with automatic theme.json integration.

## Features

- 🎨 **Smart Class Mapping**: Automatically converts Tailwind/Shadcn classes to theme.json variables
- 🎯 **Component Recognition**: Detects and converts Shadcn components (Button, Tagline, etc.)
- 🎨 **Theme Integration**: Uses colors, spacing, and typography from your theme.json
- 📦 **Layout Preservation**: Maintains flex, grid, and responsive layouts
- 🔄 **Real-time Conversion**: Instant conversion via intuitive admin interface

## Access the Tool

Navigate to: **WordPress Admin → Appearance → JSX Converter**

Or visit: `/wp-admin/themes.php?page=jsx-converter`

## Usage

### Basic Workflow

1. **Paste JSX**: Copy your Shadcn React section code
2. **Convert**: Click "Convert to Gutenberg" (or press Ctrl/Cmd + Enter)
3. **Copy Output**: Copy the generated Gutenberg blocks
4. **Use**: Paste into WordPress editor or save as a pattern

### Example Input (JSX)

```jsx
<section
  className="bg-primary section-padding-y"
  aria-labelledby="cta-heading"
>
  <div className="container-padding-x container mx-auto">
    <div className="mx-auto flex max-w-xl flex-col items-center gap-8">
      <div className="section-title-gap-lg mx-auto flex max-w-xl flex-col items-center text-center">
        <Tagline className="text-primary-foreground/80">
          CTA Section
        </Tagline>
        <h2 id="cta-heading" className="heading-lg text-primary-foreground">
          Action-driving headline that creates urgency
        </h2>
        <p className="text-primary-foreground/80">
          Add compelling content that drives action.
        </p>
      </div>
      <Button
        className="bg-primary-foreground text-primary"
        aria-label="Get started"
      >
        Get started
        <ArrowRight />
      </Button>
    </div>
  </div>
</section>
```

### Example Output (Gutenberg)

```html
<!-- wp:group {"backgroundColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|9","bottom":"var:preset|spacing|9"}}}} -->
<div class="wp-block-group has-primary-background-color has-background">
  <!-- wp:group {"layout":{"type":"constrained","contentSize":"576px"}} -->
  <div class="wp-block-group">
    <!-- wp:paragraph {"metadata":{"name":"Subtitle"},"className":"tagline","textColor":"muted-foreground","fontSize":"sm"} -->
    <p class="tagline has-muted-foreground-color has-text-color has-sm-font-size">CTA Section</p>
    <!-- /wp:paragraph -->
    
    <!-- wp:heading {"className":"heading-lg","textColor":"primary-foreground"} -->
    <h2 class="wp-block-heading heading-lg has-primary-foreground-color has-text-color">Action-driving headline that creates urgency</h2>
    <!-- /wp:heading -->
    
    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
      <!-- wp:button {"backgroundColor":"primary-foreground","textColor":"primary"} -->
      <div class="wp-block-button">
        <a class="wp-block-button__link wp-element-button">Get started</a>
      </div>
      <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

## Supported Conversions

### React Components → Gutenberg Blocks

| React Component | Gutenberg Block | Notes |
|----------------|-----------------|-------|
| `<section>` | `wp:group` | With proper metadata |
| `<div>` | `wp:group` | Preserves layout |
| `<h1-h6>` | `wp:heading` | With level attribute |
| `<p>` | `wp:paragraph` | Text colors preserved |
| `<Button>` | `wp:button` | In `wp:buttons` wrapper |
| `<Tagline>` | `wp:paragraph` | With tagline class |

### Class Mappings

#### Background Colors
- `bg-primary` → `backgroundColor="primary"`
- `bg-secondary` → `backgroundColor="secondary"`
- `bg-muted` → `backgroundColor="muted"`
- All theme.json colors supported

#### Text Colors
- `text-primary` → `textColor="primary"`
- `text-foreground` → `textColor="foreground"`
- `text-muted-foreground` → `textColor="muted-foreground"`
- Opacity variants (e.g., `/80`) handled automatically

#### Spacing
- `section-padding-y` → `padding: var(--wp--preset--spacing--9)`
- `gap-8` → `blockGap: var:preset|spacing|8`
- `p-4`, `pt-4`, `pb-4`, etc. → Converted to theme spacing

#### Layout
- `flex` + `items-center` → `layout: {"type": "constrained"}`
- `max-w-xl` → `contentSize: "576px"`
- Container classes preserved

#### Typography
- `heading-lg` → Preserved as className
- `text-center` → Handled by parent layout
- Font sizes mapped to theme.json values

## Theme.json Integration

The converter automatically uses your theme's design tokens:

### Colors (Auto-detected)
```json
{
  "colors": [
    "background", "foreground", "primary", "primary-foreground",
    "secondary", "secondary-foreground", "muted", "muted-foreground",
    "accent", "accent-foreground", "destructive", "card", "popover"
  ]
}
```

### Spacing Scale (1-10)
```json
{
  "1": "0.25rem",  "2": "0.5rem",   "3": "0.75rem",
  "4": "1rem",     "5": "1.25rem",  "6": "1.5rem",
  "7": "2rem",     "8": "2.5rem",   "9": "3rem",
  "10": "4rem"
}
```

### Font Sizes
```json
{
  "sizes": ["xs", "sm", "base", "lg", "xl", "2-xl", "3-xl", 
            "4-xl", "5-xl", "6-xl", "7-xl"]
}
```

## Best Practices

### 1. Clean JSX Input
```jsx
// ✅ Good: Clean, semantic JSX
<section className="bg-primary section-padding-y">
  <div className="container mx-auto">
    <h2 className="heading-lg text-primary-foreground">Title</h2>
  </div>
</section>

// ❌ Avoid: Inline styles, non-standard attributes
<section style="background: red" data-custom="value">
  <div>...</div>
</section>
```

### 2. Use Theme Classes
Always use classes defined in your theme.json or CSS:
- `bg-primary`, `text-foreground` (theme colors)
- `section-padding-y`, `container-padding-x` (consistent spacing)
- `heading-lg`, `tagline` (typography patterns)

### 3. Component Icons
Icons like `<ArrowRight />` are automatically removed. Add them manually in WordPress if needed.

### 4. Responsive Classes
Tailwind responsive variants are preserved as classes:
```jsx
className="gap-8 md:gap-10"  // Both classes preserved
```

### 5. Save as Patterns
For reusable sections, save the output as a pattern:
```php
// patterns/my-section.php
<?php
/**
 * Title: My Section
 * Slug: shadcn/my-section
 * Categories: shadcn
 */
?>
<!-- Paste converted Gutenberg blocks here -->
```

## Keyboard Shortcuts

- **Ctrl/Cmd + Enter**: Convert JSX to Gutenberg
- **Escape**: Clear textarea focus

## Troubleshooting

### Common Issues

#### 1. Conversion Fails
**Problem**: Error message appears after clicking Convert

**Solutions**:
- Check JSX syntax (balanced tags, quotes)
- Remove any JavaScript logic inside JSX
- Ensure components are properly closed

#### 2. Styles Not Applied
**Problem**: Colors or spacing don't match theme

**Solutions**:
- Verify class names match theme.json
- Check if color slugs exist in theme.json palette
- Use theme spacing scale (1-10)

#### 3. Layout Issues
**Problem**: Elements don't align correctly

**Solutions**:
- Use proper wrapper divs
- Add `container` and `mx-auto` for centering
- Include layout type in group attributes

#### 4. Missing Components
**Problem**: Custom components not converted

**Solutions**:
- Replace with standard HTML elements
- Add component mapping in `JSXConverter.php`
- Use Gutenberg equivalent blocks

## Advanced Usage

### Adding Custom Component Mappings

Edit `inc/Admin/JSXConverter.php`:

```php
private function convert_component_to_html( $component ) {
    // Add your custom component
    if ( preg_match( '/<MyComponent([^>]*)>(.*?)<\/MyComponent>/s', $component, $matches ) ) {
        return '<div' . $matches[1] . '>' . $matches[2] . '</div>';
    }
    
    // ... existing code
}
```

### Custom Class Mappings

Add to `parse_classes_to_attributes()`:

```php
// Custom spacing
if ( preg_match( '/^my-custom-class$/', $class ) ) {
    $attrs['style']['spacing']['padding'] = array(
        'top' => 'var:preset|spacing|5',
    );
    continue;
}
```

## API Usage (Programmatic)

For developers wanting to use the converter programmatically:

```php
<?php
// Get converter instance
$converter = \Shadcn\Admin\JSXConverter::get_instance();

// Your JSX string
$jsx = '<section>...</section>';

// Convert (use reflection to access private method)
$reflection = new ReflectionClass( $converter );
$method = $reflection->getMethod( 'convert_jsx_to_gutenberg' );
$method->setAccessible( true );
$gutenberg = $method->invoke( $converter, $jsx );

echo $gutenberg;
```

## Demo Mode

Add `#demo` to the URL to load example JSX:
```
/wp-admin/themes.php?page=jsx-converter#demo
```

## Support

For issues or feature requests:
1. Check the troubleshooting section above
2. Review your JSX syntax
3. Verify theme.json configuration
4. Test with the demo mode

## Changelog

### Version 1.0.0
- Initial release
- Core JSX to Gutenberg conversion
- Theme.json integration
- Admin UI with copy/clear functions
- Support for common Shadcn components

## Credits

Built for the Shadcn WordPress theme with ❤️

---

**Ready to convert?** Head to **Appearance → JSX Converter** and start building!
