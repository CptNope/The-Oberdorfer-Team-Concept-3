#!/usr/bin/env python3
"""Smaller copies of each site photo for srcset: assets/img/photo/<name>-640.webp and -960.webp.

Run after adding or replacing a photo in assets/img/photo/, then commit the copies (CI has no image
library, so tools/build_wp.py only copies them). The theme's oberdorfer_photo_srcset() adds them
to any <img> that shows the original, so phones download a fraction of the full image.
"""
import os
from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PHOTO_DIR = os.path.join(ROOT, "assets", "img", "photo")
WIDTHS = (640, 960)


def main():
    for name in sorted(os.listdir(PHOTO_DIR)):
        if not name.endswith(".webp") or any(name.endswith(f"-{w}.webp") for w in WIDTHS):
            continue
        with Image.open(os.path.join(PHOTO_DIR, name)) as im:
            for w in WIDTHS:
                out = os.path.join(PHOTO_DIR, name[:-5] + f"-{w}.webp")
                if im.width <= w * 1.2:
                    if os.path.exists(out):
                        os.remove(out)
                    continue
                im.resize((w, round(im.height * w / im.width)), Image.LANCZOS).save(out, "WEBP", quality=78, method=6)
                print("wrote", os.path.relpath(out, ROOT))


if __name__ == "__main__":
    main()
