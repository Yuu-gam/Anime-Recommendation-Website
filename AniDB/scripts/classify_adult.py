
import csv
import gzip
import re
import xml.etree.ElementTree as ET
from pathlib import Path

# AniDB XML 캐시 폴더
CACHE_DIR = Path("/home/animeProject/AniDB/data/cache")

# 결과 CSV
OUTPUT_FILE = Path("/home/animeProject/AniDB/data/adult_candidates.csv")

XML_LANG = "{http://www.w3.org/XML/1998/namespace}lang"


def read_xml(xml_path):
    """일반 XML과 gzip 압축 XML 모두 읽기"""
    with open(xml_path, "rb") as f:
        data = f.read()

    if data.startswith(b"\x1f\x8b"):
        data = gzip.decompress(data)

    return ET.fromstring(data)


def get_title(root, lang):
    """지정 언어의 제목 가져오기"""
    titles = root.find("titles")

    if titles is None:
        return ""

    for title in titles.findall("title"):
        if title.get(XML_LANG) == lang:
            return (title.text or "").strip()

    return ""


def get_main_title(root):
    titles = root.find("titles")

    if titles is None:
        return ""

    for title in titles.findall("title"):
        if title.get("type") == "main":
            return (title.text or "").strip()

    return ""


def get_text(root, tag):
    element = root.find(tag)

    if element is None:
        return ""

    return " ".join("".join(element.itertext()).split())


def main():
    results = []
    total = 0
    errors = 0

    for xml_path in CACHE_DIR.glob("anime_*.xml"):
        match = re.fullmatch(r"anime_(\d+)\.xml", xml_path.name)

        if not match:
            continue

        total += 1
        anidb_id = int(match.group(1))

        try:
            root = read_xml(xml_path)

            # restricted=true인 작품만 추출
            restricted = root.get("restricted", "").lower()

            if restricted != "true":
                continue

            korean_title = get_title(root, "ko")
            english_title = get_title(root, "en")
            main_title = get_main_title(root)

            results.append({
                "anidb_id": anidb_id,
                "korean_title": korean_title,
                "english_title": english_title,
                "main_title": main_title,
                "anime_type": get_text(root, "type"),
                "startdate": get_text(root, "startdate"),
                "description": get_text(root, "description"),
                "restricted": restricted,
            })

        except Exception as e:
            errors += 1
            print(f"[오류] {xml_path.name}: {e}")

    results.sort(key=lambda x: x["anidb_id"])

    OUTPUT_FILE.parent.mkdir(parents=True, exist_ok=True)

    fieldnames = [
        "anidb_id",
        "korean_title",
        "english_title",
        "main_title",
        "anime_type",
        "startdate",
        "description",
        "restricted",
    ]

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8-sig") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(results)

    print("\n===== 추출 결과 =====")
    print(f"검사한 XML 파일: {total}개")
    print(f"restricted=true 작품: {len(results)}개")
    print(f"오류 파일: {errors}개")
    print(f"CSV 저장 위치: {OUTPUT_FILE}")


if __name__ == "__main__":
    main()
