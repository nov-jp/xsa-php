import fs from 'fs';
import path from 'path';

// パスの設定
const paths = {
	json: '../core/src/data.json',
	template: './src/XSA.php',
	output: './dist/XSA.php'
};

// JSON データを PHP の配列に変換する関数
function toPhpArray( obj ) {
	const entries = Object.entries( obj ).map( ( [ key, value ] ) => {
		// 値がネストしている場合
		const v = ( 'object' === typeof value && null !== value ) ? toPhpArray( value ) : `'${ value.replace( /'/g, "\\'" ) }'`;
		return ( /^\d+$/.test( `${ key }` ) ) ? v : `'${ key }' => ${ v }`;
	} );
	return `[${ entries.join( ', ' ) }]`;
}

// JSON データを PHP の文字列に変換する関数
function toString( obj ) {
	return `'${ obj.replace( /'/g, "\\'" ) }'`;
}

// メイン処理
try {
	const jsonData = JSON.parse( fs.readFileSync( paths.json, 'utf8' ) );
	let template = fs.readFileSync( paths.template, 'utf8' );

	// マーカーを置換
	template = template.replace( '{{DATA_QUERIES}}', toPhpArray( jsonData.queries ) );
	template = template.replace( '{{DATA_COMBINATORS}}', toPhpArray( jsonData.combinators ) );
	template = template.replace( '{{DATA_SIBLINGS}}', toPhpArray( jsonData.siblings ) );
	template = template.replace( '{{DATA_PSEUDO_CLASSES}}', toPhpArray( jsonData.pseudo_classes ) );
	template = template.replace( '{{DATA_PSEUDO_ELEMENTS}}', toPhpArray( jsonData.pseudo_elements ) );
	template = template.replace( '{{DATA_PROPERTIES}}', toPhpArray( jsonData.property_styles ) );
	template = template.replace( '{{DATA_COLUMN_STYLE}}', toString( jsonData.column_style ) );
	template = template.replace( '{{DATA_LAYOUT_STYLE}}', toString( jsonData.layout_style ) );
	template = template.replace( '{{DATA_TEXT_STYLE}}', toString( jsonData.text_style ) );

	// 出力先ディレクトリがなければ作成
	if ( ! fs.existsSync( './dist' ) ) {
		fs.mkdirSync('./dist');
	}

	fs.writeFileSync( paths.output, template );
	console.log( 'XSA.php has been built successfully!' );
} catch ( err ) {
	console.error( 'Build failed:', err );
}
