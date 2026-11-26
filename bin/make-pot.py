#!/usr/bin/env python3
"""Extract WordPress strings with a gettext date header accepted by translation editors."""
from datetime import datetime, timezone
from pathlib import Path
import argparse
import json
import subprocess

ROOT = Path(__file__).resolve().parents[1]

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=ROOT / "messages.pot")
    args = parser.parse_args()
    headers = {"POT-Creation-Date": datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M%z")}
    subprocess.run(
        [
            "wp", "i18n", "make-pot", str(ROOT), str(args.output.resolve()),
            "--include=editor-menu-widget-access.php,editor-menu-and-widget-access.php,uninstall.php,includes,templates,assets/admin/settings.js",
            "--domain=editor-menu-and-widget-access", "--headers=" + json.dumps(headers),
        ],
        cwd=ROOT,
        check=True,
    )
