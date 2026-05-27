<?php /*! The MIT License. Copyright 2026 Nobuo Nakayama @ Shimotsuki (https://github.com/nov-jp/). */
class XSA
{
	private $queries = [];
	private $combinators = [];
	private $siblings = [];
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

		// 兄弟擬似クラス
		$this->siblings = {{DATA_SIBLINGS}};

		// 子孫要素
		$d_index = 1;
		foreach ( $this->combinators as $k => $v ) {
			$trimmed_v = substr( $v, 1 ); // 先頭の '&' を除去
			$this->descendants[ $k ] = [ 'val' => $trimmed_v, 'index' => $d_index++ ];
			foreach ( $this->siblings as $v2 ) {
				$trimmed_v2 = ( '-child' === substr( $v2, -6 ) ) ? substr( $v2, 0, -6 ) : $v2; // 末尾の '-child' を除去
				$args = str_contains( $v2, 'nth-' ) ? '(n)' : '';
				$this->descendants[ "{$k}-{$trimmed_v2}" ] = [ 'val' => "{$trimmed_v}:where(:{$v2}{$args})", 'index' => $d_index++ ];
			} // foreach
		} // foreach

		// 擬似クラス
		$p_classes = {{DATA_PSEUDO_CLASSES}};
		$pc_patterns = [
			'S-P' => ':where(:S:P)',
			'not-S-P' => ':where(:not(:S:P))',
			'S-P-s' => ':where(:S:P~*)',
			'not-S-P-s' => ':where(:not(:S:P~*))',
			'S-P-n' => ':where(:S:P+*)',
			'not-S-P-n' => ':where(:not(:S:P+*))',
			's-S-P' => ':where(:has(~:S:P))',
			'not-s-S-P' => ':where(:not(:has(~:S:P)))',
			'n-S-P' => ':where(:has(+:S:P))',
			'not-n-S-P' => ':where(:not(:has(+:S:P)))',
			'd-S-P' => ':where(:has(:S:P))',
			'not-d-S-P' => ':where(:not(:has(:S:P)))',
			'c-S-P' => ':where(:has(>:S:P))',
			'not-c-S-P' => ':where(:not(:has(>:S:P)))',
			'c2-S-P' => ':where(:has(>*>:S:P))',
			'not-c2-S-P' => ':where(:not(:has(>*>:S:P)))',
			'c3-S-P' => ':where(:has(>*>*>:S:P))',
			'not-c3-S-P' => ':where(:not(:has(>*>*>:S:P)))',
		];
		$pc_offset = count( $p_classes ) + count( $pc_patterns );
		$pc_index = 1;
		foreach ( $pc_patterns as $k => $v ) {
			foreach ( $p_classes as $v2 ) {
				$key = str_replace( 'P', $v2, $k );
				$val = str_replace( 'P', $v2, $v );
				$index = $pc_index++;
				$this->p_classes[ str_replace( 'S-', '', $key ) ] = [ 'val' => str_replace( ':S', '', $val ), 'index' => $index ];
				$this->p_classes[ str_replace( 'S', 'nth', $key ) ] = [ 'val' => str_replace( 'S', 'nth-child(n)', $val ), 'index' => $index ];
				$this->p_classes[ str_replace( 'S', 'nth-last', $key ) ] = [ 'val' => str_replace( 'S', 'nth-last-child(n)', $val ), 'index' => $index ];
			}
		} // foreach

		// 擬似要素
		$p_elements = {{DATA_PSEUDO_ELEMENTS}};
		$pe_index = 1;
		foreach ( $p_elements as $v ) {
			$this->p_elements[ $v ] = [ 'val' => "::{$v}", 'index' => $pe_index++ ];
		} // foreach

		// プロパティ
		$properties = {{DATA_PROPERTIES}};
		$p_index = 1;
		foreach ( $properties as $k => $v ) {
			$this->properties[ $k ] = [ 'val' => $v, 'index' => $p_index++ ];
		} // foreach

