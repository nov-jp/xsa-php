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
		$parts = explode( '_', trim( $var_name, '-' ) ); // '--cq-i-s_hover_c-nth-child-m2n-p-4-of-p_action_after_content--' => [ 'cq-i-s', 'hover', 'c-nth-child-m2n-p-4-of-p', 'action', 'after', 'content' ]

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
			if ( isset( $this->queries[ $part ] ) ) { // cq-i-s
				$slot[ 'query' ] = $this->queries[ $part ]; // @container …
				continue;
			}
			if ( isset( $this->descendants[ $part ] ) ) {
				$slot[ 'd_key' ] = $part;
				$slot[ 'd_val' ] = $this->descendants[ $part ];
				continue;
			}
			if ( ( 0 === strpos( $part, 'd' ) || 0 === strpos( $part, 'c' ) ) && false !== strpos( $part, '-' ) ) {
				$nth_part = '';
				if ( false !== strpos( $part, '-nth-' ) ) {
					if ( false !== strpos( $part, '-child-' ) ) {
						$nth_part = substr( $part, 0, strpos( $part, '-child-' ) + 6 ); // '(d|c|c2|c3)-nth(-last)?-child'
					} elseif ( false !== strpos( $part, '-of-type-' ) ) {
						$nth_part = substr( $part, 0, strpos( $part, '-of-type-' ) + 8 ); // '(d|c|c2|c3)-nth(-last)?-of-type'
					}
				} else {
					$c = substr( $part, 0, strpos( $part, '-' ) );
					if ( 0 === strpos( $part, "{$c}-of-" ) ) { // '(d|c|c2|c3)-of'
						$nth_part = "{$c}-nth-child"; // '(d|c|c2|c3)-nth-child'
					}
				}
				if ( isset( $this->descendants[ $nth_part ] ) ) {
					$n = 'n';
					$of = '';
					if ( 0 === strpos( $part, $nth_part ) ) { // '(d|c|c2|c3)-nth(-last)?(-child|-of-type)'
						$n = substr( $part, strlen( $nth_part ) + 1 ); // 'c-nth-child-m2n-p-4-of-p' => 'm2n-p-4-of-p'
						$pos = strpos( $n, '-of-' );
						if ( false !== $pos ) {
							$n = substr( $n, 0, $pos ); // 'm2n-p-4-of-p' => 'm2n-p-4'
						}
						$n = str_replace( [ '-', 'm', 'p' ], [ ' ', '-', '+' ], $n ); // 'm2n-p-4' => '-2n + 4'
					}
					if ( false === strpos( $nth_part, '-of-type' ) ) { // '(d|c|c2|c3)(-nth(-last)?-child|-of)'
						$of = substr( $part, strpos( $part, '-of-' ) + 4 ); // 'c-nth-child-m2n-p-4-of-p' => 'p'
						if ( 0 === strpos( $of, 'attr-' ) ) {
							$of = '[' . substr( $of, 5 ) . ']';
						} elseif ( 0 === strpos( $of, 'pseudo-' ) ) {
							$of = ':' . substr( $of, 7 );
						} elseif ( false !== strpos( $of, '-' ) ) {
							$of = ':is(' . str_replace( '-', ',', $of ) . ')';
						}
						$n .= " of {$of}";
					}
					$slot[ 'd_key' ] = $nth_part; // 'c-nth-child'
					$slot[ 'd_val' ] = str_replace( '(n)', "({$n})", $this->descendants[ $nth_part ] ); // '>*:where(:nth-child(n))' => '>*:where(:nth-child(-2n + 4 of p))'
					continue;
				}
			}
			if ( isset( $this->p_elements[ $part ] ) ) { // 'after'
				$slot[ 'pe_key' ] = $part;
				$slot[ 'pe_val' ] = $this->p_elements[ $part ]; // '::after'
				continue;
			}
			if ( isset( $this->p_classes[ $part ] ) ) { // 'hover', 'action'
				if ( $slot[ 'd_key' ] ) {
					$slot[ 'pc2_key' ] = $part;
					$slot[ 'pc2_val' ] = $this->p_classes[ $part ]; // ':action'
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
			'css'      => "&{$slot[ 'pc1_val' ]}{$slot[ 'd_val' ]}{$slot[ 'pc2_val' ]}{$slot[ 'pe_val' ]}{{$body}}", // '&:hover > *:nth-child(-2n + 4 of p):action::after'
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
