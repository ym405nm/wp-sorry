<?php
/*
Plugin Name: WP Sorry
Plugin URI: https://github.com/ym405nm/wp-sorry
Description: 謝罪文自動作成プラグイン
Author: yoshinori matsumoto
Version: 0.1
Author URI: https://twitter.com/ym405nm
*/

class WpSorry {
	function __construct() {
		add_action('admin_menu', array($this, 'add_pages'));
		add_shortcode('wpsorry', array($this, 'create_text'));
		add_shortcode('wpsorrycss', array($this, 'create_css'));
	}
	function add_pages() {
		add_menu_page('謝罪文作成','謝罪文作成',  'level_8', __FILE__, array($this,'wp_sorry_settings'), '', 26);
	}
	function wp_sorry_settings() {?>
<h1>設定</h1>
	<?php

		if(isset($_POST["wpsorry"]) && strcmp("1", $_POST["pdf"])==0){

			//change
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wp-sorry' ) ) {
 			   die( 'Security check' );
			}
			$sorry = $_POST['wpsorry'];

			update_option('wpsorry', $sorry);
			$pdf = $this->create_pdf();
			if(!$pdf):
				?><div class="updated fade"><p><strong>TCPDFが読み込めないため、PDFを作成できません</strong></p></div><?php
			else:
				?><div class="updated fade"><p><strong>PDFの準備ができました</strong> <a href="<?php echo plugins_url(); ?>/wp-sorry/wp-sorry.pdf" target="_blank">ダウンロード</a></p></div><?php
			endif;
		}elseif(isset($_POST["wpsorry"])){

			//change
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wp-sorry' ) ) {
 			   die( 'Security check' );
			}
			$sorry = $_POST['wpsorry'];

			update_option('wpsorry', $sorry);
			?>
			<div class="updated fade"><p><strong>謝罪文の準備ができました</strong></p></div><?php
		}
		?>

<form action="" method="post">

<?php $nonce = wp_create_nonce('wp-sorry'); ?>

<input type="hidden" name="_wpnonce" value="<?php echo $nonce; ?>">

<h2>全体</h2>
<ul>
<li>いつから？
<input type="number" name="wpsorry[start_year]" value="<?php echo date("Y");?>" min="1900" max="<?php echo date("Y");?>">年
<input type="number" name="wpsorry[start_month]" value="1" min="1" max="12">月
<input type="number" name="wpsorry[start_day]" value="1" min="1" max="31">日
<input type="number" name="wpsorry[start_time]" value="0" min="0" max="23">時</li>
<li>いつまで？
<input type="number" name="wpsorry[end_year]" value="<?php echo date("Y");?>" min="1900" max="<?php echo date("Y");?>">年
<input type="number" name="wpsorry[end_month]" value="1" min="1" max="12">月
<input type="number" name="wpsorry[end_day]" value="1" min="1" max="31">日
<input type="number" name="wpsorry[end_time]" value="0" min="0" max="23">時</li>
<li>対象範囲  <textarea name="wpsorry[affected]" cols="100" rows="5">サイト全域</textarea></li>
</ul>
<h2>改ざん</h2>
<p>改ざんの影響は？</p>
<ul>
<li><input type="checkbox" name="wpsorry[defaced_malware]" value="1">マルウェアの可能性がある</li>
</ul>
<h2>個人情報漏えい</h2>
<p>漏洩した情報は？</p>
<ul>
<li><input type="checkbox" name="wpsorry[breach][address]" value="住所">住所</li>
<li><input type="checkbox" name="wpsorry[breach][name]" value="氏名">氏名</li>
<li><input type="checkbox" name="wpsorry[breach][gender]" value="性別">性別</li>
<li><input type="checkbox" name="wpsorry[breach][birthday]" value="生年月日">生年月日</li>
<li><input type="checkbox" name="wpsorry[breach][tel]" value="電話番号">電話番号</li>
<li><input type="checkbox" name="wpsorry[breach][card]" value="クレジット番号">クレジット番号</li>
<li><input type="checkbox" name="wpsorry[breach][securitycode]" value="セキュリティコード">セキュリティコード</li>
</ul>
<h2>オプション</h2>
<p><input type="checkbox" name="pdf" value="1">PDFファイルとして出力</p>
<input type="submit" class="button-primary" value="登録/変更" >
</form>
<?php
	}
	function create_text() {
		$sorry = get_option("wpsorry");
		$is_malware = $this->is_malware($sorry["defaced_malware"]);
		$is_breach = $this->is_breach($sorry["breach"]);
		$affected = esc_html($sorry["affected"]);
		$str          = file_get_contents( WP_PLUGIN_DIR . "/wp-sorry/templete.html" );
		$params_array = array(
			"start_year",
			"start_month",
			"start_day",
			"start_time",
			"end_year",
			"end_month",
			"end_day",
			"end_time"
		);
		foreach ( $params_array as $param_key ) {
			$str = str_replace( "[" . $param_key . "]", esc_html( $sorry[ $param_key ] ), $str );
		}

		$str = str_replace( "[is_malware]", $is_malware, $str );
		$str = str_replace( "[is_breach]", $is_breach, $str );
		$str = str_replace( "[affected]", $affected, $str );

		return $str;
	}

	function is_malware($is_malware){
		$str = "";
		if(strcmp($is_malware,"1")==0){
			//マルウェア感染あり
			$str = <<< EOF
<h2>お客様へのお願い</h2>
<p>当該期間にアクセスした可能性のある方は、お手数ですが最新の状態にアップデートしたウイルス対策ソフトにて駆除をお願いいたします。</P>
EOF;
		}else{
			//マルウェア感染なし
			$str = <<< EOF
<p>アクセスしたことによるウィルスの感染は確認されておりません。</p>
EOF;
		}
		return $str;
	}

	function is_breach($is_breach){
		$str = "";
		if(count($is_breach)>0){
			//情報漏えいあり
			$breach_list = "";
			foreach($is_breach as $breach){
				$breach_list = $breach_list . sprintf("<li>%s</li>", esc_html($breach));
			}
			return sprintf("<h2>漏えいした個人情報</h2><ul>%s</ul>",$breach_list);
		}else{
			//情報漏洩なし
			$str = <<< EOF
<p>個人情報の漏洩は現在のところ確認されていません</p>
EOF;
		}
		return $str;
	}

	function create_pdf(){
		$tcpdf_path = plugin_dir_path( __FILE__ ).'tcpdf/tcpdf.php';
		if (!file_exists($tcpdf_path)){
			return false;
		}
		require_once(plugin_dir_path( __FILE__ ).'tcpdf/tcpdf.php');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);
		$pdf->AddPage();
		$font1 = $pdf->SetFont('kozgopromedium');
		$pdf->SetFont($font1, '');
		//$pdf->Text( 10, 10, $this->create_text() );
		$description = $this->create_text();
		$header = <<< EOF
