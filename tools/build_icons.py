"""Render the favicon and app-icon set from the logo SVGs.

    python3 tools/build_icons.py

Needs Python Playwright + Chromium (the same setup as tools/screenshot.py). Run it after
tools/build_logos.py, then run tools/build_coming_soon.py to copy the icons into the theme.

Writes to assets/logo/:
  favicon.ico            16, 32 and 48 px, from favicon.svg (browsers, Google results)
  favicon-32.png         32 px, from favicon.svg
  apple-touch-icon.png   180 px, from social-avatar.svg (iOS home screen; iOS rounds the corners)
  icon-192.png           192 px, from social-avatar.svg (Android, web app manifest)
  icon-512.png           512 px, from social-avatar.svg (manifest; also safe as a maskable icon:
                         the monogram frame sits inside the central 80% circle)
  social-avatar.png      800 px, from social-avatar.svg (Open Graph image, schema logo)
  site.webmanifest       name, colors and the icons above

Each PNG is checked after rendering: it must be mostly navy (#071D2D). An earlier export
captured the browser's broken-image placeholder instead of the artwork; the check stops
that from happening again.
"""
import asyncio, base64, io, json, os, sys
from PIL import Image
from playwright.async_api import async_playwright

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
LOGO = os.path.join(ROOT, "assets", "logo")
NAVY = (0x07, 0x1D, 0x2D)

PNGS = [  # (source svg, output png, size)
    ("favicon.svg", "favicon-16.png", 16),
    ("favicon.svg", "favicon-32.png", 32),
    ("favicon.svg", "favicon-48.png", 48),
    ("social-avatar.svg", "apple-touch-icon.png", 180),
    ("social-avatar.svg", "icon-192.png", 192),
    ("social-avatar.svg", "icon-512.png", 512),
    ("social-avatar.svg", "social-avatar.png", 800),
]


def check(png_bytes, name):
    im = Image.open(io.BytesIO(png_bytes)).convert("RGB")
    raw = im.tobytes()
    px = [tuple(raw[i:i + 3]) for i in range(0, len(raw), 3)]
    navy = sum(1 for p in px if all(abs(a - b) <= 6 for a, b in zip(p, NAVY)))
    if navy / len(px) < 0.5:
        sys.exit(f"{name}: only {navy / len(px):.0%} navy pixels; the SVG did not render")
    return im


async def render():
    out = {}
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(device_scale_factor=1)
        for src, name, size in PNGS:
            svg = open(os.path.join(LOGO, src), "rb").read()
            uri = "data:image/svg+xml;base64," + base64.b64encode(svg).decode()
            await page.set_viewport_size({"width": size, "height": size})
            await page.set_content(
                f'<html><body style="margin:0;background:#fff">'
                f'<img id="i" src="{uri}" width="{size}" height="{size}" style="display:block"></body></html>')
            ok = await page.evaluate("""() => new Promise(res => { const i = document.getElementById('i');
                if (i.complete) return res(i.naturalWidth > 0);
                i.onload = () => res(true); i.onerror = () => res(false); })""")
            if not ok:
                sys.exit(f"{src} failed to load")
            png = await page.screenshot(clip={"x": 0, "y": 0, "width": size, "height": size})
            out[name] = check(png, name)
        await browser.close()
    return out


def main():
    ims = asyncio.run(render())
    for name, im in ims.items():
        if name.startswith("favicon-") and name != "favicon-32.png":
            continue  # 16 and 48 only go into the .ico
        im.save(os.path.join(LOGO, name), optimize=True)
    ims["favicon-48.png"].save(os.path.join(LOGO, "favicon.ico"), format="ICO",
                               sizes=[(16, 16), (32, 32), (48, 48)],
                               append_images=[ims["favicon-16.png"], ims["favicon-32.png"]])
    manifest = {
        "name": "The Oberdorfer Team",
        "short_name": "Oberdorfer",
        "start_url": "/",
        "display": "browser",
        "background_color": "#071D2D",
        "theme_color": "#071D2D",
        "icons": [
            {"src": "icon-192.png", "sizes": "192x192", "type": "image/png"},
            {"src": "icon-512.png", "sizes": "512x512", "type": "image/png", "purpose": "any maskable"},
        ],
    }
    with open(os.path.join(LOGO, "site.webmanifest"), "w") as f:
        json.dump(manifest, f, indent=2)
        f.write("\n")
    print("ok", sorted(n for n in os.listdir(LOGO) if n.endswith((".png", ".ico", ".webmanifest"))))


if __name__ == "__main__":
    main()
