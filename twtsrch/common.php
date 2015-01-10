<?php
//共通して利用する関数
//htmlspecialcharsの別名
function hEscape($str){
	$result = htmlspecialchars($str, ENT_QUOTES);
	return $result;
}

//useragentの情報からモバイル端末を識別する
function isMobile($userAgent){
	//モバイル端末
	if (strpos($userAgent,'iPhone') || strpos($userAgent,'iPad') ||strpos($userAgent,'Android')){
		return true;
	} else {
		return false;
	}
}
?>
