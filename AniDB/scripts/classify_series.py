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

RELATION_FILE = "/home/animeProject/AniDB/data/anime_relations.csv"
CANDIDATE_FILE = "/home/animeProject/AniDB/data/series_candidates.csv"
OUTPUT_FILE = "/home/animeProject/AniDB/data/series_groups_preview.csv"

ALLOWED_RELATIONS = {
    "Sequel",
    "Prequel",
    "Parent Story"
}


def find(parent, x):
    if parent[x] != x:
        parent[x] = find(parent, parent[x])
    return parent[x]


def union(parent, a, b):
    root_a = find(parent, a)
    root_b = find(parent, b)

    if root_a != root_b:
        parent[root_b] = root_a


def main():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    try:
        # 1. DB 작품 조회
        cursor.execute("""
            SELECT anime_id, anidb_id, anime_name, series_id
            FROM Animes
            WHERE anidb_id IS NOT NULL
        """)

        animes = cursor.fetchall()

        anime_map = {
            str(row["anidb_id"]): row
            for row in animes
        }

        # 2. Union-Find 초기화
        parent = {
            aid: aid
            for aid in anime_map
        }

        candidates = []
        seen = set()
        matched = 0
        unmatched = 0

        # 3. 관계 정보 분류 및 DB 매칭
        with open(RELATION_FILE, encoding="utf-8-sig") as f:
            for row in csv.DictReader(f):

                if row["relation_type"] not in ALLOWED_RELATIONS:
                    continue

                a = row["source_anidb_id"]
                b = row["target_anidb_id"]

                if not a or not b or a == b:
                    continue

                # 양방향 관계 중복 제거
                pair = tuple(sorted((a, b)))

                if pair in seen:
                    continue

                seen.add(pair)

                candidates.append([
                    a,
                    b,
                    row["relation_type"],
                    row["target_title"]
                ])

                if a in anime_map and b in anime_map:
                    union(parent, a, b)
                    matched += 1
                else:
                    unmatched += 1

        # 4. 후보 CSV 저장
        with open(CANDIDATE_FILE, "w", newline="", encoding="utf-8-sig") as f:
            writer = csv.writer(f)
            writer.writerow([
                "source_anidb_id",
                "target_anidb_id",
                "relation_type",
                "target_title"
            ])
            writer.writerows(candidates)

        # 5. 연결된 작품 그룹화
        groups = defaultdict(list)

        for aid in anime_map:
            groups[find(parent, aid)].append(aid)

        series_groups = [
            members
            for members in groups.values()
            if len(members) >= 2
        ]

        # 대표 작품을 anime_id가 가장 작은 작품으로 선정
        series_groups.sort(
            key=lambda members: min(
                anime_map[aid]["anime_id"]
                for aid in members
            )
        )

        # 6. 미리보기 CSV 저장
        with open(OUTPUT_FILE, "w", newline="", encoding="utf-8-sig") as f:
            writer = csv.writer(f)
            writer.writerow([
                "group_no",
                "anidb_id",
                "anime_id",
                "anime_name",
                "current_series_id"
            ])

            for group_no, members in enumerate(series_groups, 1):
                members.sort(
                    key=lambda aid: anime_map[aid]["anime_id"]
                )

                for aid in members:
                    anime = anime_map[aid]

                    writer.writerow([
                        group_no,
                        aid,
                        anime["anime_id"],
                        anime["anime_name"],
                        anime["series_id"]
                    ])

        print("\n===== 시리즈 분류 완료 =====")
        print("DB 작품 수:", len(anime_map))
        print("기본 시리즈 후보:", len(candidates))
        print("매칭된 관계:", matched)
        print("매칭되지 않은 관계:", unmatched)
        print("시리즈 그룹 수:", len(series_groups))
        print(
            "시리즈 포함 작품 수:",
            sum(len(g) for g in series_groups)
        )
        print("후보 파일:", CANDIDATE_FILE)
        print("미리보기 파일:", OUTPUT_FILE)

        print("\n===== 상위 20개 그룹 =====")

        for group_no, members in enumerate(series_groups[:20], 1):
            print(f"\n[그룹 {group_no}]")

            for aid in members:
                anime = anime_map[aid]
                print(
                    f"  AniDB {aid} | "
                    f"{anime['anime_name']} | "
                    f"series_id={anime['series_id']}"
                )

    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    main()