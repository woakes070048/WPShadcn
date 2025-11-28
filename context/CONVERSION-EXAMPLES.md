# JSX to Gutenberg Conversion Examples

Real-world examples showing the conversion from Shadcn React components to WordPress Gutenberg blocks.

## Table of Contents
1. [Basic Section](#basic-section)
2. [Hero with CTA](#hero-with-cta)
3. [Feature Grid](#feature-grid)
4. [Pricing Section](#pricing-section)
5. [Testimonial Card](#testimonial-card)
6. [Contact Section](#contact-section)

---

## Basic Section

### Input (JSX)
```jsx
<section className="bg-background section-padding-y">
  <div className="container mx-auto">
    <h2 className="text-foreground heading-lg">Welcome</h2>
    <p className="text-muted-foreground">This is a basic section.</p>
  </div>
</section>
```

### Output (Gutenberg)
```html
<!-- wp:group {"className":"section-padding-y","backgroundColor":"background"} -->
<div class="wp-block-group section-padding-y has-background-background-color has-background">
  <!-- wp:group {"className":"container mx-auto"} -->
  <div class="wp-block-group container mx-auto">
    <!-- wp:heading {"level":2,"className":"heading-lg","textColor":"foreground"} -->
    <h2 class="wp-block-heading heading-lg has-foreground-color has-text-color">Welcome</h2>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {"textColor":"muted-foreground"} -->
    <p class="has-muted-foreground-color has-text-color">This is a basic section.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

---

## Hero with CTA

### Input (JSX)
```jsx
<section className="bg-primary section-padding-y">
  <div className="container mx-auto">
    <div className="max-w-4xl mx-auto flex flex-col items-center text-center gap-8">
      <Tagline className="text-primary-foreground">New Release</Tagline>
      <h1 className="text-primary-foreground">Build Faster with Shadcn</h1>
      <p className="text-primary-foreground text-xl">
        A modern WordPress theme with beautiful components.
      </p>
      <Button className="bg-primary-foreground text-primary">
        Get Started
      </Button>
    </div>
  </div>
</section>
```

### Output (Gutenberg)
```html
<!-- wp:group {"className":"section-padding-y","backgroundColor":"primary"} -->
<div class="wp-block-group section-padding-y has-primary-background-color has-background">
  <!-- wp:group {"className":"container mx-auto"} -->
  <div class="wp-block-group container mx-auto">
    <!-- wp:group {"layout":{"type":"constrained","contentSize":"896px"}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"metadata":{"name":"Subtitle"},"className":"tagline","textColor":"primary-foreground","fontSize":"sm"} -->
      <p class="tagline has-primary-foreground-color has-text-color has-sm-font-size">New Release</p>
      <!-- /wp:paragraph -->
      
      <!-- wp:heading {"level":1,"textColor":"primary-foreground"} -->
      <h1 class="wp-block-heading has-primary-foreground-color has-text-color">Build Faster with Shadcn</h1>
      <!-- /wp:heading -->
      
      <!-- wp:paragraph {"textColor":"primary-foreground","fontSize":"xl"} -->
      <p class="has-primary-foreground-color has-text-color has-xl-font-size">A modern WordPress theme with beautiful components.</p>
      <!-- /wp:paragraph -->
      
      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
      <div class="wp-block-buttons">
        <!-- wp:button {"backgroundColor":"primary-foreground","textColor":"primary"} -->
        <div class="wp-block-button">
          <a class="wp-block-button__link wp-element-button">Get Started</a>
        </div>
        <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

---

## Feature Grid

### Input (JSX)
```jsx
<section className="bg-muted section-padding-y">
  <div className="container mx-auto">
    <div className="max-w-2xl mx-auto text-center">
      <h2 className="heading-lg text-foreground">Key Features</h2>
      <p className="text-muted-foreground">Everything you need to succeed.</p>
    </div>
    
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
      <div className="bg-card p-6 rounded-lg border border-border">
        <h3 className="text-xl font-semibold text-card-foreground">Fast</h3>
        <p className="text-muted-foreground">Lightning speed performance.</p>
      </div>
      
      <div className="bg-card p-6 rounded-lg border border-border">
        <h3 className="text-xl font-semibold text-card-foreground">Secure</h3>
        <p className="text-muted-foreground">Enterprise-grade security.</p>
      </div>
      
      <div className="bg-card p-6 rounded-lg border border-border">
        <h3 className="text-xl font-semibold text-card-foreground">Scalable</h3>
        <p className="text-muted-foreground">Grows with your business.</p>
      </div>
    </div>
  </div>
</section>
```

### Notes
- Grid layouts convert to `wp:group` with grid layout type
- Responsive classes (md:grid-cols-3) are preserved
- Border and border-radius styles are maintained

---

## Pricing Section

### Input (JSX)
```jsx
<section className="bg-background section-padding-y">
  <div className="container mx-auto">
    <div className="text-center max-w-xl mx-auto">
      <Tagline>Pricing</Tagline>
      <h2 className="heading-lg text-foreground">Choose Your Plan</h2>
    </div>
    
    <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12 max-w-4xl mx-auto">
      {/* Starter Plan */}
      <div className="bg-card p-8 rounded-xl border border-border">
        <div className="flex flex-col gap-6">
          <div>
            <h3 className="text-2xl font-bold text-card-foreground">Starter</h3>
            <p className="text-muted-foreground">Perfect for individuals</p>
          </div>
          <div>
            <span className="text-4xl font-bold text-foreground">$29</span>
            <span className="text-muted-foreground">/month</span>
          </div>
          <Button className="bg-primary text-primary-foreground">
            Get Started
          </Button>
        </div>
      </div>
      
      {/* Pro Plan */}
      <div className="bg-primary p-8 rounded-xl">
        <div className="flex flex-col gap-6">
          <div>
            <h3 className="text-2xl font-bold text-primary-foreground">Pro</h3>
            <p className="text-primary-foreground">Best for teams</p>
          </div>
          <div>
            <span className="text-4xl font-bold text-primary-foreground">$99</span>
            <span className="text-primary-foreground">/month</span>
          </div>
          <Button className="bg-primary-foreground text-primary">
            Get Started
          </Button>
        </div>
      </div>
    </div>
  </div>
</section>
```

### Key Conversions
- `rounded-xl` → border-radius: 1rem
- `text-4xl` → fontSize: 4-xl
- `font-bold` → fontWeight: 700
- Grid with max-width → constrained layout with contentSize

---

## Testimonial Card

### Input (JSX)
```jsx
<section className="bg-muted section-padding-y">
  <div className="container mx-auto max-w-3xl">
    <div className="bg-card p-8 rounded-lg border border-border">
      <div className="flex flex-col gap-6">
        <p className="text-lg text-card-foreground">
          "This theme has transformed how we build WordPress sites. 
          The Gutenberg integration is seamless!"
        </p>
        <div className="flex items-center gap-4">
          <div className="w-12 h-12 bg-primary rounded-full"></div>
          <div>
            <p className="font-semibold text-card-foreground">Jane Doe</p>
            <p className="text-sm text-muted-foreground">CEO, TechCorp</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
```

### Output Shows
- Card styling with borders and padding
- Flex layout preserved
- Font sizes and weights converted
- Color hierarchy maintained

---

## Contact Section

### Input (JSX)
```jsx
<section className="bg-background section-padding-y">
  <div className="container mx-auto">
    <div className="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
      {/* Content */}
      <div className="flex flex-col gap-6">
        <Tagline>Get in Touch</Tagline>
        <h2 className="heading-lg text-foreground">Let's Talk</h2>
        <p className="text-muted-foreground text-lg">
          Have a project in mind? We'd love to hear from you.
        </p>
        <div className="flex flex-col gap-4">
          <p className="text-foreground">
            <strong>Email:</strong> hello@example.com
          </p>
          <p className="text-foreground">
            <strong>Phone:</strong> +1 (555) 123-4567
          </p>
        </div>
      </div>
      
      {/* CTA */}
      <div className="bg-primary p-8 rounded-lg flex flex-col items-center justify-center gap-6 text-center">
        <h3 className="text-2xl font-bold text-primary-foreground">
          Ready to Start?
        </h3>
        <p className="text-primary-foreground">
          Book a free consultation today.
        </p>
        <Button className="bg-primary-foreground text-primary">
          Book Now
        </Button>
      </div>
    </div>
  </div>
</section>
```

### Highlights
- Two-column grid layout
- Mixed content types (text + CTA card)
- Responsive breakpoints preserved
- Centered alignment in CTA section

---

## Advanced Example: Full Landing Page Section

### Input (JSX)
```jsx
<section className="bg-gradient-to-b from-background to-muted section-padding-y">
  <div className="container mx-auto">
    {/* Header */}
    <div className="max-w-3xl mx-auto text-center">
      <Tagline>Launch Week</Tagline>
      <h1 className="text-5xl font-bold text-foreground">
        The Future of WordPress Themes
      </h1>
      <p className="text-xl text-muted-foreground mt-6">
        Build stunning websites with Shadcn components and Gutenberg blocks.
      </p>
    </div>
    
    {/* Stats */}
    <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mt-16 max-w-4xl mx-auto">
      <div className="text-center">
        <p className="text-4xl font-bold text-primary">50+</p>
        <p className="text-muted-foreground">Components</p>
      </div>
      <div className="text-center">
        <p className="text-4xl font-bold text-primary">1000+</p>
        <p className="text-muted-foreground">Downloads</p>
      </div>
      <div className="text-center">
        <p className="text-4xl font-bold text-primary">4.9</p>
        <p className="text-muted-foreground">Rating</p>
      </div>
      <div className="text-center">
        <p className="text-4xl font-bold text-primary">24/7</p>
        <p className="text-muted-foreground">Support</p>
      </div>
    </div>
    
    {/* CTA */}
    <div className="flex justify-center gap-4 mt-12">
      <Button className="bg-primary text-primary-foreground">
        Download Now
      </Button>
      <Button className="bg-secondary text-secondary-foreground">
        View Demo
      </Button>
    </div>
  </div>
</section>
```

### Complex Features
- Gradient backgrounds (preserved as className)
- Multi-column responsive grid (2 cols on mobile, 4 on desktop)
- Multiple CTAs side-by-side
- Varied text sizes and weights
- Stats section with emphasis

---

## Tips for Converting Complex Layouts

1. **Nested Grids**: Break into separate group blocks
2. **Responsive Classes**: Will be preserved (e.g., `md:grid-cols-3`)
3. **Custom Components**: Replace with standard HTML first
4. **Icons**: Remove icon components, add manually in WordPress
5. **Animations**: Not supported in conversion, add via custom CSS

## Pattern Categories

Save your converted blocks as patterns in these categories:
- `hero` - Hero sections
- `features` - Feature showcases
- `cta` - Call-to-action sections
- `pricing` - Pricing tables
- `testimonials` - Testimonial cards
- `contact` - Contact sections
- `content` - General content blocks

---

Need more examples? Check out:
- `/patterns/` directory for existing patterns
- `/context/example-jsx.txt` for the basic example
- `README-CONVERTER.md` for full documentation
