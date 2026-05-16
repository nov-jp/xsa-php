<?php /*! The MIT License. Copyright 2026 Nobuo Nakayama (Shimotsuki/nov-jp). */
class ExStyle
{
	private $queries = [];
	private $combinators = [];
	private $tree_structures = [];
	private $descendants = [];
	private $p_classes = [];
	private $p_elements = [];
	private $properties = [];
	private $column_style = '';
	private $layout_style = '';
	private $text_style = '';

	public function __construct()
	{
		// メディアクエリ・コンテナクエリ
		$this->queries = {{DATA_QUERIES}};

		// 結合子
		$this->combinators = {{DATA_COMBINATORS}};

		// ツリー構造
		$this->tree_structures = {{DATA_TREE_STRUCTURES}};

		// 子孫要素
		foreach ( $this->combinators as $k1 => $v1 ) {
			$this->descendants[ $k1 ] = substr( $v1, 1 ); // 先頭の '&' を除去
			foreach ( $this->tree_structures as $v2 ) {
				$args = ( 0 === strpos( $v2, 'nth-' ) ) ? '(n)' : '';
				$this->descendants[ "{$k1}-{$v2}" ] = substr( "{$v1}:where(:{$v2}{$args})", 1 ); // 先頭の '&' を除去
			} // foreach
		} // foreach

		// 擬似クラス
		$p_classes = {{DATA_PSEUDO_CLASSES}};
		foreach ( $p_classes as $v ) {
			$this->p_classes[ $v ] = ":where(:{$v})";
			$this->p_classes[ "not-{$v}" ] = ":where(:not(:{$v}))";
			$this->p_classes[ "s-{$v}" ] = ":where(:has(~:{$v}))";
			$this->p_classes[ "not-s-{$v}" ] = ":where(:not(:has(~:{$v})))";
			$this->p_classes[ "{$v}-s" ] = ":where(:{$v}~*)";
			$this->p_classes[ "not-{$v}-s" ] = ":where(:not(:{$v}~*))";
			$this->p_classes[ "n-{$v}" ] = ":where(:has(+:{$v}))";
			$this->p_classes[ "not-n-{$v}" ] = ":where(:not(:has(+:{$v})))";
			$this->p_classes[ "{$v}-n" ] = ":where(:{$v}+*)";
			$this->p_classes[ "not-{$v}-n" ] = ":where(:not(:{$v}+*))";
			$this->p_classes[ "d-{$v}" ] = ":where(:has(:{$v}))";
			$this->p_classes[ "not-d-{$v}" ] = ":where(:not(:has(:{$v})))";
			$this->p_classes[ "c-{$v}" ] = ":where(:has(>:{$v}))";
			$this->p_classes[ "not-c-{$v}" ] = ":where(:not(:has(>:{$v})))";
			$this->p_classes[ "c2-{$v}" ] = ":where(:has(>*>:{$v}))";
			$this->p_classes[ "not-c2-{$v}" ] = ":where(:not(:has(>*>:{$v})))";
			$this->p_classes[ "c3-{$v}" ] = ":where(:has(>*>*>:{$v}))";
			$this->p_classes[ "not-c3-{$v}" ] = ":where(:not(:has(>*>*>:{$v})))";
		} // foreach

		// 擬似要素
		$p_elements = {{DATA_PSEUDO_ELEMENTS}};
		foreach ( $p_elements as $v ) {
			$this->p_elements[ $v ] = "::{$v}";
		} // foreach

		// プロパティ
		$this->properties = {{DATA_PROPERTIES}};
		$this->column_style = {{DATA_COLUMN_STYLE}};
		$this->layout_style = {{DATA_LAYOUT_STYLE}};
		$this->text_style = {{DATA_TEXT_STYLE}};
	}

