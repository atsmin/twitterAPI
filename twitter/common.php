<?php
//共通して利用する関数
//htmlspecialcharsの別名
function hEscape($str){
	$result = htmlspecialchars($str, ENT_QUOTES);
	return $result;
}
?>
