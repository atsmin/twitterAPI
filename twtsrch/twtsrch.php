<?php
//OAUth認証用ライブラリ
require_once("twitteroauth.php");
//共通関数の読み込み
require_once("common.php");

/*****************************関数の定義*********************************/
//search APIを利用し、検索結果のjsonデータを取得
function searchTweets($twObj, $options){
	$json = $twObj->OAuthRequest(
		'https://api.twitter.com/1.1/search/tweets.json',
		'GET',
		$options
	);
	$twtArray = json_decode($json, true);
	return $twtArray['statuses'];
}

//user_timeline APIを利用し、検索結果のjsonデータを取得
function getUsrTweets($twObj, $options){
	$json = $twObj->OAuthRequest(
		'https://api.twitter.com/1.1/statuses/user_timeline.json',
		'GET',
		$options
	);
	$twtArray = json_decode($json, true);
	if($twtArray[0]['user']['screen_name'] == ""){
		return null;
	}else{
		return $twtArray;
	}
}

//取得したツイートの表を生成
function makeTwtTable($twtArray, $mobileFlag){
	//表のヘッダ
	$twtTableHtml = <<<EOD
<h3><div class="subHeaderText">検索結果</div></h3>
<table class="table table-striped">
	<tr>
	<th>ユーザID</th>
	<th>イメージ</th>
	<th>ツイート</th>
	<th>時刻</th>
	<th>場所</th>
	<th>場所を表示</th>
	</tr>\n
EOD;

	//検索結果の表を生成
	foreach($twtArray as $tweet){
		$name = hEscape($tweet['user']['name']);
		$screnName = hEscape($tweet['user']['screen_name']);
		$url = "http://twitter.com/".$screnName;
		$nameHtml = "<a href=\"$url\" target=\"_blank\">$screnName</a>";

		$text = hEscape($tweet['text']);

		$imgLink = $tweet['user']['profile_image_url'];
		$imgHtml = "<img src=\"$imgLink\" class=\"userImage\">";

		//時刻の表記を整形
		$updated = hEscape($tweet['created_at']);
		$timestamp = strtotime($updated);
		$jpTime = date("Y-m-d H:i:s",$timestamp);

		$placeName = hEscape($tweet['place']['full_name']);
		//placeが存在しない場合はuser_profileの'location'を場所として取得
		if($placeName == ""){
			$placeName = hEscape($tweet['user']['location']);
		}
		$geoCode = $tweet['geo']; 
		$lati = hEscape($geoCode['coordinates'][0]); //緯度
		$lngti = hEscape($geoCode['coordinates'][1]); //経度
		//場所をgoogleMapsで表示する
		if($lati != "" && $lngti != ""){
			//モバイル端末の場合は地図をポップアップにしない
			if($mobileFlag){
				$gMapsLnkHtml = "<a href=\"gmapwindow.php?lati=$lati&lngti=$lngti\" target=\"_blank\">表示</a>";
			}else{
				$gMapsLnkHtml = "<a href=\"gmapwindow.php?lati=$lati&lngti=$lngti\" onclick=\"window.open(this.href,'null','width=400,height=400,menubar=no,toolbar=no,location=no'); return false;\">
				表示</a>";
			}
		}else{
			$gMapsLnkHtml = "";
		}

//	$id = hEscape($tweet['id']);
//	$reply_to = hEscape($tweet['in_reply_to_screen_name']);

		$twtTableHtml .= <<<EOD
	<tr>
	<td>$nameHtml</td>
	<td>$imgHtml</td> <td>$text</td>
	<td>$jpTime</td>
	<td>$placeName</td>
	<td>$gMapsLnkHtml</td>
	</tr>\n
EOD;
	}
	$twtTableHtml .= <<<EOD
</table>
EOD;

	return $twtTableHtml;
}

