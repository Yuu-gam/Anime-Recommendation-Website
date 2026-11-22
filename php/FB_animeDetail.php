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
            $rating = (float)($_POST['rating'] ?? 0);
            $rating = max(0, min(5, $rating));
            $rating = round($rating * 2) / 2;

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

            header("Location: FB_animeDetail.php?anime_id=" . $animeId);
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

            header("Location: FB_animeDetail.php?anime_id=" . $animeId);
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

    // 추천 목록 조회
    $recommendList = [];

    if (!empty($_SESSION['user_id'])) {
        require_once(__DIR__ . '/recommend.php');

        $userId = (int)$_SESSION['user_id'];

        // 1. 사용자 평점 기반 추천
        $ratingResult = getRecommendationsByTypeAndSeries(
            $conn,
            $userId
        );
        if ($ratingResult) {
            while ($row = mysqli_fetch_assoc($ratingResult)) {
                $recommendList[$row['anime_id']] = $row;
            }
        }

        // 2. 같은 연령대 사용자의 즐겨찾기 기반 추천
        $ageResult = getRecommendationsByAgeGroup(
            $conn,
            $userId
        );
        if ($ageResult) {
            while ($row = mysqli_fetch_assoc($ageResult)) {
                $recommendList[$row['anime_id']] = $row;
            }
        }

        // 현재 보고 있는 작품은 추천에서 제외
        unset($recommendList[$animeId]);

        // 최대 5개 출력
        $recommendList = array_slice(
            $recommendList,
            0,
            5,
            true
        );
    }

    mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo h($anime['anime_name']); ?> | 작품 상세</title>

    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../Frontend/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/plyr.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/style.css" type="text/css">
</head>

