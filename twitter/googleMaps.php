<?php
//getパラメータから表示する緯度、経度を取得
if($_GET['lati'] != ""){
	$lati = intval(mb_convert_kana($_GET['lati'],"a","UTF-8"));	
	if($lati > 90){
		$lati = 90;	
	}else if($lati < -90){
		$lati = -90;
	}
}else{
	$lati = 0;
}
if($_GET['lngti'] != ""){
	$lngti = intval(mb_convert_kana($_GET['lngti'],"a","UTF-8"));	
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
				zoom: 12,
				mapTypeId: google.maps.MapTypeId.ROADMAP
				//mapTypeId: google.maps.MapTypeId.SATELLITE
			};

			var map = new google.maps.Map(document.getElementById("map_canvas"),
				mapOptions);

			var marker = new google.maps.Marker({
				map: map,
				position: latLng
			});
		}
EOD;

//htmlの出力
print <<<EOD
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<meta http-equiv="Content-Script-Type" content="text/javascript">
		<meta name="author" content="aminami">
		<style type="text/css">
			html { height: 100% }
			body { height: 100%; margin: 0; padding: 0 }
			#map_canvas { height: 100% }
		</style>
		<script type="text/javascript"
			src="http://maps.googleapis.com/maps/api/js?key=AIzaSyDY54x92q9_mCJ5CoSu5eqGMkvYwg35la8&sensor=true">
		</script>
		<script type="text/javascript">
		<!--googleMapsのJavaScript-->
$gMapsJS		
		</script>
	</head>
	<body onload="initialize()">
		<!--地図を表示する場所-->
		<div id="map_canvas" style="width:100%; height:100%"></div>
	</body>
</html>
EOD;
?>
