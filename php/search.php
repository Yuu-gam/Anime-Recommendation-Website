<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<?php $message = $_POST['message'] ?? ""; ?>
<HTML>
	<HEAD>      
		<script language="javascript">
			// 전달받은 메시지 출력
			function showMessage( message )
			{
				if ( ( message != null ) && ( message != "" ) && ( message.substring( 0, 3 ) == " * " )  ) 
				{
					alert( message );
				}
			}     
			// 지정한 url로 이동하는 함수 
			function move( url )	
	 		{
				document.formm.action = url;
				document.formm.submit();
			}
		</script>
	</HEAD>
	<BODY onLoad="showMessage( '<?php echo $message; ?>' );" >
		<!-- logout button -->
		<INPUT TYPE="button" value="로그아웃" onClick="javascript:move('./logout.php');">
		<!-- 화면구성 -->
		<BR><BR><BR> 
		<form name = "formm" method = "post">				
			&nbsp; &nbsp; &nbsp; 
			책 제목 : <INPUT TYPE="text" NAME="message" SIZE="60"> 
		</form>  
		 &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;   
		<INPUT TYPE = "button" value = "책 제목 검색" onClick="javascript:move( './search.php' );">
		<INPUT TYPE = "button" value = "새 책  추가" onClick="javascript:move( './insert.php' );">	
		<INPUT TYPE = "button" value = "책 삭제" onClick="javascript:move( './delete.php' );">	
		<INPUT TYPE = "button" value = "즐겨찾기 목록" onClick="javascript:move('./favorite.php');">
		<BR> <BR> &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
		<BR> <BR>  

		<!-- 책 목록 출력 -->
		<?php include("./selectSQL.php"); ?>
</BODY>
</HTML>