		$this->column_style = {{DATA_COLUMN_STYLE}};
		$this->layout_style = {{DATA_LAYOUT_STYLE}};
		$this->text_style = {{DATA_TEXT_STYLE}};
	}

	// 解析
	private function parse( $var_name )
	{
		$parts = explode( '_', trim( $var_name, '-' ) );

		$slot = [
			'query' => null,
			'pc_key' => '',
			'pc_val' => '',
			'd_key' => '',
			'd_val' => '',
			'dpc_key' => '',
			'dpc_val' => '',
			'pe_key' => '',
			'pe_val' => '',
			'prop' => array_pop( $parts ), // 'CSS-PROPERTY'
		];

		foreach ( $parts as $part ) {
			// '(cqi|vw)-(s|m|l|xl)'
			if ( isset( $this->queries[ $part ] ) ) {
				$slot[ 'query' ] = $this->queries[ $part ]; // '(@container|@media) …'
				continue;
			}

			// '(d|c|c2|c3)-(first|last|only)?'
			if ( isset( $this->descendants[ $part ] ) ) {
				$slot[ 'd_key' ] = $part;
				$slot[ 'd_val' ] = $this->descendants[ $part ][ 'val' ]; // '( *|(>*){1,3}):where(:(first|last|only)-child)'
				continue;
			}

			// '(before|after|…)'
			if ( isset( $this->p_elements[ $part ] ) ) {
				$slot[ 'pe_key' ] = $part;
				$slot[ 'pe_val' ] = $this->p_elements[ $part ][ 'val' ];
				continue;
			}

			// '(d|c|c2|c3)-nth-(last-)?MAnPB(-of-S)?'
			// '(d|c|c2|c3)-of-S'
			// '(not-)?nth-(last-)?MAnPB(-of-S)?-is-PSEUDO-CLASS-(n|s)'
			// '(not-)?S-is-PSEUDO-CLASS-(n|s)'
			// '(not-)?(n|s|d|c|c2|c3)-nth-(last-)?MAnPB(-of-S)?-is-PSEUDO-CLASS'
			// '(not-)?(n|s)-S-is-PSEUDO-CLASS'
			// '(not-)?(d|c|c2|c3)-of-S-is-PSEUDO-CLASS'
			$nth_part = '';
			$n = 'n';
			if ( ! isset( $this->p_classes[ $part ] ) && ! str_contains( $part, '-child-' ) && ! str_contains( $part, '-of-type-' ) ) {
				$s = ''; // 'name', 'name-name', 'ID-name', 'CLASS-name', 'PSEUDO-name', 'ATTR-name'
				$is_descendants = false;
				$pos_nth = strpos( $part, 'nth-' );
				$pos_nth_last = strpos( $part, 'nth-last-' );
				$pos_of = strpos( $part, '-of-' );
				$pos_is = strpos( $part, '-is-' );
				if ( false === $pos_is ) { // '(d|c|c2|c3)-(nth-(last-)?MAnPB(-of-S)?|of-S)'
					$is_descendants = true;
					if ( false !== $pos_nth_last ) { // '(d|c|c2|c3)-nth-last-MAnPB(-of-S)?'
						$nth_part = substr( $part, 0, $pos_nth_last + 8 ); // '(d|c|c2|c3)-nth-last'
						$n = substr( $part, $pos_nth_last + 9 ); // 'MAnPB(-of-S)?'
					} else if ( false !== $pos_nth ) { // '(d|c|c2|c3)-nth-MAnPB(-of-S)?'
						$nth_part = substr( $part, 0, $pos_nth + 3 ); // '(d|c|c2|c3)-nth'
						$n = substr( $part, $pos_nth + 4 ); // 'MAnPB(-of-S)?'
					} else if ( false !== $pos_of ) { // '(d|c|c2|c3)-of-S'
						$nth_part = substr( $part, 0, $pos_of ) . '-nth'; // '(d|c|c2|c3)-nth'
					}
					if ( false !== $pos_of ) {
						$s = substr( $part, $pos_of + 4 ); // 'S'
					}
				} else {
					$has_not = str_starts_with( $part, 'not-' );
					$start = $has_not ? 4 : 0;
					$combinator = substr( $part, $start, strpos( $part, '-', $start ) + 1 - $start ); // '(n|s|d|c|c2|c3)-'
					if ( str_ends_with( $part, '-n' ) || str_ends_with( $part, '-s' ) ) { // '(not-)?(nth-(last-)?MAnPB(-of-S)?|S)-is-PSEUDO-CLASS-(n|s)'
						$nth_part = ( $has_not ? 'not-' : '' ) . 'nth' . ( false !== $pos_nth_last ? '-last' : '' ) . substr( $part, $pos_is + 3 ); // (not-)?nth(-last)?-PSEUDO-CLASS-(n|s)
						if ( false !== $pos_of ) {
							$s = substr( $part, $pos_of + 4, $pos_is - $pos_of - 4 ); // '-of-S-is-'
						} else if ( false === $pos_nth ) {
							$s = substr( $part, $start, $pos_is - $start ); // '(not-)?S-is-'
						}
					} elseif ( 'n-' === $combinator || 's-' === $combinator ) { // '(not-)?(n|s)-(nth-(last-)?MAnPB(-of-S)?|S)-is-PSEUDO-CLASS'
						$nth_part = ( $has_not ? 'not-' : '' ) . $combinator . 'nth' . ( false !== $pos_nth_last ? '-last' : '' ) . substr( $part, $pos_is + 3 ); // (not-)?(n|s)-nth(-last)?-PSEUDO-CLASS
						if ( false !== $pos_of ) {
							$s = substr( $part, $pos_of + 4, $pos_is - $pos_of - 4 ); // '-of-S-is-'
						} else if ( false === $pos_nth ) {
							$s = substr( $part, $start + 2, $pos_is - $start - 2 ); // '(not-)?(n|s)-S-is-'
						}
					} elseif ( false !== $pos_nth || false !== $pos_of ) { // '(not-)?(d|c|c2|c3)-(nth-(last-)?MAnPB(-of-S)?|of-S)-is-PSEUDO-CLASS'
						if ( false !== $pos_nth_last ) {
							$nth_part = substr( $part, 0, $pos_nth_last + 8 ); // '(not-)?(d|c|c2|c3)-nth-last'
						} elseif ( false !== $pos_nth ) {
							$nth_part = substr( $part, 0, $pos_nth + 3 ); // '(not-)?(d|c|c2|c3)-nth'
						} else {
							$nth_part = substr( $part, 0, $pos_of ) . '-nth'; // '(not-)?(d|c|c2|c3)-nth'
						}
						$nth_part .= substr( $part, $pos_is + 3 ); // '-PSEUDO-CLASS'
						if ( false !== $pos_of ) { // '-of-S-is-'
							$s = substr( $part, $pos_of + 4, $pos_is - $pos_of - 4 );
						}
					}
					$len_base = false !== $pos_of ? $pos_of : $pos_is;
					if ( false !== $pos_nth_last ) {
						$n = substr( $part, $pos_nth_last + 9, $len_base - $pos_nth_last - 9);
					} else if ( false !== $pos_nth ) {
						$n = substr( $part, $pos_nth + 4, $len_base - $pos_nth - 4 );
					}
				}
				if ( 'n' !== $n ) {
					$pos_of = strpos( $n, '-of-' );
					if ( false !== $pos_of ) {
						$n = substr( $n, 0, $pos_of );
					}
					$n = str_replace( [ 'M', 'P' ], [ '-', '+' ], $n ); // 'MAnPB' => '-An+B'
				}
				if ( '' !== $s && '' === trim( $s, "\x2D\x30..\x39\x41..\x5A\x61..\x7A" ) ) { // [\-0-9A-Za-z]
					if ( str_starts_with( $s, 'ID-' ) ) {
						$s = '#' . substr( $s, 3 );
					} elseif ( str_starts_with( $s, 'CLASS-' ) ) {
						$s = '.' . substr( $s, 6 );
					} elseif ( str_starts_with( $s, 'PSEUDO-' ) ) {
						$s = ':' . substr( $s, 7 );
					} elseif ( str_starts_with( $s, 'ATTR-' ) ) {
						$s = substr( $s, 5 );
						if ( ! str_contains( $s, '-EQ-' ) ) {
							$s = '[' . $s . ']';
						} else {
							$s_parts = explode( '-EQ-', $s );
							$s_name = $s_parts[ 0 ];
							$s_op = '';
							if ( str_ends_with( $s_name, '-A' ) ) { // Asterisk
								$s_op = '*';
							} elseif ( str_ends_with( $s_name, '-C' ) ) { // Caret
								$s_op = '^';
							} elseif ( str_ends_with( $s_name, '-D' ) ) { // Dollar
								$s_op = '$';
							} elseif ( str_ends_with( $s_name, '-T' ) ) { // Tilde
								$s_op = '~';
							} elseif ( str_ends_with( $s_name, '-P' ) ) { // Pipe
								$s_op = '|';
							}
							if ( '' !== $s_op ) {
								$s_name = substr( $s_name, 0, -2 );
							}
							$s = '[' . $s_name . $s_op . '="' . $s_parts[ 1 ] . '"]';
						}
					} elseif ( str_contains( $s, '-' ) ) {
						$s = ':is(' . str_replace( '-', ',', $s ) . ')';
					}
					$n .= " of {$s}"; // '-An+B of S'
				}
				if ( $is_descendants && '' !== $nth_part && isset( $this->descendants[ $nth_part ] ) ) {
					$slot[ 'd_key' ] = $nth_part;
					$slot[ 'd_val' ] = str_replace( '(n)', "({$n})", $this->descendants[ $nth_part ][ 'val' ] );
					continue;
				}
			}

			// '((not-)?focus|(not-)?focus(-n|-s)|(not-)?(n-|s-)focus|(not-)?(d|c|c2|c3)-focus|…)'
			if ( isset( $this->p_classes[ $part ] ) || ( '' !== $nth_part && isset( $this->p_classes[ $nth_part ] ) ) ) {
				$prefix = ( '' !== $slot[ 'd_key' ] ) ? 'dpc' : 'pc';
				$slot[ "{$prefix}_key" ] = ( '' !== $nth_part ) ? $nth_part : $part;
				$slot[ "{$prefix}_val" ] = ( '' !== $nth_part ) ? str_replace( '(n)', "({$n})", $this->p_classes[ $nth_part ][ 'val' ] ) : $this->p_classes[ $part ][ 'val' ];
				continue;
			}

			return null;
		} // foreach

		$body = "{$slot[ 'prop' ]}:var({$var_name});";
		if ( isset( $this->properties[ $slot[ 'prop' ] ] ) ) {
			$body = str_replace( [ '/*@prop@*/', '/*@layout_style@*/', '/*@column_style@*/', '/*@text_style@*/' ], [ $var_name, $this->layout_style, $this->column_style, $this->text_style ], $this->properties[ $slot[ 'prop' ] ][ 'val' ] );
		}

		return [
			'selector' => "[style*=\"{$var_name}:\"]",
			'css'      => "&{$slot[ 'pc_val' ]}{$slot[ 'd_val' ]}{$slot[ 'dpc_val' ]}{$slot[ 'pe_val' ]}{{$body}}", // '&:hover>*:nth-child(-2n+4 of p):active::after{content:var(--cqi-s_hover_c-nth-m2np4-of-p_active_after_content--);}'
			'slot'     => $slot,
		];
	}

	// 優先度計算
	private function get_priority_array( $data )
	{
		$slot = $data[ 'slot' ];
		return [
			( isset( $this->descendants[ $slot[ 'pc_key' ] ] ) ? $this->p_classes[ $slot[ 'pc_key' ] ][ 'index' ] : 0 ),
			( isset( $this->descendants[ $slot[ 'd_key' ] ] ) ? $this->descendants[ $slot[ 'd_key' ] ][ 'index' ] : 1e3 ),
			( isset( $this->descendants[ $slot[ 'dpc_key' ] ] ) ? $this->p_classes[ $slot[ 'dpc_key' ] ][ 'index' ] : 0 ),
			( isset( $this->descendants[ $slot[ 'pe_key' ] ] ) ? $this->p_elements[ $slot[ 'pe_key' ] ][ 'index' ] : 0 ),
			( isset( $this->descendants[ $slot[ 'prop' ] ] ) ? $this->properties[ $slot[ 'prop' ] ][ 'index' ] : 1e3 ),
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
		if ( empty( $matches[ 1 ] ) && empty( $matches[ 2 ] ) ) {
			return '';
		}
		$style = implode( ' ', array_filter( array_merge( $matches[ 1 ], $matches[ 2 ] ), 'trim' ) );

		// style属性値 から XSAプロパティ を取得
		preg_match_all( '/(--[A-Za-z0-9_\-]+--(?=:))/', $style, $matches );
		if ( empty( $matches[ 1 ] ) ) {
			return '';
		}
		$props = array_unique( $matches[ 1 ] );

		$map = [];

		foreach ( $props as $var_name ) {
			$data = $this->parse( $var_name );
			if ( $data ) {
				$map[ $var_name ] = $data;
			}
		} // foreach

		if ( [] === $map ) {
			return '';
		}

		// 並べ替え
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