<br>
<h1 style="text-align:center">当サイトの改ざんのお詫びとご報告</h1>
<br><br><br>
EOF;
		$description = $header . $description;
		$pdf->writeHTML($description);
		$pdf->Output(plugin_dir_path( __FILE__ )."wp-sorry.pdf", "F");
		return true;
	}

	function create_css(){
		$sorry = get_option("wpsorry");
		$is_malware = $this->is_malware($sorry["defaced_malware"]);
		$is_malware =preg_replace("/お客様へのお願い/", "[ お客様へのお願い ]  ", $is_malware);
		$is_malware = preg_replace("/(<(\/|)[a-zA-Z0-9]*>|\n)/", "", $is_malware);
		$is_breach = $this->is_breach($sorry["breach"]);
		$is_breach = preg_replace("/漏えいした個人情報/", "[ 漏えいした個人情報 ]  ", $is_breach);
		$is_breach = preg_replace("/<\/li><li>/", "、 ", $is_breach);
		$is_breach = preg_replace("/<(\/|)[a-zA-Z0-9]*>/", "", $is_breach);
		$affected = esc_html($sorry["affected"]);
		$str = <<< EOF
<style>
#s p:nth-child(1):before {
  content: "この度、弊社ウェブサイトに第三者からの不正なアクセスがあり、ウェブサイトが改"; }
#s p:nth-child(1):after {
  content: "ざんされていたことがわかりました。ご利用の皆様にはご迷惑をお掛けしましたことを心よりお詫び申し上げます。"; }
#s p:nth-child(2):before {
  content: "改ざんされた期間 : "; }
#s p:nth-child(2):after {
  content: "{$sorry["start_year"]}年{$sorry["start_month"]}月{$sorry["start_day"]}日{$sorry["start_time"]}時ごろから{$sorry["end_year"]}年{$sorry["end_month"]}月{$sorry["end_day"]}日{$sorry["end_time"]}時ごろまで"; }
#s p:nth-child(3):before {
  content: "影響範囲 : "; }
#s p:nth-child(3):after {
  content: "{$affected}"; }
#s p:nth-child(4):before {
  content: "{$is_malware}"; }
#s p:nth-child(5):before {
  content: "{$is_breach}"; }

</style>
<div id="s"><p><p><p><p><p></div>
EOF;
		return $str;
	}
}
$wpSorry = new WpSorry;