	// 解析
	private function parse( $var_name )
	{
		$parts = explode( '_', trim( $var_name, '-' ) ); // '--cq-i-s_hover_c-nth-m2np4-of-p_active_after_content--' => [ 'cq-i-s', 'hover', 'c-nth-m2np4-of-p', 'active', 'after', 'content' ]

		$slot = [
			'query' => null,
			'pc1_key' => '',
			'pc1_val' => '',
			'd_key' => '',
			'd_val' => '',
			'pc2_key' => '',
			'pc2_val' => '',
			'pe_key' => '',
			'pe_val' => '',
			'prop' => array_pop( $parts ), // 'content'
		];

		foreach ( $parts as $part ) {
			if ( isset( $this->queries[ $part ] ) ) { // '(cq-i|mq-w)-(s|m|l|xl)'
				$slot[ 'query' ] = $this->queries[ $part ]; // '@container …'
				continue;
			}
			if ( isset( $this->descendants[ $part ] ) ) { // '(d|c|c2|c3)(-empty)?'
				$slot[ 'd_key' ] = $part;
				$slot[ 'd_val' ] = $this->descendants[ $part ]; // '( *|(>*){1,3})(:empty)?'
				continue;
			}
			if ( isset( $this->descendants[ "{$part}-child" ] ) ) { // '(d|c|c2|c3)-(first|last|only)'
				$slot[ 'd_key' ] = "{$part}-child";
				$slot[ 'd_val' ] = $this->descendants[ "{$part}-child" ]; // '( *|(>*){1,3}):(first|last|only)-child'
				continue;
			}
			if ( ( false !== strpos( $part, '-nth-' ) || false !== strpos( $part, '-of-' ) ) && false === strpos( $part, '-child-' ) && false === strpos( $part, '-of-type-' ) ) {
				$nth_part = '';
				$n = 'n';
				$c = substr( $part, 0, strpos( $part, '-' ) ); // '(d|c|c2|c3)'
				if ( 0 === strpos( $part, "{$c}-nth-last-" ) ) { // '(d|c|c2|c3)-nth-last-mAnpB(-of-S)?'
					$nth_part = "{$c}-nth-last-child";
					$n = substr( $part, strpos( $part, '-last-' ) + 6 ); // 'mAnpB(-of-S)?'
				} elseif ( 0 === strpos( $part, "{$c}-nth-" ) ) { // '(d|c|c2|c3)-nth-mAnpB(-of-S)?'
					$nth_part = "{$c}-nth-child";
					$n = substr( $part, strpos( $part, '-nth-' ) + 5 ); // 'mAnpB(-of-S)?'
				} elseif ( 0 === strpos( $part, "{$c}-of-" ) ) { // '(d|c|c2|c3)-of-S'
					$nth_part = "{$c}-nth-child";
				}
				if ( '' !== $nth_part && isset( $this->descendants[ $nth_part ] ) ) {
					if ( 'n' !== $n ) {
						$pos = strpos( $n, '-of-' );
						if ( false !== $pos ) {
							$n = substr( $n, 0, $pos ); // 'mAnpB-of-S' => 'mAnpB'
						}
						$n = str_replace( [ 'm', 'p' ], [ '-', '+' ], $n ); // 'mAnpB' => '-An+B'
					}
					if ( false !== strpos( $part, '-of-' ) ) {
						$s = substr( $part, strpos( $part, '-of-' ) + 4 ); // '(d|c|c2|c3)-(nth(-last)?-mAnpB-of-S|of-S)' => 'S'
						if ( 0 === strpos( $s, 'attr-' ) ) {
							$s = '[' . substr( $s, 5 ) . ']'; // 'attr-NAME' => '[NAME]'
						} elseif ( 0 === strpos( $s, 'pseudo-' ) ) {
							$s = ':' . substr( $s, 7 ); // 'pseudo-NAME' => ':NAME'
						} elseif ( false !== strpos( $s, '-' ) ) {
							$s = ':is(' . str_replace( '-', ',', $s ) . ')'; // 'TYPE-TYPE' => ':is(TYPE,TYPE)'
						}
						$n .= " of {$s}"; // '-An+B of S'
					}
					$slot[ 'd_key' ] = $nth_part; // 'd-nth-child'
					$slot[ 'd_val' ] = str_replace( '(n)', "({$n})", $this->descendants[ $nth_part ] ); // '>*:where(:nth-child(-2n+4 of p))'
					continue;
				}
			}
			if ( isset( $this->p_elements[ $part ] ) ) { // '(before|after|…)'
				$slot[ 'pe_key' ] = $part;
				$slot[ 'pe_val' ] = $this->p_elements[ $part ]; // '::after'
				continue;
			}
			if ( isset( $this->p_classes[ $part ] ) ) { // '(hover|active|…)', 
				if ( $slot[ 'd_key' ] ) {
					$slot[ 'pc2_key' ] = $part;
					$slot[ 'pc2_val' ] = $this->p_classes[ $part ]; // ':active'
				} else {
					$slot[ 'pc1_key' ] = $part;
					$slot[ 'pc1_val' ] = $this->p_classes[ $part ]; // ':hover'
				}
				continue;
			}
			return null;
		} // foreach

		$body = "{$slot[ 'prop' ]}:var({$var_name});";
		if ( isset( $this->properties[ $slot[ 'prop' ] ] ) ) {
			$body = str_replace( [ '/*@prop@*/', '/*@layout_style@*/', '/*@column_style@*/', '/*@text_style@*/' ], [ $var_name, $this->layout_style, $this->column_style, $this->text_style ], $this->properties[ $slot[ 'prop' ] ] );
		}

		return [
			'selector' => "[style*=\"{$var_name}:\"]",
			'css'      => "&{$slot[ 'pc1_val' ]}{$slot[ 'd_val' ]}{$slot[ 'pc2_val' ]}{$slot[ 'pe_val' ]}{{$body}}", // '&:hover>*:nth-child(-2n+4 of p):active::after{content:var(--cq-i-s_hover_c-nth-m2np4-of-p_active_after_content--);}'
			'slot'     => $slot,
		];
	}

