<?php
    header('Content-Type: text/html; charset=UTF-8');
    session_start();

    // log, DB 연결 (/home/php/ 기준)
    require_once(__DIR__ . '/log.php');
    include('./SQLconstants.php');

    $conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
        or die("DB 연결에 실패했습니다.");
    mysqli_set_charset($conn, "utf8mb4");

    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    // 입력값 수집
    $name = trim($_GET['anime_name'] ?? '');
    $types = $_GET['anime_type'] ?? [];
    $year = trim((string)($_GET['anime_year'] ?? ''));
    $quarter = trim((string)($_GET['anime_quarter'] ?? ''));
    $adult = $_GET['adult'] ?? '0';
    $adult = ($adult === '1') ? '1' : '0';

    if (!is_array($types)) {
        $types = [];
    }
    $validTypes = array_values(array_filter($types, fn($t) => is_string($t) && $t !== ''));


    // 동적 SQL 조건(WHERE) 및 바인딩 파라미터 구성
    $where = [];
    $params = [];
    $paramTypes = '';

    if ($name !== '') {
        $where[] = "anime_name LIKE ?";
        $params[] = '%' . $name . '%';
        $paramTypes .= 's';
    }

    if (!empty($validTypes)) {
        $placeholders = implode(',', array_fill(0, count($validTypes), '?'));
        $where[] = "anime_type IN ($placeholders)";
        foreach ($validTypes as $type) {
            $params[] = $type;
            $paramTypes .= 's';
        }
    }

    if ($year !== '' && ctype_digit($year)) {
        $where[] = "release_year = ?";
        $params[] = (int)$year;
        $paramTypes .= 'i';
    }

    if ($quarter !== '' && ctype_digit($quarter)) {
        $where[] = "anime_quarter = ?";
        $params[] = (int)$quarter;
        $paramTypes .= 'i';
    }

    if ($adult === '0') {
        $where[] = "adult = 0";
    }


    // 페이지네이션 설정
    $itemsPerPage = 9;
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    

    // 전체 검색 결과 수 계산
    $countQuery = "SELECT COUNT(*) AS total FROM Animes";

    if (!empty($where)) {
        $countQuery .= " WHERE " . implode(' AND ', $where);
    }

    $countStmt = mysqli_prepare($conn, $countQuery);

    if (!empty($params)) {
        mysqli_stmt_bind_param($countStmt, $paramTypes, ...$params);
    }

    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);

    $totalItems = (int)mysqli_fetch_assoc($countResult)['total'];
    $totalPages = max(1, (int)ceil($totalItems / $itemsPerPage));

    mysqli_stmt_close($countStmt);

    // 현재 페이지 보정
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $itemsPerPage;


    // 실제 작품 목록 조회
    $query = "SELECT anime_id, anime_name, anime_image, anime_type, release_year, anime_quarter, adult FROM Animes";

    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }

    $query .= " ORDER BY anime_name LIMIT ? OFFSET ?";

    $stmt = mysqli_prepare($conn, $query);

    $paginationTypes = $paramTypes . 'ii';
    $paginationParams = array_merge($params, [$itemsPerPage, $offset]);

    mysqli_stmt_bind_param($stmt, $paginationTypes, ...$paginationParams);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resultCount = $result ? mysqli_num_rows($result) : 0;
    $hasDetailFilter = !empty($validTypes) || ($year !== '' && ctype_digit($year)) || ($quarter !== '' && ctype_digit($quarter)) || ($adult === '1');



    // 우측 사이드바: 최신 등록 작품 5개
    $recentQuery = "
        SELECT anime_id, anime_name, anime_image, anime_type, release_year
        FROM Animes
        WHERE adult = 0
        ORDER BY anime_id DESC
        LIMIT 5
    ";

    $recentResult = mysqli_query($conn, $recentQuery)
    or die('최신 등록 작품을 불러오지 못했습니다.');


    $searchDetails = sprintf( //로그 용 출력 내용 함수
            "검색어: [%s], 유형: [%s], 년도: [%s], 분기: [%s], 성인 작품: [%s] | 결과 건수: %d건",
            $name !== '' ? $name : '전체',
            !empty($validTypes) ? implode(', ', $validTypes) : '전체',
            $year !== '' ? $year : '전체',
            $quarter !== '' ? $quarter : '전체',
            $adult === '1' ? '포함' : '일반 작품만',
            mysqli_num_rows($result)
        );
    writeLog("검색- user(" . $login_id . ") " . $searchDetails);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Anime Template">
    <meta name="keywords" content="Anime, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Anime | 검색</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="../Frontend/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/plyr.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/style.css" type="text/css">

    <!-- AniDB 스타일 상세 검색 전용 스타일 -->
    <style>
        .anidb-search-box {
            background: #070720;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 6px;
            padding: 22px;
            margin-bottom: 35px;
        }

        .anidb-search-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .anidb-search-bar input[type="text"] {
            flex: 1;
            min-width: 200px;
            height: 46px;
            background: #1d1e39;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 4px;
            padding: 0 16px;
            color: #ffffff;
            font-size: 14px;
        }

        .anidb-search-bar input[type="text"]::placeholder {
            color: #9a9ab0;
        }

        .anidb-search-bar input[type="text"]:focus {
            outline: none;
            border-color: #e53637;
        }

        .btn-filter-toggle {
            height: 46px;
            padding: 0 18px;
            background: #1d1e39;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-filter-toggle:hover,
        .btn-filter-toggle[aria-expanded="true"] {
            border-color: #e53637;
            color: #e53637;
        }

        .btn-search-submit {
            height: 46px;
            padding: 0 26px;
            background: #e53637;
            border: none;
            border-radius: 4px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-search-submit:hover {
            background: #c92a2b;
        }

        .advanced-filter-panel {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filter-group {
            margin-bottom: 18px;
        }

        .filter-group-label {
            display: block;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group-label span {
            color: #9a9ab0;
            font-weight: 400;
            font-size: 12px;
            margin-left: 6px;
        }

        .filter-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-chip {
            margin: 0;
            cursor: pointer;
        }

        .filter-chip input {
            display: none;
        }

        .filter-chip span {
            display: inline-block;
            padding: 6px 13px;
            background: #1d1e39;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            color: #d0d0e0;
            font-size: 13px;
            transition: all 0.2s;
            user-select: none;
        }

        .filter-chip input:checked + span {
            background: #e53637;
            border-color: #e53637;
            color: #ffffff;
            font-weight: 600;
        }

        .filter-chip:hover span {
            border-color: rgba(229, 54, 55, 0.6);
        }

        .year-range-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 320px;
        }

        .year-range-inputs input[type="number"] {
            width: 100%;
            height: 40px;
            background: #1d1e39;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 4px;
            padding: 0 12px;
            color: #ffffff;
            font-size: 13px;
        }

        .year-range-inputs input[type="number"]:focus {
            outline: none;
            border-color: #e53637;
        }

        .advanced-filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .btn-filter-reset {
            height: 40px;
            padding: 0 16px;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 4px;
            color: #b7b7b7;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-filter-reset:hover {
            color: #ffffff;
            border-color: #ffffff;
        }

        .active-filter-summary {
            margin-top: 8px;
            font-size: 13px;
            color: #b7b7b7;
        }

        .active-filter-summary .badge-tag {
            display: inline-block;
            background: rgba(229, 54, 55, 0.18);
            border: 1px solid rgba(229, 54, 55, 0.45);
            color: #ff7b7c;
            padding: 2px 9px;
            border-radius: 12px;
            font-size: 12px;
            margin-right: 5px;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <!-- Page Preloder -->
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

    <!-- Breadcrumb Begin -->
    <div class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__links">
                        <a href="./FB_main.php"><i class="fa fa-home"></i> 홈</a>
                        <span>검색</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Product Section Begin -->
    <section class="product-page spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="product__page__content">

                        <!-- AniDB 스타일 기본 + 상세 검색 영역 Begin -->
                        <div class="anidb-search-box">
                            <form id="detail-search-form" action="./FB_searchSQL.php" method="GET">
                                <!-- 1. 기본 제목 검색줄 -->
                                <div class="anidb-search-bar">
                                    <input type="text" id="detail_anime_name" name="anime_name" value="<?= h($name) ?>" placeholder="작품 제목 또는 키워드를 입력하세요..." autocomplete="off">
                                    <button type="button" id="toggle-filter-btn" class="btn-filter-toggle" data-toggle="collapse" data-target="#advanced-filter-box" aria-expanded="<?= $hasDetailFilter ? 'true' : 'false' ?>" aria-controls="advanced-filter-box">
                                        <i class="fa fa-sliders"></i> 상세 검색
                                    </button>
                                    <button type="submit" class="btn-search-submit">
                                        <i class="fa fa-search"></i> 검색
                                    </button>
                                </div>

                                <!-- 2. 접이식 상세 검색 패널 -->
                                <div class="collapse <?= $hasDetailFilter ? 'show' : '' ?>" id="advanced-filter-box">
                                    <div class="advanced-filter-panel">
                                        <div class="row">
                                            <!-- 작품 유형 ($_GET['anime_type'] 배열로 전송) -->
                                            <div class="col-md-6">
                                                <div class="filter-group">
                                                    <label class="filter-group-label">작품 유형</label>
                                                    <div class="filter-chip-list">
                                                        <label class="filter-chip">
                                                            <input type="checkbox" name="anime_type[]" value="TV Series" <?= in_array('TV Series', $validTypes, true) ? 'checked' : '' ?>>
                                                            <span>TV Series</span>
                                                        </label>
                                                        <label class="filter-chip">
                                                            <input type="checkbox" name="anime_type[]" value="TV Special" <?= in_array('TV Special', $validTypes, true) ? 'checked' : '' ?>>
                                                            <span>TV Special</span>
                                                        </label>
                                                        <label class="filter-chip">
                                                            <input type="checkbox" name="anime_type[]" value="Movie" <?= in_array('Movie', $validTypes, true) ? 'checked' : '' ?>>
                                                            <span>Movie</span>
                                                        </label>
                                                        <label class="filter-chip">
                                                            <input type="checkbox" name="anime_type[]" value="OVA" <?= in_array('OVA', $validTypes, true) ? 'checked' : '' ?>>
                                                            <span>OVA</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 방영 연도 ($_GET['anime_year']) -->
                                        <div class="filter-group" style="margin-bottom: 10px;">
                                            <label class="filter-group-label" for="anime_year">방영 연도</label>
                                            <div class="year-range-inputs">
                                                <input type="number" id="anime_year" name="anime_year" value="<?= h($year) ?>" min="1970" max="2030" placeholder="예: 2015">
                                            </div>
                                        </div>

                                        <!-- 방영 분기 ($_GET['anime_quarter']) -->
                                        <div class="col-md-6">
                                            <div class="filter-group">
                                                <label class="filter-group-label">방영 분기</label>
                                                <div class="filter-chip-list">
                                                    <?php
                                                    $quarterOptions = [
                                                        ''  => '전체 분기',
                                                        '1' => '1분기',
                                                        '2' => '2분기',
                                                        '3' => '3분기',
                                                        '4' => '4분기',
                                                    ];
                                                    foreach ($quarterOptions as $qVal => $qLabel):
                                                        $checked = ($quarter === (string)$qVal) ? 'checked' : '';
                                                    ?>
                                                        <label class="filter-chip">
                                                            <input type="radio" name="anime_quarter" value="<?= h((string)$qVal) ?>" <?= $checked ?>>
                                                            <span><?= h($qLabel) ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 성인 작품 표시($_GET['adult']) -->
                                        <div class="col-md-6">
                                            <div class="filter-group">
                                                <label class="filter-group-label">성인 작품 표시</label>
                                                <div class="filter-chip-list">
                                                    <?php
                                                    $adultOptions = [
                                                        '0'  => '일반 작품만 표시',
                                                        '1'  => '성인 작품 포함',
                                                    ];
                                                    foreach ($adultOptions as $aVal => $aLabel):
                                                        $checked = ($adult === (string)$aVal) ? 'checked' : '';
                                                    ?>
                                                        <label class="filter-chip">
                                                            <input type="radio" name="adult" value="<?= h((string)$aVal) ?>" <?= $checked ?>>
                                                            <span><?= h($aLabel) ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="advanced-filter-actions">
                                            <button type="button" id="reset-filter-btn" class="btn-filter-reset">
                                                <i class="fa fa-rotate-left"></i> 조건 초기화
                                            </button>
                                            <button type="submit" class="btn-search-submit" style="height: 40px; padding: 0 20px;">
                                                상세 조건 적용 검색
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- AniDB 스타일 기본 + 상세 검색 영역 End -->

                        <!-- 검색 결과 타이틀 및 활성 필터 요약 -->
                        <div class="product__page__title">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="section-title">
                                        <h4>
                                            검색 결과: "<span id="search-term" style="color: #e53637;"><?= $name !== '' ? h($name) : '전체' ?></span>"
                                            <span style="font-size: 14px; color: #b7b7b7; margin-left: 8px;">(<?= $totalItems ?>개)</span>
                                        </h4>
                                    </div>
                                    <div id="active-filter-summary" class="active-filter-summary">
                                        <?php if (!empty($validTypes)): ?>
                                            <span class="badge-tag">유형: <?= h(implode(', ', $validTypes)) ?></span>
                                        <?php endif; ?>
                                        <?php if ($year !== '' && ctype_digit($year)): ?>
                                            <span class="badge-tag">연도: <?= h($year) ?>년</span>
                                        <?php endif; ?>
                                        <?php if ($quarter !== '' && ctype_digit($quarter)): ?>
                                            <span class="badge-tag">분기: <?= h($quarter) ?>분기</span>
                                        <?php endif; ?>
                                        <?php if ($adult === '1'): ?> 
                                            <span class="badge-tag">표시: 성인 작품 포함</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 검색 결과 카드 목록 -->
                        <div class="row">
                        <?php if ($resultCount === 0): ?>
                            <div class="col-lg-12">
                                <p style="color: #b7b7b7; padding: 24px 0;">조건에 맞는 작품이 없습니다.</p>
                            </div>
                        <?php else: ?>
                            <?php while ($anime = mysqli_fetch_assoc($result)): ?>
                                <?php
                                    $rawImg = trim((string)($anime['anime_image'] ?? ''));
                                    if ($rawImg === '') {
                                        $imgSrc = '../Frontend/img/trending/trend-1.jpg';
                                    } elseif (preg_match('#^(https?://|/)#i', $rawImg) || str_starts_with($rawImg, '../Frontend/')) {
                                        $imgSrc = $rawImg;
                                    } else {
                                        $imgSrc = '../Frontend/' . ltrim($rawImg, './');
                                    }

                                    $detailUrl = './FB_animeDetail.php?anime_id=' . urlencode((string)$anime['anime_id']);
                                ?>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="product__item">
                                        <div class="product__item__pic">
                                            <img 
                                                class="anime-result-image"
                                                src="<?= h($imgSrc) ?>"
                                                referrerpolicy="no-referrer"
                                                alt="<?= h($anime['anime_name']) ?>">
                                            <?php if (!empty($anime['anime_quarter'])): ?>
                                                <div class="ep"><?= h($anime['anime_quarter']) ?>분기</div>
                                            <?php endif; ?>
                                            <?php if ((int)$anime['adult'] === 1): ?>
                                                <div class="adult-badge">성인</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product__item__text">
                                            <ul>
                                                <?php if (!empty($anime['release_year'])): ?>
                                                    <li><?= h($anime['release_year']) ?>년</li>
                                                <?php endif; ?>
                                                <?php if (!empty($anime['anime_type'])): ?>
                                                    <li><?= h($anime['anime_type']) ?></li>
                                                <?php endif; ?>
                                            </ul>
                                            <h5><a href="<?= h($detailUrl) ?>"><?= h($anime['anime_name']) ?></a></h5>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        </div>
                    </div>

                    <!-- 페이지네이션 -->
                    <div class="product__pagination">

                        <?php if ($currentPage > 1): ?>
                            <a href="?<?= h(http_build_query(array_merge($_GET, ['page' => $currentPage - 1]))) ?>">
                                <i class="fa fa-angle-double-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $pages = [];

                        // 첫 페이지
                        $pages[] = 1;

                        // 현재 페이지 주변
                        for ($i = $currentPage - 3; $i <= $currentPage + 3; $i++) {
                            if ($i > 1 && $i < $totalPages) {
                                $pages[] = $i;
                            }
                        }

                        // 마지막 페이지
                        if ($totalPages > 1) {
                            $pages[] = $totalPages;
                        }

                        $pages = array_unique($pages);
                        sort($pages);

                        $previousPage = 0;

                        foreach ($pages as $page):
                            if ($previousPage > 0 && $page - $previousPage > 1):?>
                                <span class="pagination-dots">...</span>
                        <?php
                            endif;?>

                            <a href="?<?= h(http_build_query(array_merge($_GET, ['page' => $page]))) ?>"
                               class="<?= $page === $currentPage ? 'current-page' : '' ?>">
                                <?= $page ?>
                            </a>

                        <?php
                            $previousPage = $page;
                        endforeach;
                        ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?<?= h(http_build_query(array_merge($_GET, ['page' => $currentPage + 1]))) ?>">
                                <i class="fa fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

                
                <!-- 우측 사이드바: 최신 등록 작품 -->
                <div class="col-lg-4 col-md-6 col-sm-8">
                    <div class="product__sidebar">
                        <div class="product__sidebar__view">

                            <div class="section-title">
                                <h5>최신 등록 작품</h5>
                            </div>

                            <div class="filter__gallery">
                                <?php if (mysqli_num_rows($recentResult) === 0): ?>
                                    <p style="color: #b7b7b7;">
                                        등록된 작품이 없습니다.
                                    </p>
                                <?php else: ?>

                                    <?php while ($recent = mysqli_fetch_assoc($recentResult)): ?>

                                        <?php
                                            $recentImg = trim((string)($recent['anime_image'] ?? ''));

                                            if ($recentImg === '') {
                                                $recentImg = '../Frontend/img/trending/trend-1.jpg';
                                            } elseif (
                                                !preg_match('#^(https?://|/)#i', $recentImg)
                                                && !str_starts_with($recentImg, '../Frontend/')
                                            ) {
                                                $recentImg = '../Frontend/' . ltrim($recentImg, './');
                                            }
                                        ?>

                                        <div class="product__sidebar__view__item set-bg"
                                            data-setbg="<?= h($recentImg) ?>">

                                            <?php if (!empty($recent['release_year'])): ?>
                                                <div class="ep">
                                                    <?= h($recent['release_year']) ?>년
                                                </div>
                                            <?php endif; ?>

                                            <h5>
                                                <a href="./FB_animeDetail.php?anime_id=<?= (int)$recent['anime_id'] ?>">
                                                    <?= h($recent['anime_name']) ?>
                                                </a>
                                            </h5>

                                        </div>

                                    <?php endwhile; ?>

                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Product Section End -->

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

    <!-- Search model Begin (빠른 재검색 가능) -->
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch"><i class="icon_close"></i></div>
            <form action="./FB_searchSQL.php" method="GET" class="search-model-form">
                <input type="text" name="anime_name" id="modal_anime_name" placeholder="작품 제목을 입력하세요..." autocomplete="off">
            </form>
        </div>
    </div>
    <!-- Search model end -->

    <!-- Js Plugins -->
    <script src="../Frontend/js/jquery-3.3.1.min.js"></script>
    <script src="../Frontend/js/bootstrap.min.js"></script>
    <script src="../Frontend/js/player.js"></script>
    <script src="../Frontend/js/jquery.nice-select.min.js"></script>
    <script src="../Frontend/js/mixitup.min.js"></script>
    <script src="../Frontend/js/jquery.slicknav.js"></script>
    <script src="../Frontend/js/owl.carousel.min.js"></script>
    <script src="../Frontend/js/main.js"></script>

    <script>
        (function () {
            const resetBtn = document.getElementById('reset-filter-btn');
            if (!resetBtn) return;

            resetBtn.addEventListener('click', function () {
                const nameInput = document.getElementById('detail_anime_name');
                const yearInput = document.getElementById('anime_year');
                if (nameInput) nameInput.value = '';
                if (yearInput) yearInput.value = '';

                document.querySelectorAll('#advanced-filter-box input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });

                const defaultQuarter = document.querySelector('#advanced-filter-box input[name="anime_quarter"][value=""]');
                if (defaultQuarter) defaultQuarter.checked = true;

                const defaultAdult = document.querySelector('#advanced-filter-box input[name="adult"][value="0"]');
                if (defaultAdult) defaultAdult.checked = true;
            });
        })();
    </script>
</body>

</html>
<?php
    if ($result instanceof mysqli_result) {
        mysqli_free_result($result);
    }

    if ($recentResult instanceof mysqli_result) {
        mysqli_free_result($recentResult);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
?>