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
	}
	function add_pages() {
		add_menu_page('謝罪文作成','謝罪文作成',  'level_8', __FILE__, array($this,'wp_sorry_settings'), '', 26);
	}
	function wp_sorry_settings() {?>
<h1>設定</h1>
	<?php 
		if(isset($_POST["wpsorry"])){
			update_option('wpsorry', $_POST["wpsorry"]);?>
			<div class="updated fade"><p><strong>謝罪文の準備ができました</strong></p></div><?php 
		}
		?>
<form action="" method="post">
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
<input type="submit" class="button-primary" value="登録/変更" >
</form>
<?php
	}
	function create_text() {
		$sorry = get_option("wpsorry");
		$is_malware = $this->is_malware($sorry["defaced_malware"]);
		$is_breach = $this->is_breach($sorry["breach"]);
		$affected = esc_html($sorry["affected"]);
		$str = <<< EOF
<p>この度、弊社ウェブサイトに第三者からの不正なアクセスがあり、ウェブサイトが改ざんされていたことがわかりました。ご利用の皆様にはご迷惑をお掛けしましたことを心よりお詫び申し上げます。</p>

<h2>改ざんされた期間</h2>
<p>{$sorry["start_year"]}年{$sorry["start_month"]}月{$sorry["start_day"]}日{$sorry["start_time"]}時ごろから{$sorry["end_year"]}年{$sorry["end_month"]}月{$sorry["end_day"]}日{$sorry["end_time"]}時ごろまで</p>

<h2>影響範囲</h2>
<p>{$affected}</p>

{$is_malware}

{$is_breach}

EOF;
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
}
$wpSorry = new WpSorry;
