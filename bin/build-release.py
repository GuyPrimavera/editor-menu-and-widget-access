#!/usr/bin/env python3
"""Create a reproducible WordPress.org-ready zip from an explicit release allowlist."""
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo
import argparse

ROOT = Path(__file__).resolve().parents[1]
SLUG = "editor-menu-and-widget-access"
FILES = (
    "LICENSE",
    "readme.txt",
    "messages.pot",
    "editor-menu-widget-access.php",
    "editor-menu-and-widget-access.php",
    "uninstall.php",
    "assets/admin/settings.css",
    "assets/admin/settings.js",
    "assets/admin/site-editor.css",
)
GLOBS = (
    "assets/admin/*.css",
    "assets/admin/*.js",
    "includes/**/*.php",
    "templates/admin/*.php",
    "lang/*.mo",
    "lang/*.po",
)


def build(output: Path) -> None:
    paths = [ROOT / filename for filename in FILES]
    for pattern in GLOBS:
        paths.extend(ROOT.glob(pattern))
    missing = [str(path.relative_to(ROOT)) for path in paths if not path.is_file()]
    if missing:
        raise SystemExit("Missing release files: " + ", ".join(missing))
    paths = sorted(set(paths), key=lambda path: path.relative_to(ROOT).as_posix())
    output.parent.mkdir(parents=True, exist_ok=True)
    with ZipFile(output, "w", ZIP_DEFLATED, compresslevel=9) as archive:
        for path in paths:
            name = f"{SLUG}/{path.relative_to(ROOT).as_posix()}"
            entry = ZipInfo(name, date_time=(2020, 1, 1, 0, 0, 0))
            entry.compress_type = ZIP_DEFLATED
            entry.external_attr = 0o644 << 16
            archive.writestr(entry, path.read_bytes(), compress_type=ZIP_DEFLATED, compresslevel=9)
    print(f"Built {output} with {len(paths)} release files")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=ROOT / "build" / f"{SLUG}.zip")
    build(parser.parse_args().output)
