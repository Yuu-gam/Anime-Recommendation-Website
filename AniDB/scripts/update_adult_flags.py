
import csv
import os
import mysql.connector
from pathlib import Path

CSV_FILE = Path(
    "/home/animeProject/AniDB/data/adult_candidates.csv"
)

DB_CONFIG = {
    "host": "localhost",
    "user": os.environ["DB_USER"],
    "password": os.environ["DB_PASSWORD"],
    "database": "AnimationInformation",
}

# 처음에는 False로 실행해서 업데이트 대상만 확인
APPLY_CHANGES = True


def main():
    adult_ids = set()

    with open(CSV_FILE, encoding="utf-8-sig", newline="") as f:
        reader = csv.DictReader(f)

        for row in reader:
            anidb_id = row.get("anidb_id", "").strip()

            if anidb_id.isdigit():
                adult_ids.add(int(anidb_id))

    if not adult_ids:
        print("CSV에 업데이트할 작품 ID가 없습니다.")
        return

    conn = mysql.connector.connect(**DB_CONFIG)

    try:
        cursor = conn.cursor()

        placeholders = ",".join(["%s"] * len(adult_ids))
        params = tuple(sorted(adult_ids))

        # DB에 존재하는 작품 확인
        cursor.execute(
            f"""
            SELECT anime_id, anime_name, anidb_id, adult
            FROM Animes
            WHERE anidb_id IN ({placeholders})
            ORDER BY anidb_id
            """,
            params
        )

        matched = cursor.fetchall()

        print(f"CSV 성인 후보: {len(adult_ids)}개")
        print(f"DB에서 찾은 작품: {len(matched)}개")
        print(f"DB에서 찾지 못한 작품: {len(adult_ids) - len(matched)}개")

        to_update = [row for row in matched if row[3] != 1]

        print(f"adult=1로 변경할 작품: {len(to_update)}개")

        if not APPLY_CHANGES:
            print("\n[미리보기] DB는 변경하지 않았습니다.")
            return

        cursor.execute(
            f"""
            UPDATE Animes
            SET adult = 1
            WHERE anidb_id IN ({placeholders})
            """,
            params
        )

        conn.commit()

        print(f"\n업데이트 완료: {cursor.rowcount}개")

    except Exception:
        conn.rollback()
        raise

    finally:
        conn.close()


if __name__ == "__main__":
    main()
