
import os
import gzip
import csv
import xml.etree.ElementTree as ET

CACHE_DIR = "/home/animeProject/AniDB/data/cache"
OUTPUT_FILE = "/home/animeProject/AniDB/data/anime_relations.csv"


def main():
    relations = []
    error_count = 0
    xml_count = 0

    for filename in os.listdir(CACHE_DIR):
        if not filename.endswith(".xml"):
            continue

        path = os.path.join(CACHE_DIR, filename)

        try:
            with open(path, "rb") as f:
                data = f.read()

            # gzip 자동 판별
            if data[:2] == b"\x1f\x8b":
                data = gzip.decompress(data)

            root = ET.fromstring(data)
            source_id = root.attrib.get("id", "")

            xml_count += 1

            for anime in root.findall("./relatedanime/anime"):
                relations.append([
                    source_id,
                    anime.attrib.get("id", ""),
                    anime.attrib.get("type", ""),
                    (anime.text or "").strip()
                ])

        except Exception as e:
            error_count += 1
            print(f"[오류] {filename}: {e}")

    # CSV 저장
    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8-sig") as f:
        writer = csv.writer(f)
        writer.writerow([
            "source_anidb_id",
            "target_anidb_id",
            "relation_type",
            "target_title"
        ])
        writer.writerows(relations)

    print("\n===== 시리즈 관계 수집 완료 =====")
    print("읽은 XML:", xml_count)
    print("추출된 관계:", len(relations))
    print("오류 파일:", error_count)
    print("저장 위치:", OUTPUT_FILE)


if __name__ == "__main__":
    main()
