<?php
	session_start();

	$login_id = $_SESSION['login_id'] ?? "-";

	//log 연결
	require_once(__DIR__ . '/log.php');
	writeLog("로그아웃 - user(" . $login_id . ") 로그아웃 성공");

	session_destroy();

	header("Location: ../Frontend/login.html");
	exit;
?>
