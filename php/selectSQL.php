<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<?php 
	session_start();

	// 이전 페이지에서 전달 받은 메시지 확인(이 메세지는 echo의 message아님. search의 message)
	$message =  $_POST['message'];
	$message = ( ( ( $message == null ) || ( $message == "" ) || ( strncmp( $message, " * ", 3 ) == 0 ) ) ? "_%" : $message );

	//log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');
	$conn = mysqli_connect($mySQL_host,$mySQL_id,$mySQL_password,$mySQL_database) or die ("Can't access DB");
    mysqli_set_charset($conn, "utf8mb4"); 

	// MySQL 책 검색 실행 및 결과 출력
	$query = "select * from book where title like '%".$message."%';";
	$resultSet = mysqli_query( $conn, $query );
	
	while( $result = mysqli_fetch_array( $resultSet ) )
	{
		echo "\n<BR>ID : ".$result['id'];
		echo "\n<BR>책제목 : ".$result['title'];
		echo "\n<BR>저자 : ".$result['author'];
		echo "\n<BR>출판사 : ".$result['publisher'];
		echo "\n<BR>출판일 : ".$result['date'];
		echo "\n<BR><img src = '".$result['image']."' height='280' width='180'> <br>";
		echo "\n<BR><a href = './favoriteSQL.php?book_id=".$result['id']."'>[즐겨찾기]</a><BR>";
	}

	writeLog("키워드 (" . $message . ") 로 작품 검색");

	// MySQL 드라이버 연결 해제
	mysqli_free_result( $resultSet );
	mysqli_close( $conn );
?>