<body>

    <div id="preloder">
        <div class="loader"></div>
    </div>

    <!-- Header Section Begin -->
    <header class="header">
        <div class="container">
            <div class="row">
                <div class="col-lg-2">
                    <div class="header__logo">
                        <a href="./FB_main.php">
                            <img src="../Frontend/img/logo.png" alt="Logo">
                        </a>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="header__nav">
                        <nav class="header__menu mobile-menu">
                            <ul>
                                <li class="active">
                                    <a href="./FB_main.php">홈</a>
                                </li>
                                <li>
                                    <a href="./FB_searchSQL.php">검색</a>
                                </li>
                                <?php if (!empty($_SESSION['user_id'])): ?>
                                    <li>
                                        <a href="./FB_favorites.php">
                                            <?= h($_SESSION['login_id'] ?? '사용자') ?> 님
                                        </a>
                                        <ul class="dropdown">
                                            <li><a href="./FB_favorites.php">마이페이지</a></li>
                                            <li><a href="./logout.php">로그아웃</a></li>
                                        </ul>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a href="../Frontend/login.html">
                                            로그인 / 마이페이지
                                            <span class="arrow_carrot-down"></span>
                                        </a>
                                        <ul class="dropdown">
                                            <li><a href="../Frontend/login.html">로그인</a></li>
                                            <li><a href="./FB_signup.php">회원가입</a></li>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="header__right">
                        <a href="#" class="search-switch" title="빠른 검색">
                            <span class="icon_search"></span>
                        </a>
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <a href="./FB_favorites.php" title="마이페이지">
                                <span class="icon_profile"></span>
                            </a>
                        <?php else: ?>
                            <a href="../Frontend/login.html" title="로그인">
                                <span class="icon_profile"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="mobile-menu-wrap"></div>
        </div>
    </header>
    <!-- Header End -->

    <!-- Breadcrumb -->
    <div class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__links">
                        <a href="./FB_main.php">
                            <i class="fa fa-home"></i> 홈
                        </a>
                        <span>작품 상세</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Anime Details -->
    <section class="anime-details spad">
        <div class="container">
            <div class="anime__details__content">
                <div class="row">

                    <!-- Anime Image -->
                    <div class="col-lg-3">
                        <div class="anime__details__pic">
                            <img
                                class="anime-detail-image"
                                src="<?= h($anime['anime_image']) ?>"
                                referrerpolicy="no-referrer"
                                alt="<?= h($anime['anime_name']) ?>"
                            >
                        </div>
                    </div>

                    <!-- Anime Information -->
                    <div class="col-lg-9">
                        <div class="anime__details__text">

                            <div class="anime__details__title">
                                <h3><?php echo h($anime['anime_name']); ?></h3>
                            </div>

                            <div class="anime__details__rating">
                                <div class="rating">
                                    <?php
                                    $rating = (float)($currentRating ?? 0);

                                    for ($i = 1; $i <= 5; $i++):
                                        if ($rating >= $i):
                                            $starClass = 'fa-star';
                                        elseif ($rating >= $i - 0.5):
                                            $starClass = 'fa-star-half-o';
                                        else:
                                            $starClass = 'fa-star-o';
                                        endif;
                                    ?>
                                        <i class="fa <?= $starClass ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span>
                                    내 평점:
                                    <?php echo $rating > 0 ? $rating . ' / 5' : '아직 평가하지 않았습니다.'; ?>
                                </span>
                            </div>

                            <div class="anime__details__widget">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <ul>
                                            <li>
                                                <span>작품 유형:</span>
                                                <?php echo h($anime['anime_type']); ?>
                                            </li>
                                            <li>
                                                <span>방영 연도:</span>
                                                <?php echo h($anime['release_year'] ?? '정보 없음'); ?>
                                            </li>
                                            <li>
                                                <span>방영 분기:</span>
                                                <?php
                                                if (!empty($anime['anime_quarter'])) {
                                                    echo h($anime['anime_quarter']) . '분기';
                                                } else {
                                                    echo '정보 없음';
                                                }
                                                ?>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Favorite Button -->
                            <div class="anime__details__btn">
                                <form method="POST"
                                    action="FB_animeDetail.php?anime_id=<?php echo (int)$animeId; ?>"
                                    style="display:inline-block;">
                                    <input type="hidden" name="action" value="toggle_favorite">
                                    <button type="submit" class="follow-btn">
                                        <?php if ($isFavorite): ?>
                                            <i class="fa fa-star"></i> 즐겨찾기 해제
                                        <?php else: ?>
                                            <i class="fa fa-star-o"></i> 즐겨찾기 추가
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <a href="./FB_favorites.php" class="watch-btn">
                                    <span>즐겨찾기 목록</span>
                                    <i class="fa fa-angle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating Form -->
            <div class="row">
                <div class="col-lg-8 col-md-8">
                    <div class="anime__details__form">
                        <div class="section-title">
                            <h5>작품 평점 등록</h5>
                        </div>
                        <form method="POST" action="FB_animeDetail.php?anime_id=<?php echo (int)$animeId; ?>">
                            <input type="hidden" name="action" value="update_rating">
                            <div class="rating-picker" id="ratingPicker">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="rating-pick-item">
                                        <i class="fa fa-star-o" data-index="<?= $i ?>"></i>

                                        <button type="button" class="rating-hit left" data-value="<?= $i - 0.5 ?>"></button>
                                        <button type="button" class="rating-hit right" data-value="<?= $i ?>"></button>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden"
                                   name="rating"
                                   id="ratingValue"
                                   value="<?= h($currentRating ?? 0) ?>">                           
                            <button type="submit">평점 등록</button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar: 추천 작품 -->
                <div class="col-lg-4 col-md-4">
                    <div class="anime__details__sidebar">
                        <div class="section-title">
                            <h5>비슷한 추천 작품</h5>
                        </div>
                        <?php if (empty($_SESSION['user_id'])): ?>
                            <p>로그인하면 맞춤 작품을 추천받을 수 있습니다.</p>
                        <?php elseif (empty($recommendList)): ?>
                            <p>아직 추천할 작품이 없습니다.</p>
                        <?php else: ?>
                            <?php foreach ($recommendList as $recommend): ?>
                                <div
                                    class="product__sidebar__view__item set-bg"
                                    data-setbg="<?= h($recommend['anime_image']) ?>">
                                    <h5>
                                        <a href="./FB_animeDetail.php?anime_id=<?= (int)$recommend['anime_id'] ?>">
                                            <?= h($recommend['anime_name']) ?>
                                        </a>
                                    </h5>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section Begin -->
    <footer class="footer">
        <div class="page-up">
            <a href="#" id="scrollToTopButton"><span class="arrow_carrot-up"></span></a>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="footer__logo">
                        <a href="./FB_main.php"><img src="../Frontend/img/logo.png" alt="Logo"></a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="footer__nav">
                        <ul>
                            <li><a href="./FB_main.php">홈</a></li>
                            <li><a href="./FB_searchSQL.php">검색</a></li>
                            <li>
                                <?php if (!empty($_SESSION['user_id'])): ?>
                                    <a href="./FB_favorites.php">마이페이지</a>
                                <?php else: ?>
                                    <a href="../Frontend/login.html">로그인</a>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3">
                    <p>
                        Copyright &copy;
                        <script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="fa fa-heart" aria-hidden="true"></i> by <a href="https://ahmedmaher-portfolio.vercel.app/" target="_blank">Ahmed Maher</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer Section End -->

    <!-- Search Model -->
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">
                <i class="icon_close"></i>
            </div>

            <form action="./FB_searchSQL.php"
                method="GET"
                class="search-model-form">

                <input
                    type="text"
                    name="anime_name"
                    id="search-input"
                    placeholder="작품 제목을 입력하세요..."
                    autocomplete="off"
                >
            </form>
        </div>
    </div>

    <!-- JS Plugins -->
    <script src="../Frontend/js/jquery-3.3.1.min.js"></script>
    <script src="../Frontend/js/bootstrap.min.js"></script>
    <script src="../Frontend/js/player.js"></script>
    <script src="../Frontend/js/jquery.nice-select.min.js"></script>
    <script src="../Frontend/js/mixitup.min.js"></script>
    <script src="../Frontend/js/jquery.slicknav.js"></script>
    <script src="../Frontend/js/owl.carousel.min.js"></script>
    <script src="../Frontend/js/main.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ratingInput = document.getElementById('ratingValue');
        const ratingPicker = document.getElementById('ratingPicker');

        if (!ratingInput || !ratingPicker) return;

        const stars = ratingPicker.querySelectorAll('[data-index]');
        const buttons = ratingPicker.querySelectorAll('.rating-hit');

        function updateRating(value) {
            value = Math.max(0, Math.min(5, value));
            value = Math.round(value * 2) / 2;

            ratingInput.value = value.toFixed(1);

            stars.forEach(function (star) {
                const index = Number(star.dataset.index);

                star.classList.remove(
                    'fa-star',
                    'fa-star-half-o',
                    'fa-star-o',
                    'full',
                    'half'
                );

                if (value >= index) {
                    star.classList.add('fa-star', 'full');
                } else if (value >= index - 0.5) {
                    star.classList.add('fa-star-half-o', 'half');
                } else {
                    star.classList.add('fa-star-o');
                }
            });
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                const selectedValue = Number(this.dataset.value);

                console.log('선택한 점수:', selectedValue);

                updateRating(selectedValue);
            });
        });

        // 페이지 로딩 시 기존에 저장된 평점을 별에 표시
        updateRating(Number(ratingInput.value) || 0);
    });
    </script>
</body>
</html>