//検索結果のgoogleMapへのマーカー出力用の配列を生成
function getTwtLatLng($twtArray){
	$latLngArray = array(
		array()
	);
	$count = 0;
	foreach($twtArray as $tweet){
		$geoCode = $tweet['geo']; 
		$lati = hEscape($geoCode['coordinates'][0]); //緯度
		$lngti = hEscape($geoCode['coordinates'][1]); //経度
		//何分前のツイートかを計算
		$twtTime = strtotime(hEscape($tweet['created_at']));
		$now = time();
		$difTimeH = intval(($now - $twtTime) / 3600); 
		$difTimeM = intval(intval(($now - $twtTime) % 3600) / 60); 
		if($difTimeH > 0){
			$difTime = $difTimeH . "時間" . $difTimeM . "分前";
		}else{
			$difTime = $difTimeM . "分前";
		}
		//緯度経度をハッシュに格納する
		if($lati != "" && $lngti != ""){
			$latLngArray[$count]["lat"] = doubleval($lati); 
			$latLngArray[$count]["lng"] = doubleval($lngti); 
			$latLngArray[$count]["difTime"] = $difTime; 
			$count++;
		}
	}
	//javascript側で扱えるようにjson形式にエンコードする
	$latLngJson = json_encode($latLngArray);
	return $latLngJson;
}	

/*****************************処理の開始*********************************/
//OAUth認証情報
$consumerKey="gD8M8WAqzDPDrnbw38iUg";
$consumerSecret="2z7EUUB6ojc7m1SP8wFO01Fvz6HfPuz6zDQElXbxac";
$accessToken="194930173-UVbTACbN3fHr06H6aag9Zw8P5kMKXgM2F8nFa29q";
$accessTokenSecret="qKpGlks7D8sAOICldYaGb4K0HAyY4gr6CKjB7eMdY";
 
//twitterオブジェクトの生成
$twObj = new TwitterOAuth(
	$consumerKey,
	$consumerSecret,
	$accessToken,
	$accessTokenSecret
);

//モバイル端末からのアクセスを識別
$mobileFlag = isMobile($_SERVER['HTTP_USER_AGENT']);

//GETパラメータの取得
//検索ワード
if($_GET['sWord'] != ""){
	$sWord = hEscape(mb_convert_encoding($_GET['sWord'],"UTF-8","auto"));
}else{
	$sWord = "";
}
//ツイート検索場所(緯度)
if($_GET['sLati'] != ""){
	$sLati = doubleval(mb_convert_kana($_GET['sLati'],"a","UTF-8"));	
	if($sLati > 90){
		$sLati = 90;	
	}else if($sLati < -90){
		$sLati = -90;
	}
}else{
	$sLati = "";
}
//ツイート検索場所(経度)
if($_GET['sLngt'] != ""){
	$sLngt = doubleval(mb_convert_kana($_GET['sLngt'],"a","UTF-8"));	
	if($sLngt > 180){
		$sLngt = 180;	
	}else if($sLngt < -180){
		$sLngt = -180;
	}
}else{
	$sLngt = "";
}
//ツイート検索場所(半径)
if($_GET['sRad'] != ""){
	$sRad = intval(mb_convert_kana($_GET['sRad'],"a","UTF-8"));	
	if($sRad > 1000){
		$sRad = 1000;  //半径の最大値	
	}else if($sRad < 1){
		$sRad = 1;  //半径の最小値
	}
}else{
	$sRad = "1";
}
//緯度経度をツイート検索の書式へ変換
if($sLati != "" && $sLngt != ""){
	$lmi = "$sRad" . "km"; //ツイート取得範囲をkmで指定
	$sLatLngt = $sLati . "," . $sLngt . "," . $lmi;
}else{
	$sLatLngt = "";
}
//取得ツイート数
if($_GET['sCount'] != ""){
	$sCount = intval(mb_convert_kana($_GET['sCount'],"a","UTF-8")); //全角文字を半角に変換
	if($sCount > 100){
		$sCount = 100; //一度に取得できるツイート数の上限(Search APIの仕様)
	}
}else{
	$sCount = 50;
}
//検索対象のツイートの言語
if($_GET['sLang'] != ""){
	$sLang = hEscape($_GET['sLang']);
}else{
	$sLang = "ja"; //デフォルトは日本語
}
//ユーザIDでの検索用フラグ
if($_GET['sUsrNameFlg'] != ""){
	$sUsrNameFlg = 1;
}else{
	$sUsrNameFlg = 0;
}

