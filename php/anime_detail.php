<?php
    header('Content-Type: text/html; charset=UTF-8');
    session_start();

    // Log 및 DB 연결
    require_once(__DIR__ . '/log.php');
    include('./SQLconstants.php');

    $conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
        or die("DB 연결에 실패했습니다.");
    mysqli_set_charset($conn, "utf8mb4");

    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    $animeId = isset($_GET['anime_id']) ? (int)$_GET['anime_id'] : 0;

    if ($animeId <= 0) {
        die("잘못된 접근입니다.");
    }

    // POST 요청 처리 (평점 등록 또는 즐겨찾기 토글 시 로그인 여부 먼저 검사)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 비로그인 상태인 경우
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == "") {
            echo "<script>
                alert('로그인을 해주세요!');
                location.href = './login.php';
            </script>";
            exit;
        }

        $userId = $_SESSION['user_id'];
        $action = $_POST['action'] ?? '';

        //평점
        if ($action === 'update_rating') {
            $rating = (int)($_POST['rating'] ?? 0);
            if ($rating < 0) $rating = 0;
            if ($rating > 10) $rating = 10;

            // History 테이블에 해당 유저/애니메이션 기록이 있는지 확인
            $checkStmt = mysqli_prepare($conn, "SELECT history_id, status FROM History WHERE user_id = ? AND anime_id = ?");
            mysqli_stmt_bind_param($checkStmt, "ii", $userId, $animeId);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);

            if ($row = mysqli_fetch_assoc($checkResult)) {
                // 이미 존재하면 평점 업데이트
                $upStmt = mysqli_prepare($conn, "UPDATE History SET rating = ? WHERE history_id = ?");
                mysqli_stmt_bind_param($upStmt, "di", $rating, $row['history_id']);
                mysqli_stmt_execute($upStmt);
                mysqli_stmt_close($upStmt);
            } else {
                // 없으면 새로 생성 (status 기본값 1 등 지정)
                $insStmt = mysqli_prepare($conn, "INSERT INTO History (user_id, anime_id, rating, status) VALUES (?, ?, ?, 1)");
                mysqli_stmt_bind_param($insStmt, "iid", $userId, $animeId, $rating);
                mysqli_stmt_execute($insStmt);
                mysqli_stmt_close($insStmt);
            }
            mysqli_stmt_close($checkStmt);

            header("Location: anime_detail.php?anime_id=" . $animeId);
            exit;
        }

        //즐겨찾기
        if ($action === 'toggle_favorite') {
            // favorites 테이블에서 해당 유저의 해당 작품 즐겨찾기 여부 확인
            $checkStmt = mysqli_prepare($conn, "SELECT * FROM Favorites WHERE user_id = ? AND anime_id = ?");
            mysqli_stmt_bind_param($checkStmt, "ii", $userId, $animeId);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);

            if (mysqli_num_rows($checkResult) > 0) {
                // 이미 즐겨찾기에 있다면 삭제 (토글 해제)
                $delStmt = mysqli_prepare($conn, "DELETE FROM Favorites WHERE user_id = ? AND anime_id = ?");
                mysqli_stmt_bind_param($delStmt, "ii", $userId, $animeId);
                mysqli_stmt_execute($delStmt);
                mysqli_stmt_close($delStmt);
            } else {
                // 즐겨찾기에 없다면 새로 추가
                $insStmt = mysqli_prepare($conn, "INSERT INTO Favorites (user_id, anime_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($insStmt, "ii", $userId, $animeId);
                mysqli_stmt_execute($insStmt);
                mysqli_stmt_close($insStmt);
            }
            mysqli_stmt_close($checkStmt);

            header("Location: anime_detail.php?anime_id=" . $animeId);
            exit;
        }
    }

    // 1. 작품 정보 조회
    $stmt = mysqli_prepare($conn, "SELECT * FROM Animes WHERE anime_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $animeId);
    mysqli_stmt_execute($stmt);
    $anime = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$anime) {
        die("존재하지 않는 작품입니다.");
    }

    // 로그인 상태인 경우 유저의 평점(History)과 즐겨찾기(favorites) 여부 조회
    $currentRating = 0;
    $isFavorite = false;

    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != "") {
        $userId = $_SESSION['user_id'];
        
        // 1. 평점 조회 (History 테이블)
        $histStmt = mysqli_prepare($conn, "SELECT rating FROM History WHERE user_id = ? AND anime_id = ?");
        mysqli_stmt_bind_param($histStmt, "ii", $userId, $animeId);
        mysqli_stmt_execute($histStmt);
        $history = mysqli_fetch_assoc(mysqli_stmt_get_result($histStmt));
        mysqli_stmt_close($histStmt);

        if ($history) {
            $currentRating = $history['rating'] ?? 0;
        }

        // 2. 즐겨찾기 여부 조회 (favorites 테이블)
        $favStmt = mysqli_prepare($conn, "SELECT * FROM Favorites WHERE user_id = ? AND anime_id = ?");
        mysqli_stmt_bind_param($favStmt, "ii", $userId, $animeId);
        mysqli_stmt_execute($favStmt);
        $favResult = mysqli_stmt_get_result($favStmt);
        
        if (mysqli_num_rows($favResult) > 0) {
            $isFavorite = true;
        }
        mysqli_stmt_close($favStmt);
    }

    mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($anime['anime_name']) ?> - 상세 정보</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #222; max-width: 700px; }
        .detail-card { display: flex; gap: 24px; border: 1px solid #ddd; border-radius: 10px; padding: 20px; align-items: flex-start; }
        .detail-card img { width: 180px; height: auto; border-radius: 6px; object-fit: cover; }
        .info h1 { margin-top: 0; font-size: 24px; display: flex; align-items: center; justify-content: space-between; }
        .muted { color: #666; font-size: 15px; line-height: 1.8; }
        
        /* 즐겨찾기 별 버튼 스타일 */
        .favorite-btn { background: none; border: none; font-size: 28px; cursor: pointer; color: #ccc; }
        .favorite-btn.active { color: #f59e0b; } /* 활성화 시 노란색 별 */

        /* 평점 슬라이더 영역 */
        .rating-section { margin-top: 24px; border-top: 1px solid #eee; padding-top: 16px; }
        .rating-section label { display: block; font-weight: bold; margin-bottom: 8px; }
        .slider-container { display: flex; align-items: center; gap: 12px; }
        input[type="range"] { flex-grow: 1; cursor: pointer; }
        
        .actions { margin-top: 24px; display: flex; gap: 10px; }
        a.button, button.submit-btn { padding: 10px 16px; background: #333; color: white; text-decoration: none; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        a.button { background: #777; }
    </style>
</head>
<body>

<div class="detail-card">
    <?php if (!empty($anime['anime_image'])): ?>
        <img src="<?= h($anime['anime_image']) ?>" alt="<?= h($anime['anime_name']) ?>">
    <?php endif; ?>
    
    <div class="info" style="flex-grow: 1;">
        <h1>
            <?= h($anime['anime_name']) ?>
            <!-- 즐겨찾기(별) 버튼 Form -->
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="toggle_favorite">
                <button type="submit" class="favorite-btn <?= $isFavorite ? 'active' : '' ?>" title="즐겨찾기 토글">
                    ★
                </button>
            </form>
        </h1>
        
        <div class="muted">
            <strong>유형:</strong> <?= h($anime['anime_type']) ?><br>
            <strong>방영년도:</strong> <?= h($anime['release_year']) ?>년<br>
            <strong>방영분기:</strong> <?= h($anime['anime_quarter']) ?>분기<br>
            <strong>시리즈 ID:</strong> <?= h($anime['series_id']) ?>
        </div>

        <!-- 평점 입력 슬라이더 Form -->
        <div class="rating-section">
            <form method="post">
                <input type="hidden" name="action" value="update_rating">
                <label for="ratingRange">내 평점: <span id="ratingValue"><?= (int)$currentRating ?></span>점 (10점 만점)</label>
                <div class="slider-container">
                    <input type="range" id="ratingRange" name="rating" min="0" max="10" value="<?= (int)$currentRating ?>" oninput="document.getElementById('ratingValue').innerText = this.value">
                    <button type="submit" class="submit-btn">평점 저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="actions">
    <a class="button" href="./main.php">메인으로</a>
</div>

</body>
</html>