[日本語](README.md) | [English](README.en.md)

This text was translated from Japanese by Google Gemini.

---

# ExStyle PHP (@exstyle/php)

ExStyle PHP is a PHP helper class that collects and parses ExStyle properties within HTML code to generate CSS code.

## Features

* **High Versatility**: Can be integrated into WordPress and other PHP-based software.
* **The Most Logical Choice**: This is the PHP version of ExStyle JS operating on the server side. It generates only the necessary CSS code, making it the most efficient in terms of data size and transfer volume.

## Installation

Download and place it in any directory, or if you have a development environment set up, install it via npm:

```Bash
npm install @exstyle/php
```

Or via composer:

```Bash
composer require nov-jp/exstyle-php
```

## General Usage Example

```PHP
<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    …
    <!--ExStyle-->
    …
  </head>
  <body>
    …
    <p style="--background--: var(--indigo-6); --color--: var(--gray-0) --padding-block--: var(--size-2); --padding-inline--: var(--size-3);"> … </p>
    …
  </body>
</html>
<?php
// Retrieve the HTML code.
$html = ob_get_clean();

// Load the file if not using an autoloader.
require_once __DIR__ . '/path/to/ExStyle.php';

// Create an instance. Set the namespace as needed.
$exstyle = new ExStyle();

// Generate CSS code.
$css = $exstyle->generate( $html );

if ( ! empty( $exstyle_css ) ) {
  // Replace the comment in the head element with a style element.
  $html = str_replace( '<!--ExStyle-->', "<style>{ $css }</style>", $html );
}

// Final output.
echo $html;
```

## Usage in WordPress

### Generating CSS from the Entire Page

Add the following code to your theme's `functions.php`.

```functions.php
// Set a placeholder for the style element in the head element.
add_action( 'wp_head', function() {
  echo '<!--ExStyle-->';
}, 0 );

// Capture the HTML code just before output using buffering.
add_action( 'init', function() {
  ob_start();

  add_action( 'shutdown', function() {
    $html = '';

    // Handle nested buffers and retrieve HTML code.
    $level = ob_get_level();
    for ( $i = 0; $i < $level; $i++ ) {
      $html .= ob_get_clean();
    }

    echo apply_filters( 'html_before_shutdown', $html );
  }, 0 );
}, 0 );

// Generate CSS from the final HTML and embed it in the head.
add_filter( 'html_before_shutdown', function( $html ) {
  // Load if not using autoloader.
  require_once __DIR__ . '/path/to/ExStyle.php';

  // Create instance.
  $exstyle = new ExStyle();

  // Generate CSS.
  $css = $exstyle->generate( $html );

  if ( ! empty( $exstyle_css ) ) {
    // Replace comment with style element.
    $html = str_replace( '<!--ExStyle-->', "<style>{ $css }</style>", $html );
  }

  return $html;
}, 10 );
```

### Generating CSS from Post or Page Content

```functions.php
// Generate CSS when content is called and register it via wp_register_style().
add_filter( 'the_content', function( $content ) {
  // Load if not using autoloader.
  require_once __DIR__ . '/path/to/ExStyle.php';

  // Create instance.
  $exstyle = new ExStyle();

  // Generate CSS code from the content.
  $css = $exstyle->generate( $content );

  // Register the CSS code using wp_register_style().
  if ( ! empty( $css ) ) {
    wp_register_style( 'mytheme-content-exstyle', false, [] );
    wp_add_inline_style( 'mytheme-content-exstyle', $css );
  }

  return $content;
}, 10000 ); // High priority to capture final processed content.

// Enqueue the registered CSS.
add_action( 'wp_footer', function() {
  if ( wp_script_is( 'mytheme-content-exstyle', 'registered' ) ) {
    wp_enqueue_style( 'mytheme-content-exstyle' );
  }
}, 0 );
```

Since modern WordPress specifications allow scripts and styles enqueued after `wp_head()` to be inserted into the `head` element, enqueuing via `wp_footer()` is acceptable. However, as controlling the exact output location can be difficult, adjusting selector specificity may be necessary.

By caching the generated ExStyle CSS using WordPress's [get_transient()](https://developer.wordpress.org/reference/functions/get_transient/) and [set_transient()](), you can minimize CPU load and maintain high page speeds.

---

The MIT License. Copyright 2026 Nobuo Nakayama (Shimotsuki/nov-jp).