//言語指定プルダウンの出力用HTMLを生成
$langPuldwnHtml = <<<EOD
<select class="form-control" name="sLang">\n 
EOD;
if($sLang == "ja"){
	$langPuldwnHtml .= <<<EOD
	<option value="ja" selected>日本語</option>
	<option value="en">English</option>\n
EOD;
}else{
	$langPuldwnHtml .= <<<EOD
	<option value="ja">日本語</option>
	<option value="en" selected>English</option>\n
EOD;
}
$langPuldwnHtml .= <<<EOD
</select> 
EOD;

//ユーザIDで検索するチェックボックスのHtmlを生成
$sUsrChkboxHtml = '<label class="checkbox-inline">';
if($sUsrNameFlg){
	$sUsrChkboxHtml .= <<<EOD
<input type="checkbox" name="sUsrNameFlg" value=1 maxlength="255" onclick="chgLatlngForm()" checked="checked">
EOD;
}else{
	$sUsrChkboxHtml .= <<<EOD
<input type="checkbox" name="sUsrNameFlg" value=1 maxlength="255" onclick="chgLatlngForm()">
EOD;
}
$sUsrChkboxHtml .= 'ユーザIDで検索</label>';

//ユーザIDで検索する
if($sUsrNameFlg){
	$sUsrName = $sWord;
	//検索条件の指定
	$options = array('screen_name'=>$sUsrName,'count'=>$sCount,'include_rts'=>'true','contributor_details'=>'true');
	$twtTableHtml ="";
	if($sUsrName != ""){
		$twtArray = getUsrTweets($twObj, $options);
		$twtTableHtml = makeTwtTable($twtArray, $mobileFlag);
		//googleMapでのツイート位置表示に利用
		$twtLatLngJson = getTwtLatLng($twtArray);
	}else{
		$twtLatLngJson = json_encode(array(array()));
	}
//ツイート又は緯度経度で検索する
}else{
	//検索条件の指定
	$options = array('q'=>$sWord,'count'=>$sCount,'lang'=>$sLang,'geocode'=>$sLatLngt);
	//検索結果のHTMLを取得
	$twtTableHtml ="";
	if($sWord != "" || $sLatLngt != ""){
		$twtArray = searchTweets($twObj, $options);
		$twtTableHtml = makeTwtTable($twtArray, $mobileFlag);
		//googleMapでのツイート位置表示に利用
		$twtLatLngJson = getTwtLatLng($twtArray);
	}else{
		$twtLatLngJson = json_encode(array(array()));
	}
}

//googleMapsのJavascriptを生成
$gMapsJS = "";
$gZoom = 15;
if($sLati != "" && $sLngt != ""){
	$gLat = $sLati;
	$gLnt = $sLngt;
	$markerFlg = 1;
//ユーザIDが指定されている場合は直近のツイート場所を中心にする
}else if($sUsrNameFlg && $twtLatLngJson != "[[]]"){
	$twtLatLngArray = json_decode($twtLatLngJson,true);
	$gLat = $twtLatLngArray[0]["lat"];
	$gLnt = $twtLatLngArray[0]["lng"];
	$markerFlg = 0;
}else{
	//緯度経度が設定されていないときのデフォルト値(東京駅)
	$gLat = 35.681382;
	$gLnt = 139.766084;
	$gZoom = 3;
	$markerFlg = 0;
}
$gMapsJS = <<<EOD
	var geocoder;
	var map;
	var marker;
	var markerFlg = $markerFlg;
	var sUsrNameFlg = $sUsrNameFlg;

	function initialize() {
		geocoder = new google.maps.Geocoder();
		var latlng = new google.maps.LatLng($gLat,$gLnt);
		var mapOptions = {
			zoom: $gZoom,
			center: latlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);

		//緯度経度が設定されていない場合はマーカー非表示
		if(markerFlg){
			//Centerのマーカーを表示
			setCenterMarker(latlng);	
		}
		//ユーザIDが指定されている場合はツイートのマーカーのみ表示
		if(markerFlg || sUsrNameFlg){
			//Twitterのマーカーを表示
			var latLngJson = $twtLatLngJson;
			var length = latLngJson.length;
			var count;

			//直近のツイートが手前になるように配列の後ろ側から表示
			for(count = length-1; count >= 0 ; count--){
				var latlng = new google.maps.LatLng(latLngJson[count]["lat"],latLngJson[count]["lng"]);
				var difTime = latLngJson[count]["difTime"];
				setTwitterMarker(latlng, difTime);	
			}
		}
		
		//クリックした場所の緯度経度を取得するイベントハンドラ
		google.maps.event.addListener(map,'click',function(event){
			if(getLatLngFlg){
				var lat = event.latLng.lat();
				var lng = event.latLng.lng();
				var latlng = new google.maps.LatLng(lat,lng);

				document.getElementsByName("sLati")[0].value = lat; 
				document.getElementsByName("sLngt")[0].value = lng; 
				map.panTo(latlng);
				setCenterMarker(latlng);	
			}		
		});

		//ユーザID指定がオンのとき緯度経度フォームを無効にする
		chgLatlngForm();
	}
