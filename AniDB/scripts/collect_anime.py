
import gzip
import time
import os
import urllib.parse
import urllib.request
import urllib.error
import xml.etree.ElementTree as ET
from datetime import datetime

import mysql.connector

BASE_DIR = "/home/animeProject/AniDB/data"
ID_FILE = os.path.join(BASE_DIR, "korean-anime-ids.txt")
FAILED_ID_FILE = os.path.join(BASE_DIR, "failed-anime-ids.txt")
CACHE_DIR = os.path.join(BASE_DIR, "cache")

CLIENT = "webproject"
CLIENT_VERSION = "1"
PROTO_VERSION = "1"

API_URL = "http://api.anidb.net:9001/httpapi"
IMAGE_BASE_URL = "https://cdn.anidb.net/images/main/"
REQUEST_INTERVAL = 5.0

DB_CONFIG = {
    "host": "localhost",
    "user": os.environ["DB_USER"],
    "password": os.environ["DB_PASSWORD"],
    "database": "AnimationInformation"
}

os.makedirs(CACHE_DIR, exist_ok=True)

last_request_time = 0


class AniDBBannedError(Exception):
    """AniDB API가 클라이언트를 차단했을 때 발생하는 예외."""
    pass


def read_xml(path):
    with open(path, "rb") as f:
        data = f.read()

    if data.startswith(b"\x1f\x8b"):
        data = gzip.decompress(data)

    return ET.fromstring(data)


def fetch_anime(aid):
    global last_request_time

    cache_path = os.path.join(CACHE_DIR, f"anime_{aid}.xml")

    if os.path.exists(cache_path):
        print(f"[CACHE] AniDB ID {aid}")
        return read_xml(cache_path)

    elapsed = time.monotonic() - last_request_time

    if elapsed < REQUEST_INTERVAL:
        time.sleep(REQUEST_INTERVAL - elapsed)

    params = {
        "request": "anime",
        "client": CLIENT,
        "clientver": CLIENT_VERSION,
        "protover": PROTO_VERSION,
        "aid": aid
    }

    url = API_URL + "?" + urllib.parse.urlencode(params)

    request = urllib.request.Request(
        url,
        headers={"User-Agent": "AnimeRecommendationWebsite/1.0"}
    )

    print(f"[API] AniDB ID {aid} 요청 중...")
    last_request_time = time.monotonic()

    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            data = response.read()

    except urllib.error.HTTPError as e:
        data = e.read()

        if data.startswith(b"\x1f\x8b"):
            data = gzip.decompress(data)

        error_text = data.decode("utf-8", errors="replace")

        if "banned" in error_text.lower():
            raise AniDBBannedError(
                f"AniDB API 차단 응답 (HTTP {e.code}): {error_text[:300]}"
            )

        print(f"[ERROR] AniDB ID {aid}: HTTP {e.code} - {error_text[:300]}")
        return None

    except Exception as e:
        print(f"[ERROR] AniDB ID {aid}: {e}")
        return None

    if data.startswith(b"\x1f\x8b"):
        data = gzip.decompress(data)

    try:
        root = ET.fromstring(data)
    except ET.ParseError as e:
        error_text = data.decode("utf-8", errors="replace")

        if "banned" in error_text.lower():
            raise AniDBBannedError(
                f"AniDB API 차단 응답: {error_text[:300]}"
            )

        print(f"[ERROR] AniDB ID {aid}: XML 파싱 실패 - {e}")
        return None

    if root.tag == "error":
        error_text = "".join(root.itertext()).strip()

        if "banned" in error_text.lower():
            raise AniDBBannedError(f"AniDB API 차단 응답: {error_text}")

        print(f"[ERROR] AniDB ID {aid}: AniDB API 오류 - {error_text}")
        return None

    with open(cache_path, "wb") as f:
        f.write(data)

    return root


def record_failed_id(aid, reason):
    with open(FAILED_ID_FILE, "a", encoding="utf-8") as f:
        f.write(f"{aid}\t{reason}\n")

    print(f"[FAILED] AniDB ID {aid}: {reason}")


