import os
import csv
import mysql.connector
from collections import defaultdict

DB_CONFIG = {
    "host": "localhost",
    "user": os.environ["DB_USER"],
    "password": os.environ["DB_PASSWORD"],
    "database": "AnimationInformation"
}

INPUT_FILE = "/home/animeProject/AniDB/data/series_groups_preview.csv"

# False: 미리보기
# True: 실제 DB 반영
APPLY_CHANGES = True


def main():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    try:
        # 1. 그룹 정보 읽기
        groups = defaultdict(list)

        with open(INPUT_FILE, encoding="utf-8-sig") as f:
            for row in csv.DictReader(f):
                groups[row["group_no"]].append({
                    "anime_id": int(row["anime_id"]),
                    "anime_name": row["anime_name"],
                    "anidb_id": row["anidb_id"]
                })

        created_count = 0
        skipped_count = 0

        for group_no, members in groups.items():

            # 2. 대표 작품 선정
            members.sort(key=lambda x: x["anime_id"])

            representative = members[0]
            series_name = representative["anime_name"]

            anime_ids = [m["anime_id"] for m in members]
            placeholders = ",".join(["%s"] * len(anime_ids))

            # 3. 현재 DB 상태 재확인
            cursor.execute(
                f"""
                SELECT anime_id, series_id
                FROM Animes
                WHERE anime_id IN ({placeholders})
                """,
                anime_ids
            )

            db_rows = cursor.fetchall()

            if len(db_rows) != len(members):
                print(f"[건너뜀] 그룹 {group_no}: 작품 수 불일치")
                skipped_count += 1
                continue

            existing_ids = {
                row["series_id"]
                for row in db_rows
                if row["series_id"] is not None
            }

            # 기존 시리즈 연결은 덮어쓰지 않음
            if existing_ids:
                print(
                    f"[건너뜀] 그룹 {group_no}: "
                    f"기존 series_id {existing_ids}"
                )
                skipped_count += 1
                continue

            print(f"\n[그룹 {group_no}] {series_name}")

            for member in members:
                print(" -", member["anime_name"])

            if APPLY_CHANGES:
                # 4. Series 등록
                cursor.execute(
                    """
                    INSERT INTO Series (series_name)
                    VALUES (%s)
                    """,
                    (series_name,)
                )

                series_id = cursor.lastrowid

                # 5. Animes 연결
                cursor.execute(
                    f"""
                    UPDATE Animes
                    SET series_id = %s
                    WHERE anime_id IN ({placeholders})
                      AND series_id IS NULL
                    """,
                    [series_id] + anime_ids
                )

                if cursor.rowcount != len(members):
                    raise RuntimeError(
                        f"그룹 {group_no} 업데이트 수 불일치"
                    )

                print(" => series_id:", series_id)

            created_count += 1

        if APPLY_CHANGES:
            conn.commit()
            print("\nDB 반영 완료!")
        else:
            conn.rollback()
            print("\n미리보기 완료. DB는 변경되지 않았습니다.")

        print("처리 대상 그룹:", created_count)
        print("건너뛴 그룹:", skipped_count)

    except Exception:
        conn.rollback()
        raise

    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    main()