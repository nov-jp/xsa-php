[日本語](README.md) | [English](README.en.md)

This text was translated from Japanese by Google Gemini.

---

# ExStyle PHP (@exstyle/php)

ExStyle PHP is a helper class that can be integrated into PHP environments to collect and parse ExStyle Properties within HTML code and generate the corresponding CSS code.

## Features

* **High Versatility**: Can be integrated into PHP-based software, including WordPress and other frameworks.
* **Most Rational Approach**: This is the PHP version of ExStyle JS operating on the server side. By generating only the necessary CSS code, it is the most efficient method in terms of data volume and transfer weight.

## General Usage Example

```PHP
&lt;?php
ob_start();
?&gt;
&lt;!DOCTYPE html&gt;
&lt;html lang=&quot;en&quot;&gt;
  &lt;head&gt;
    &lt;meta charset=&quot;utf-8&quot; /&gt;
    &lt;meta name=&quot;viewport&quot; content=&quot;width=device-width,initial-scale=1&quot; /&gt;
    …
    &lt;!--ExStyle--&gt;
    …
  &lt;/head&gt;
  &lt;body&gt;
    …
    &lt;p style=&quot;--background--: var(--indigo-6); --color--: var(--gray-0) --padding-block--: var(--size-2); --padding-inline--: var(--size-3);&quot;&gt; … &lt;/p&gt;
    …
  &lt;/body&gt;
&lt;/html&gt;
&lt;?php
// Capture the HTML code.
$html = ob_get_clean();

// Load the file if not using an autoloader.
require_once __DIR__ . '/path/to/ExStyle.php';

// Create an instance. Set the namespace as needed.
$exstyle = new ExStyle();

// Generate the CSS code.
$css = $exstyle-&gt;generate( $html );

if ( ! empty( $css ) ) {
  // Replace the comment in the head element with the style element.
  $html = str_replace( '&lt;!--ExStyle--&gt;', &quot;&lt;style&gt;{ $css }&lt;/style&gt;&quot;, $html );
}

// Final output.
echo $html;
```

## Usage Example for WordPress

### Generating CSS code from the entire page

Add the following code to your theme's `functions.php`.

```PHP
// Place a placeholder for the style element within the head element.
add_action( 'wp_head', function() {
  echo '&lt;!--ExStyle--&gt;';
}, 0 );

// Use buffering to capture the HTML code immediately before output.
add_action( 'init', function() {
  ob_start();

  add_action( 'shutdown', function() {
    $html = '';

    // Handle nested buffers and capture the HTML code.
    $level = ob_get_level();
    for ( $i = 0; $i &lt; $level; $i++ ) {
      $html .= ob_get_clean();
    }

    echo apply_filters( 'html_before_shutdown', $html );
  }, 0 );
}, 0 );

// Generate CSS code from the final HTML and embed it into the head element.
add_filter( 'html_before_shutdown', function( $html ) {
  // Load the file if not using an autoloader.
  require_once __DIR__ . '/path/to/ExStyle.php';

  // Create an instance.
  $exstyle = new ExStyle();

  // Generate the CSS code.
  $css = $exstyle-&gt;generate( $html );

  if ( ! empty( $css ) ) {
    // Replace the placeholder comment with the actual style element.
    $html = str_replace( '&lt;!--ExStyle--&gt;', &quot;&lt;style&gt;{ $css }&lt;/style&gt;&quot;, $html );
  }

  return $html;
}, 10 );
```

### Generating CSS code specifically from Post or Page content

```PHP
// Generate CSS code when the content is called and register it via wp_register_style().
add_filter( 'the_content', function( $content ) {
  // Load the file if not using an autoloader.
  require_once __DIR__ . '/path/to/ExStyle.php';

  // Create an instance.
  $exstyle = new ExStyle();

  // Generate the CSS code.
  $css = $exstyle-&gt;generate( $content );

  // Register the CSS code via wp_register_style().
  if ( ! empty( $css ) ) {
    wp_register_style( 'mytheme-content-exstyle', false, [] );
    wp_add_inline_style( 'mytheme-content-exstyle', $css );
  }

  return $content;
}, 10000 ); // High priority to capture the final processed content.

// Enqueue the registered CSS code via wp_footer().
add_action( 'wp_footer', function() {
  if ( wp_script_is( 'mytheme-content-exstyle', 'registered' ) ) {
    wp_enqueue_style( 'mytheme-content-exstyle' );
  }
}, 0 );
```

In current versions of WordPress, scripts and styles enqueued after `wp_head()` are still inserted into the `head` element, so enqueuing via `wp_footer()` works fine. However, since the output location can be difficult to adjust, you may need to manage CSS specificity carefully.

By caching the generated ExStyle CSS using WordPress's [get_transient()](https://developer.wordpress.org/reference/functions/get_transient/) and [set_transient()](https://developer.wordpress.org/reference/functions/set_transient/), you can minimize CPU load and maintain high page speeds.

---

## License

The MIT License. Copyright 2026 Nobuo Nakayama (Shimotsuki/nov-jp).
