//住所を緯度経度に変換するgoogleGeocodeのJavascript
function codeAddress() {
		var address = $("#sAdrs")[0].value;
		geocoder.geocode( { 'address': address},
			function(results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					//緯度経度を取得
					var latlng = results[0].geometry.location;
					$("[name='sLati']")[0].value = latlng.lat();
					$("[name='sLngt']")[0].value = latlng.lng();
					
					map.panTo(latlng);
					setCenterMarker(latlng);	
					map.setZoom(zoomUp);

				}else {
					alert("Geocode was not successful for the following reason: " + status);
				}
			});
		//緯度経度を指定する場合ユーザ名で検索のチェックをはずす
		$("[name='sUsrNameFlg']")[0].checked = "";
		chgLatlngForm();
}

//googleMapのCenterマーカーを表示、マーカーの移動
function setCenterMarker(latlng){
		if(typeof(centerMarker) == 'undefined'){
				centerMarker = new google.maps.Marker({
				map: map,
				position: latlng,
				icon: 'http://chart.apis.google.com/chart?chst=d_map_pin_icon_withshadow&chld=star|ff4500',
				animation: google.maps.Animation.BOUNCE,
			});
		}else{
			centerMarker.setPosition(latlng); 
			centerMarker.setAnimation(google.maps.Animation.BOUNCE); 
		}
}

//googleMapのTwitterマーカーを表示
function setTwitterMarker(latlng, difTime){
		marker = new google.maps.Marker({
			map: map,
			position: latlng,
			icon: 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.4|0|b0e0e6|15|_|',
			title: difTime,
			animation: google.maps.Animation.DROP,
		});
}

//現在位置の緯度経度を取得(Geolocation API:HTML5)
function getLocation(){
		if(navigator.geolocation){
			var option = {
				enableHighAccuracy: true
			};
			navigator.geolocation.getCurrentPosition(setPosition,null,option);
		}else{
			alert("Geolocation is not supported by this browser");
		}
		//緯度経度を指定する場合ユーザ名で検索のチェックをはずす
		$("[name='sUsrNameFlg']")[0].checked = "";
		chgLatlngForm();
}
//getCurrentPositionに渡すCallback関数
function setPosition(position){
		var lat = position.coords.latitude;
		var lng = position.coords.longitude;
		var latlng = new google.maps.LatLng(lat,lng,false);

		$("[name='sLati']")[0].value = lat;
		$("[name='sLngt']")[0].value = lng;
		$("#sAdrs")[0].value = "";

		map.panTo(latlng);
		setCenterMarker(latlng);
		map.setZoom(zoomUp);
}

//クリックで緯度経度取得モードの切り替え
function getFlgChg(){
		if(getLatLngFlg) {
			getLatLngFlg = false;
			$("#getFlgChng")[0].value = "開始";
		}else{
			getLatLngFlg = true;
			$("#getFlgChng")[0].value = "停止";
			//緯度経度を指定する場合ユーザ名で検索のチェックをはずす
			$("[name='sUsrNameFlg']")[0].checked = "";
			chgLatlngForm();
		}	
}

//ユーザIDで検索する場合、緯度経度のフォームを無効にする
function chgLatlngForm(){
		if($("[name='sUsrNameFlg']")[0].checked){	
			$("[name='sLati']")[0].disabled = "true"; 
			$("[name='sLngt']")[0].disabled = "true"; 
			$("[name='sRad']")[0].disabled = "true"; 
			$("[name='sLang']")[0].disabled = "true"; 
		}else{
			$("[name='sLati']")[0].disabled = ""; 
			$("[name='sLngt']")[0].disabled = ""; 
			$("[name='sRad']")[0].disabled = ""; 
			$("[name='sLang']")[0].disabled = ""; 
		}
}

//リセットボタン
function formReset(){
	$("[name='sWord']")[0].value = ""; 
	$("[name='sLati']")[0].value = ""; 
	$("[name='sLngt']")[0].value = ""; 
	$("[name='sRad']")[0].value = ""; 
	$("[name='sCount']")[0].value = ""; 
	$("[name='sLang']")[0].options[$("[name='sLang']")[0].selectedIndex].value = ""; 
	$("[name='sUsrNameFlg']")[0].checked = "";
	$("#mainform").submit();		
}

function submitForm(){
	$("#mainform").submit();		
}
