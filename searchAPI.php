<?php

//OAUth認証用ライブラリ
require_once("twitteroauth.php");

//関数の定義
function searchTweets($twObj, $options){

	//Search APIを利用し、検索結果のjsonデータを取得
	$json = $twObj->OAuthRequest(
		'https://api.twitter.com/1.1/search/tweets.json',
		'GET',
		$options
	);
	$res_array = json_decode($json, true);

	//取得したツイートの表を生成
	//表のヘッダ
	$twtTableHtml = <<<EOD
<h3>検索結果</h3>
<table border=2 bordercolor="black">
	<tr>
	<th>ユーザ名</th>
	<th>イメージ</th>
	<th>ツイート</th>
	<th>時刻</th>
	<th>場所</th>
	<th>緯度,経度</th>
	</tr>\n
EOD;

	//検索結果の表を生成
	foreach($res_array['statuses'] as $result){
		$name = hEscape($result['user']['name']);
		$screnName = hEscape($result['user']['screen_name']);
		$url = "http://twitter.com/".$screnName;
		$nameHtml = "<a href=\"$url\" target=\"_blank\">$name</a>";

		$tweet = hEscape($result['text']);

		$imgLink = $result['user']['profile_image_url'];
		$imgHtml = "<img src=\"$imgLink\" width=70 heght=70>";

		//時刻の表記を整形
		$updated = hEscape($result['created_at']);
		$timestamp = strtotime($updated);
		$jpTime = date("Y-m-d H:i:s",$timestamp);

		$placeName = hEscape($result['place']['full_name']);
		//placeが存在しない場合はuser_profileの'location'を場所として取得
		if($placeName == ""){
			$placeName = hEscape($result['user']['location']);
		}
		$geoCord = $result['geo']; 
		$lati = hEscape($geoCord['coordinates'][0]); //緯度
		$longti = hEscape($geoCord['coordinates'][1]); //経度

//		$id = hEscape($result['id']);
//		$reply_to = hEscape($result['in_reply_to_screen_name']);

		$twtTableHtml .= <<<EOD
	<tr>
	<td>$nameHtml</td>
	<td>$imgHtml</td>
	<td>$tweet</td>
	<td>$jpTime</td>
	<td>$placeName</td>
	<td>$lati, $longti</td>
	</tr>\n
EOD;
	}
	$twtTableHtml .= <<<EOD
</table>
EOD;

	return $twtTableHtml;
}

//htmlspecialcharsの別名
function hEscape($str){
	$result = htmlspecialchars($str, ENT_QUOTES);
	return $result;
}

//処理の開始
//OAUth認証情報
$consumerKey="gD8M8WAqzDPDrnbw38iUg";
$consumerSecret="";
$accessToken="194930173-UVbTACbN3fHr06H6aag9Zw8P5kMKXgM2F8nFa29q";
$accessTokenSecret="";
 
//twitterオブジェクトの生成
$twObj = new TwitterOAuth(
		$consumerKey,
		$consumerSecret,
		$accessToken,
		$accessTokenSecret
);

//検索ワード
if($_GET['sWord'] != ""){
	$sWord = hEscape($_GET['sWord']);
}else{
	$sWord = "";
}
//取得ツイート数
if($_GET['sCount'] != ""){
	$sCount = intval(mb_convert_kana($_GET['sCount'],"a","UTF-8")); //全角文字を半角に変換
	if($sCount > 100){
		$sCount = 100; //一度に取得できるツイート数の上限(Search APIの仕様)
	}
}else{
	$sCount = 100;
}
//検索対象のツイートの言語
if($_GET['sLang'] != ""){
	$sLang = hEscape($_GET['sLang']);
}else{
	$sLang = "ja"; //デフォルトは日本語
}
//言語指定プルダウンの出力用HTMLを生成
$langPuldwnHtml = <<<EOD
<select name="sLang">\n 
EOD;

if($sLang == "ja"){
	$langPuldwnHtml .= <<<EOD
	<option value="ja" selected>日本語</option>
	<option value="en">英語</option>\n
EOD;

}else{
	$langPuldwnHtml .= <<<EOD
	<option value="ja">日本語</option>
	<option value="en" selected>英語</option>\n
EOD;
}
$langPuldwnHtml .= <<<EOD
</select> 
EOD;

//検索条件の指定
$options = array('q'=>$sWord,'count'=>$sCount,'lang'=>$sLang);//,'geocode'=>$geoCord);

//検索結果のHTMLを取得
$twtTableHtml ="";
if($sWord != ""){
	$twtTableHtml = searchTweets($twObj, $options);
}	 

//出力するhtmlの生成
$html = "";
$html = <<<EOD
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<meta name="author" content="aminami">
	<title>Twitter Search API</title>
</head>
<body>
<h1>Twitter Search API</h1>
<form action="" method="get">
<div>検索ワード:<input type="text" name="sWord" size="20" value="$sWord" maxlength="255"></div>
<div>取得ツイート数:<input type="text" name="sCount" size="3" value="$sCount"></div>
<div>ツイートの言語:
$langPuldwnHtml
</div>
<input type="submit" value="ツイートを検索">
</form>
<!--取得したツイート表-->
$twtTableHtml
</body>
</html>
EOD;

//ページの出力
echo $html;
?>
