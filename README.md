[日本語](README.md) | [English](README.en.md)

---

# ExStyle PHP (@exstyle/php)

ExStyle PHP は、PHP に組み込める、HTMLコード内 の ExStyleプロパティ を収集・解析して CSSコード を生成するヘルパークラスです。

## 特徴

- **高い汎用性**: WordPress を始めとする PHP製 のソフトウェアに組み込めます。
- **最も合理的**: サーバサイドで動作する ExStyle JS の PHP版 です。必要な CSSコード しか生成せず、データ量・転送量に最も無駄がありません。

## 一般的な使用例

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
    &lt;p style=&quot;--background--: var(--indigo-6); --color--: var(--gray-0) --padding-block--: var(--size-2); --padding-inline--: var(--size-3);&quot;&gt; &hellip; &lt;/p&gt;
    …
  &lt;/body&gt;
&lt;/html&gt;
&lt;?php
// HTMLコード を取得します。
$html = ob_get_clean();

// オートロードを使用していない場合は読み込みます。
require_once __DIR__ . '/path/to/ExStyle.php';

// インスタンスを作成します。必要に応じてネームスペースを設定してください。
$exstyle = new ExStyle();

// CSSコード を生成します。
$css = $exstyle-&gt;generate( $html );

if ( ! empty( $exstyle_css ) ) {
  // head要素内 のコメントを style要素 に置換します。
  $html = str_replace( '&lt;!--ExStyle--&gt;', &quot;&lt;style&gt;{ $css }&lt;/style&gt;&quot;, $html );
}

// 最後に出力します。
echo $html;
```

## WordPress での使用例

### ページ全体から CSSコード を生成する場合

使用しているテーマの functions.php に次のようなコードを追加してください。

```functions.php
// head要素内 に style要素 の埋め込み場所を設置する。
add_action( 'wp_head', function() {
  echo '&lt;!--ExStyle--&gt;';
}, 0 );

// バッファリングで出力直前の HTMLコード を取得する。
add_action( 'init', function() {
  ob_start();

  add_action( 'shutdown', function() {
    $html = '';

    // 入れ子になったバッファへの対策と HTMLコード の取得
    $level = ob_get_level();
    for ( $i = 0; $i &lt; $level; $i++ ) {
      $html .= ob_get_clean();
    }

    echo apply_filters( 'html_before_shutdown', $html );
  }, 0 );
}, 0 );

// 出力直前の HTMLコード から CSSコード を生成して head要素内 に埋め込む。
add_filter( 'html_before_shutdown', function( $html ) {
  // オートロードを使用していない場合は読み込みます。
  require_once __DIR__ . '/path/to/ExStyle.php';

  // インスタンスを作成します。必要に応じてネームスペースを設定してください。
  $exstyle = new ExStyle();

  // CSSコード を生成します。
  $css = $exstyle-&gt;generate( $html );

  if ( ! empty( $exstyle_css ) ) {
    // head要素内 のコメントを style要素 に置換します。
    $html = str_replace( '&lt;!--ExStyle--&gt;', &quot;&lt;style&gt;{ $css }&lt;/style&gt;&quot;, $html );
  }

  return $html;
}, 10 );
```

### 投稿や固定ページの本文から CSSコード を生成する場合

```functions.php
// 本文が呼び出されたときに CSSコード を生成し wp_register_style() で登録する。
add_filter( 'the_content', function( $content ) {
  // オートロードを使用していない場合は読み込みます。
  require_once __DIR__ . '/path/to/ExStyle.php';

  // インスタンスを作成します。必要に応じてネームスペースを設定してください。
  $exstyle = new ExStyle();

  // CSSコード を生成します。
  $css = $exstyle-&gt;generate( $content );

  // wp_register_style() で CSSコード を登録します。
  if ( ! empty( $css ) ) {
    wp_register_style( 'mytheme-content-exstyle', false, [] );
    wp_add_inline_style( 'mytheme-content-exstyle', $css );
  }

  return $content;
}, 10000 ); // 最終的な内容を取得するためプライオリティを高めに設定

// wp_enqueue_style() で登録した CSSコード をエンキューします。
add_action( 'wp_footer', function() {
  if ( wp_script_is( 'mytheme-content-exstyle', 'registered' ) ) {
    wp_enqueue_style( 'mytheme-content-exstyle' );
  }
}, 0 );
```

現行の WordPress は wp_head() 以降にエンキューされたスクリプトとスタイルを head要素内 に挿入する仕様になっているので、`wp_footer()` でエンキューしても問題ありませんが、出力場所の調整が難しいので詳細度を調整する必要がありそうです。

WordPress の [get_transient()](https://developer.wordpress.org/reference/functions/get_transient/) と [set_transient()](https://developer.wordpress.org/reference/functions/set_transient/) で、生成した ExStyle CSS をキャッシュしておけば、CPU の負荷を最小限に抑え、ページスピードも維持できます。

---

## License

The MIT License. Copyright 2026 Nobuo Nakayama (Shimotsuki/nov-jp).
