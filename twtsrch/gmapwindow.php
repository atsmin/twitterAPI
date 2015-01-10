<?php
//getパラメータから表示する緯度、経度を取得
if($_GET['lati'] != ""){
	$lati = doubleval(mb_convert_kana($_GET['lati'],"a","UTF-8"));	
	if($lati > 90){
		$lati = 90;	
	}else if($lati < -90){
		$lati = -90;
	}
}else{
	$lati = 0;
}
if($_GET['lngti'] != ""){
	$lngti = doubleval(mb_convert_kana($_GET['lngti'],"a","UTF-8"));	
	if($lngti > 180){
		$lngti = 180;	
	}else if($lngti < -180){
		$lngti = -180;
	}
}else{
	$lngti = 0;
}

//googleMapsのjavascriptを生成
$gMapsJS = "";
$gMapsJS = <<<EOD
		function initialize() {
			var latLng = new google.maps.LatLng($lati, $lngti);

			var mapOptions = {
				center: latLng, 
				zoom: 14,
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				overviewMapControl: true,
				overviewMapControlOptions:{
					opened: true
				}
			};

			var map = new google.maps.Map(document.getElementById("map_canvas"),
				mapOptions);

			var marker = new google.maps.Marker({
				map: map,
				position: latLng,
				title: 'クリックで拡大',
				animation: google.maps.Animation.BOUNCE,
				icon: 'http://chart.apis.google.com/chart?chst=d_map_pin_icon_withshadow&chld=glyphish_note|b0e0e6'
			});

			google.maps.event.addListener(marker,'click',function(){
				map.setZoom(18);
			});
		}
EOD;

//モバイル端末からのアクセスを識別し、設定を変更するjavascript
$dtctBrowsJS = "";
$dtctBrowsJS = <<<EOD
		function detectBrowser() {
			var useragent = navigator.userAgent;
			var mapdiv = document.getElementById("map_canvas");

			if (useragent.indexOf('iPhone') != -1 || useragent.indexOf('iPad') != -1 || useragent.indexOf('Android') != -1) {
				mapdiv.style.width = '100%';
				mapdiv.style.height = '100%';
			}		
		}
EOD;

//htmlの出力
print <<<EOD
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<meta http-equiv="Content-Script-Type" content="text/javascript">
		<meta name="author" content="(c) aminami 2013">
		<!-- スマートフォン向けviewportの指定 -->
		<meta name="viewport" content="initial-scale=1.0;user-scalable=no" />	
		<style type="text/css">
			html { height: 100% }
			body { height: 100%; margin: 0; padding: 0 }
			#map_canvas { height: 100% }
		</style>
		<script type="text/javascript"
			src="http://maps.googleapis.com/maps/api/js?key=AIzaSyDY54x92q9_mCJ5CoSu5eqGMkvYwg35la8&sensor=true">
		</script>
		<script type="text/javascript">
		<!--
		//googleMapsのJavaScript
$gMapsJS		
		//モバイル端末識別用のJavaScript
$dtctBrowsJS
		-->
		</script>
	</head>
	<body onload="initialize();detectBrowser()">
		<!--地図を表示する場所-->
		<div id="map_canvas" style="width:100%; height:100%"></div>
	</body>
</html>
EOD;
?>
