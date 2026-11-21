<?php
// 데이터베이스 연결 및 세션이 이미 시작되어 있다고 가정합니다.

/**
 * 1. 사용자가 시청/평가한 작품과 유사한 유형(anime_type)의 작품 중 무작위 추천
 */
function getRecommendationsByType($conn, $user_id) {
    // 사용자가 평점을 남긴 작품들의 유형(anime_type) 조회
    $typeQuery = "SELECT DISTINCT a.anime_type 
                  FROM History h 
                  JOIN Animes a ON h.anime_id = a.anime_id 
                  WHERE h.user_id = ? AND h.rating IS NOT NULL";
    
    $stmt = mysqli_prepare($conn, $typeQuery);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $types = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $types[] = $row['anime_type'];
    }
    mysqli_stmt_close($stmt);

    if (empty($types)) {
        // 평가한 기록이 없으면 전체 중에서 무작위 추천
        $fallbackQuery = "SELECT * FROM Animes ORDER BY RAND() LIMIT 5";
        return mysqli_query($conn, $fallbackQuery);
    }

    // 해당 유형들에 속하면서 아직 시청/평가하지 않은 작품 중 무작위 추천
    $placeholders = implode(',', array_fill(0, count($types), '?'));
    $recQuery = "SELECT * FROM Animes 
                 WHERE anime_type IN ($placeholders) 
                 AND anime_id NOT IN (SELECT anime_id FROM History WHERE user_id = ?)
                 ORDER BY RAND() LIMIT 5";
    
    $stmtRec = mysqli_prepare($conn, $recQuery);
    
    // 파라미터 동적 바인딩 (유형 타입들 + user_id)
    $types[] = $user_id;
    $typesStr = str_repeat('s', count($types) - 1) . 'i';
    
    // bind_param 호출을 위한 참조 배열 생성
    $bindParams = [$stmtRec, $typesStr];
    foreach ($types as $key => $value) {
        $bindParams[] = &$types[$key];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bindParams);
    
    mysqli_stmt_execute($stmtRec);
    $recResult = mysqli_stmt_get_result($stmtRec);
    mysqli_stmt_close($stmtRec);

    return $recResult;
}

/**
 * 2. 같은 나잇대(예: 10대, 20대 등 10단위 연령대) 유저들의 즐겨찾기 목록 기반 추천
 */
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