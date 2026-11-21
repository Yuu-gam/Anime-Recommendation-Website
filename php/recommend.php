<?php
    header('Content-Type: text/html; charset=UTF-8');
    session_start();

    $login_id = $_SESSION['login_id'] ?? '-';

    // Log, DB 연결
    require_once(__DIR__ . '/log.php'); //로그 출력할 만한 내용이 없어서 안적었는데 혹시 필요한 것 같으면 바로 쓸 수 있게 넣어둠ㅎㅎ
    include('./SQLconstants.php');

    //사용자 시청 목록(평점)에 따라 작품 추천
    function getRecommendationsByTypeAndSeries($conn, $user_id) {
        //사용자가 평점을 남긴 작품들의 유형(anime_type)과 시리즈(seires_id) 조회
        $prefQuery = "SELECT DISTINCT a.anime_type, a.seires_id 
                    FROM History h 
                    JOIN Animes a ON h.anime_id = a.anime_id 
                    WHERE h.user_id = ? AND h.rating IS NOT NULL";
    
        $stmt = mysqli_prepare($conn, $prefQuery);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    
        $types = [];
        $seriesIds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['anime_type']) && !in_array($row['anime_type'], $types)) {
                $types[] = $row['anime_type'];
            }
            if (!empty($row['seires_id']) && !in_array($row['seires_id'], $seriesIds)) {
                $seriesIds[] = $row['seires_id'];
            }
        }
        mysqli_stmt_close($stmt);

        // 평가한 기록(유형이나 시리즈)이 전혀 없다면 전체 중에서 무작위 추천
        if (empty($types) && empty($seriesIds)) {
            $fallbackQuery = "SELECT * FROM Animes ORDER BY RAND() LIMIT 5";
            return mysqli_query($conn, $fallbackQuery);
        }

        // 2. 동적 SQL 조건 구성 (유형 조건 OR 시리즈 조건)
        $whereClauses = [];
        $params = [];
        $paramTypes = '';

        // 유형(IN) 조건 추가
        if (!empty($types)) {
            $typePlaceholders = implode(',', array_fill(0, count($types), '?'));
            $whereClauses[] = "anime_type IN ($typePlaceholders)";
            foreach ($types as $t) {
                $params[] = $t;
                $paramTypes .= 's';
            }
        }

        // 시리즈(IN) 조건 추가
        if (!empty($seriesIds)) {
            $seriesPlaceholders = implode(',', array_fill(0, count($seriesIds), '?'));
            $whereClauses[] = "seires_id IN ($seriesPlaceholders)";
            foreach ($seriesIds as $s) {
                $params[] = $s;
                $paramTypes .= 'i';
            }
        }

        $conditionSql = '(' . implode(' OR ', $whereClauses) . ')';

        //최종 추천 쿼리 (선호 유형 또는 시리즈에 속하며, 내가 아직 시청하지 않은 작품)
        $recQuery = "SELECT * FROM Animes 
                    WHERE $conditionSql 
                    AND anime_id NOT IN (SELECT anime_id FROM History WHERE user_id = ?)
                    ORDER BY RAND() LIMIT 5";
    
        $stmtRec = mysqli_prepare($conn, $recQuery);
    
        // 마지막 바인딩을 위한 user_id 추가
        $params[] = $user_id;
        $paramTypes .= 'i';

        // bind_param 동적 호출 준비
        $bindParams = [$stmtRec, $paramTypes];
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        call_user_func_array('mysqli_stmt_bind_param', $bindParams);
    
        mysqli_stmt_execute($stmtRec);
        $recResult = mysqli_stmt_get_result($stmtRec);
        mysqli_stmt_close($stmtRec);

        return $recResult;
    }

    //같은 나잇대(예: 10대, 20대 등 10단위 연령대) 유저들의 즐겨찾기 목록 기반 추천
    function getRecommendationsByAgeGroup($conn, $user_id) {
        // 현재 사용자의 나이(age) 조회
        $ageQuery = "SELECT age FROM Users WHERE user_id = ?";
        $stmtAge = mysqli_prepare($conn, $ageQuery);
        mysqli_stmt_bind_param($stmtAge, "i", $user_id);
        mysqli_stmt_execute($stmtAge);
        $ageRes = mysqli_stmt_get_result($stmtAge);
        $userAgeRow = mysqli_fetch_assoc($ageRes);
        mysqli_stmt_close($stmtAge);

        if (!$userAgeRow || !isset($userAgeRow['age'])) {
            // 나이 정보가 없으면 일반 인기/무작위 추천 반환
            return mysqli_query($conn, "SELECT * FROM Animes ORDER BY RAND() LIMIT 5");
        }

        $userAge = (int)$userAgeRow['age'];
        // 10대, 20대 등 대략적인 연령대 계산 (예: 25세 -> 20대 범위 [20, 29] 또는 floor($userAge / 10) * 10)
        $minAge = floor($userAge / 10) * 10;
        $maxAge = $minAge + 9;

        // 같은 나잇대 유저들이 즐겨찾기한 작품 중 내가 아직 즐겨찾기하지 않은 작품 무작위 추천
        $query = "SELECT DISTINCT a.* 
                FROM favorites f
                JOIN Users u ON f.user_id = u.user_id
                JOIN Animes a ON f.anime_id = a.anime_id
                WHERE u.age BETWEEN ? AND ? 
                AND f.user_id != ?
                AND f.anime_id NOT IN (SELECT anime_id FROM favorites WHERE user_id = ?)
                ORDER BY RAND() LIMIT 5";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiii", $minAge, $maxAge, $user_id, $user_id);
        mysqli_stmt_execute($stmt, $queryRes);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        return $result;
    }
?>