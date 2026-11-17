<?php 
	function writeLog(string $message)
	{
		//시간대 설정
		date_default_timezone_set('Asia/Seoul');

		// 로그 파일 이름
		$logFileName = '/var/log/animeTest/log.txt'; //log.txt 파일 위치
		$logFile = fopen( $logFileName, "a" );
		if( $logFile )
		{
			if (session_status() === PHP_SESSION_NONE) {
            	session_start();
        	}
			// 접속 정보
			$loginId = $_SESSION['login_id'] ?? '-';
			$sessionId = hash('sha256', session_id());
			$uri = $_SERVER['PHP_SELF'] ?? '-';
			$previous = $_SERVER['HTTP_REFERER'] ?? '-';
			$browser = $_SERVER['HTTP_USER_AGENT'] ?? '-';

        	$message = str_replace(["\r", "\n", "\t"], ' ', $message);	//줄바꿈

			// 로그 데이터 출력
			$logData = "\nTime:\t" . date('Y-m-d H:i:s')	//요청 발생 시간
				. "\tloginID:\t" . $loginId			//요청한 user ID
				. "\tSessionID:\t" . $sessionId 	//요청한 세션 ID
				. "\tURI:\t" . $uri 				//요청한 php 파일 경로
				. "\tPrevious:\t" . $previous 		//이전 페이지 경로
				. "\tBrowser:\t" . $browser			//요청한 브라우저 정보
				. "\tMessage:\t" . $message;		//로그 메시지 (직접 지정한 것)
			
				// 로그 파일에 저장
			if (flock($logFile, LOCK_EX)) {
				fwrite($logFile, $logData);
				flock($logFile, LOCK_UN);
			}
        	fclose( $logFile );
		}
		else
		{
        	error_log("로그 파일 열기 실패: " . $logFileName);
		}
	}