EOD;

/*****************************htmlの出力*********************************/
print <<<EOD
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<!-- スマートフォン向けviewportの指定 -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0">	
	<meta name="author" content="(c) aminami 2013">

	<title>Tweet Searcher</title>
	<link href="./style.css" rel="stylesheet" type="text/css">

	<!--jQuery-->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

	<!--bootstrap-->
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css">
	<!-- Optional theme -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap-theme.min.css">
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>

	<!--googleMapsAPIの取得-->
	<script type="text/javascript"
		src="http://maps.googleapis.com/maps/api/js?key=AIzaSyDY54x92q9_mCJ5CoSu5eqGMkvYwg35la8&sensor=true">
	</script>
	<script>
	//クリックで緯度経度取得モードの切り替え用フラグ
	var getLatLngFlg = false;
	var zoomUp = 15;

	//googleMapsの表示
	$gMapsJS	
	</script>
	<script type="text/javascript" src="./twtsrch.js"></script>
</head>
<body onload="initialize()" background="./img/congruent_pentagon.png">
<h1><div class="headerText">Tweet Searcher</div></h1>
<form id="mainform" action="" method="get">
<div class="form-inline">
緯度,経度をワード検索(GoogleGeocoding):<input type="text" class="form-control" id="sAdrs" size="20" value="$sAdrs" maxlength="255" onChange="codeAddress()" placeholder="※地名、住所など">
</div>
<!--<input type="button" class="btn btn-info" value="検索" onclick="codeAddress()"></div>-->
<div class="form-inline">地図をクリックして緯度,経度を取得:
<input type="button" class="btn btn-info" id="getFlgChng" value="開始" onclick="getFlgChg()">
<input type="button" class="btn btn-info" value="(GPS)現在位置を取得" onclick="getLocation()">
</div>
<div class="form-inline">ツイートの言語:
$langPuldwnHtml
取得ツイート数:<input type="text" class="form-control" name="sCount" size="3" value="$sCount">
</div>
<div class="form-inline">ツイート検索場所:
緯度:<input type="text" class="form-control" name="sLati" size="10" value="$sLati" maxlength="255">
経度:<input type="text" class="form-control" name="sLngt" size="10" value="$sLngt" maxlength="255">
半径:<input type="text" class="form-control" name="sRad" size="3" value="$sRad" maxlength="255">km
</div>
<div class="form-group">
<div class="form-inline">
検索ワード:<input type="text" class="form-control" name="sWord" value="$sWord" maxlength="255" placeholder="※省略可">
$sUsrChkboxHtml
</div></div>
<div>
<input type="button" class="btn btn-info" value="リセット" onclick="formReset()"><input type="button" class="btn btn-info" value="ツイートを検索" onclick="submitForm()">
</div>
</form>
<div id="map_canvas"></div>
<!--取得したツイート表-->
$twtTableHtml
</body>
</html>
EOD;
?>