	// 優先度計算
	private function get_priority_array( $data )
	{
		$slot = $data[ 'slot' ];

		$d_array = array_flip( array_keys( $this->descendants ) );
		$d_index = isset( $d_array[ $slot[ 'd_key' ] ] ) ? $d_array[ $slot[ 'd_key' ] ] + 1 : 0;

		$pc_array = array_flip( array_keys( $this->p_classes ) );
		$pc1_index = isset( $pc_array[ $slot[ 'pc1_key' ] ] ) ? $pc_array[ $slot[ 'pc1_key' ] ] + 1 : 0;
		$pc2_index = isset( $pc_array[ $slot[ 'pc2_key' ] ] ) ? $pc_array[ $slot[ 'pc2_key' ] ] + 1 : 0;

		$pe_array = array_flip( array_keys( $this->p_elements ) );
		$pe_index = isset( $pe_array[ $slot[ 'pe_key' ] ] ) ? $pe_array[ $slot[ 'pe_key' ] ] + 1 : 0;

		$prop_array = array_flip( array_keys( $this->properties ) );
		$prop_index = isset( $prop_array[ $slot[ 'prop' ] ] ) ? $prop_array[ $slot[ 'prop' ] ] + 1 : 0;

		return [
			$pc1_index,
			( $d_index ?: 1e3 ),
			$pc2_index,
			$pe_index,
			( $prop_index ?: 1e3 ),
		];
	}

	// CSSコード の生成
	public function generate( $html )
	{
		if ( empty( $html ) ) {
			return '';
		}

		// すべての style属性値 を取得
		preg_match_all( '/[\s]style=\"([^\"]+)\"|[\s]style=\'([^\']+)\'/', $html, $matches );
		$style = ( ! empty( $matches[ 1 ] ) && ! empty( $matches[ 2 ] ) ) ? implode( ' ', array_filter( array_merge( $matches[ 1 ], $matches[ 2 ] ), 'trim' ) ) : '';

		if ( empty( $style ) ) {
			return '';
		}

		$map = [];

		// style属性値 から ExStyleプロパティ を取得
		preg_match_all( '/(--[a-z0-9-_]+--(?=:))/', $style, $matches );

		if ( empty( $matches[ 1 ] ) ) {
			return '';
		}

		foreach ( array_unique( $matches[ 1 ] ) as $var_name ) {
			$data = $this->parse( $var_name );
			if ( $data ) {
				$map[ $var_name ] = $data;
			}
		} // foreach

		if ( [] === $map ) {
			return '';
		}

		usort( $map, function( $a, $b ) {
			$array_a = $this->get_priority_array( $a );
			$array_b = $this->get_priority_array( $b );

			for ( $i = 0; $i < count( $array_a ); $i++ ) {
				if ( $array_a[ $i ] !== $array_b[ $i ] ) {
					return $array_a[ $i ] - $array_b[ $i ];
				}
			} // for

			if ( strlen( $a[ 'slot' ][ 'prop' ] ) !== strlen( $b[ 'slot' ][ 'prop' ] ) ) {
				return strlen( $a[ 'slot' ][ 'prop' ] ) - strlen( $b[ 'slot' ][ 'prop' ] );
			}

			return strcmp( $a[ 'slot' ][ 'prop' ], $b[ 'slot' ][ 'prop' ] );
		} );

		$output = '';
		$query_groups = [];

		foreach ( $map as $data ) {
			$slot = $data[ 'slot' ];
			if ( empty( $slot[ 'query' ] ) ) {
				$output .= "{$data[ 'selector' ]}{{$data[ 'css' ]}}\n";
			} else {
				if ( ! isset( $query_groups[ $slot[ 'query' ] ] ) ) {
					$query_groups[ $slot[ 'query' ] ] = '';
				}
				$query_groups[ $slot[ 'query' ] ] .= "\t{$data[ 'selector' ]}{{$data[ 'css' ]}}\n";
			}
		} // foreach

		foreach ( $this->queries as $query ) {
			if ( ! empty( $query_groups[ $query ] ) ) {
				$output .= "{$query}{\n{$query_groups[ $query ]}}\n";
			}
		} // foreach

		return $output;
	}
}