def select_korean_title(root):
    priority = {
        "official": 1,
        "main": 2,
        "synonym": 3
    }

    candidates = []

    for title in root.findall("./titles/title"):
        lang = title.get(
            "{http://www.w3.org/XML/1998/namespace}lang"
        )
        title_type = title.get("type")

        if lang == "ko" and title_type in priority:
            if title.text and title.text.strip():
                candidates.append(
                    (priority[title_type], title.text.strip())
                )

    if not candidates:
        return None

    candidates.sort(key=lambda x: x[0])
    return candidates[0][1]


def parse_anime(root):
    aid = int(root.get("id"))
    name = select_korean_title(root)

    if not name:
        return None

    startdate = root.findtext("startdate")

    release_year = None
    anime_quarter = None

    if startdate:
        try:
            date = datetime.strptime(startdate, "%Y-%m-%d")
            release_year = date.year
            anime_quarter = (date.month - 1) // 3 + 1
        except ValueError:
            pass

    anime_type = root.findtext("type")

    if not anime_type:
        print(f"[SKIP] {aid}: 애니메이션 유형 없음")
        return None

    picture = root.findtext("picture")

    anime_image = (
        IMAGE_BASE_URL + picture.strip()
        if picture and picture.strip()
        else None
    )

    return {
        "anidb_id": aid,
        "anime_name": name,
        "anime_image": anime_image,
        "release_year": release_year,
        "anime_quarter": anime_quarter,
        "anime_type": anime_type
    }


def insert_anime(cursor, anime):
    sql = """
        INSERT INTO Animes (
            anime_name,
            anime_image,
            release_year,
            anime_quarter,
            anime_type,
            series_id,
            anidb_id
        )
        VALUES (%s, %s, %s, %s, %s, NULL, %s)
        ON DUPLICATE KEY UPDATE
            anidb_id = anidb_id
    """

    values = (
        anime["anime_name"],
        anime["anime_image"],
        anime["release_year"],
        anime["anime_quarter"],
        anime["anime_type"],
        anime["anidb_id"]
    )

    cursor.execute(sql, values)

    if cursor.rowcount == 1:
        print(f"[INSERT] {anime['anime_name']}")
    else:
        print(f"[SKIP] 이미 등록된 AniDB ID: {anime['anidb_id']}")


def main():
    with open(ID_FILE, encoding="utf-8") as f:
        ids = [int(line.strip()) for line in f if line.strip()]

    # AniDB ID 774에서 차단이 발생했으므로, 해당 ID부터 다시 확인한다.
    # 이미 캐시가 있거나 DB에 등록된 ID는 기존 로직에서 재사용/중복 방지된다.
    target_ids = [aid for aid in ids if aid >= 774]

    connection = None
    cursor = None

    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        cursor = connection.cursor()

        print(f"수집 대상: {len(target_ids)}개")
        print("-" * 50)

        banned_stop = False

        for aid in target_ids:
            try:
                root = fetch_anime(aid)

                if root is None:
                    record_failed_id(aid, "API 요청 실패")
                    continue

                anime = parse_anime(root)

            except AniDBBannedError as e:
                record_failed_id(aid, f"AniDB BANNED - 수집 중단: {e}")
                print("\n[STOP] AniDB 차단 응답을 받아 수집을 즉시 중단합니다.")
                print("차단이 해제되었는지 확인하기 전까지 다시 실행하지 마세요.")
                banned_stop = True
                break

            except Exception as e:
                record_failed_id(aid, str(e))
                continue

            if anime is None:
                print(f"[SKIP] {aid}: 한국어 제목 없음")
                continue

            print("\n수집 결과")

            for key, value in anime.items():
                print(f"{key}: {value}")

            insert_anime(cursor, anime)
            connection.commit()

            print("-" * 50)

        if banned_stop:
            print("\n차단으로 인해 수집을 중단했습니다. 정상 완료가 아닙니다.")
        else:
            print("\n수집 완료!")

    except Exception as e:
        if connection and connection.is_connected():
            connection.rollback()

        print(f"[DB ERROR] {e}")

    finally:
        if cursor:
            cursor.close()

        if connection and connection.is_connected():
            connection.close()


if __name__ == "__main__":
    main()
