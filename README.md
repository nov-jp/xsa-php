[日本語](README.md) | [English](README.en.md)

---

# XSA PHP (@xsa/php)

PHP実行環境 で XSAプロパティ から CSSコード を生成するヘルパークラスです。

## インストール

ビルドツールなどを使用している場合は npm や composer からインストールできます。

```Bash
npm install @xsa/php
```

```Bash
composer require nov-jp/xsa-php
```

## 一般的な使用例

```PHP
<?php
require_once __DIR__ . '/path/XSA.php';
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <!--XSA-->
    …
  </head>
  <body>
    …
    <p style="--background--: var(--indigo-6); --color--: var(--gray-0) --padding-block--: var(--size-2); --padding-inline--: var(--size-3);"> … </p>
    …
  </body>
</html>
<?php
$html = ob_get_clean();
$xsa = new \XSA();
$css = $xsa->generate( $html );
if ( ! empty( $css ) ) {
  $style = "<style>{$css}</style>";
  $html = str_replace( '<!--XSA-->', $style, $html );
}
echo $html;
```

## WordPress での使用例

functions.php にフックを追加してヘルパークラスを使用します。

```PHP
require_once __DIR__ . '/path/XSA.php';
add_filter( 'the_content', function( $content ) {
  $xsa = new \XSA();
  $css = $xsa->generate( $content );
  if ( ! empty( $css ) ) {
    $style = "<style>@scope{{$css}}</style>";
    $content = $style . "\n" . $content;
  }
  return $content;
}, 10000 );
```

---

The MIT License. Copyright 2026 Nobuo Nakayama @ Shimotsuki (https://github.com/nov-jp/).
