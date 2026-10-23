"""Render the link-preview (Open Graph) image: 1200 x 630, what iMessage, Facebook,
LinkedIn and X show when someone shares oberdorferteam.com.

    python3 tools/build_share_image.py

Needs Python Playwright + Chromium (as tools/screenshot.py). Fonts are the publication's
Cormorant Garamond and Manrope subsets in tools/fonts/. Writes
assets/img/share-oberdorfer-team-v1.jpg. Apps cache previews by image URL, so when the
design changes, bump the version in the file name (and in the theme's functions.php).

The copy is the team's own (supplied 2026-10-22); the portrait is the team portrait from
Wakefield at Home.
"""
import asyncio, base64, os
from playwright.async_api import async_playwright

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FONTS = os.path.join(ROOT, "tools", "fonts")
OUT = os.path.join(ROOT, "assets", "img", "share-oberdorfer-team-v1.jpg")


def b64(path):
    return base64.b64encode(open(path, "rb").read()).decode()


def main():
    logo = b64(os.path.join(ROOT, "assets", "logo", "logo-primary-gold.svg"))
    photo = b64(os.path.join(ROOT, "assets", "img", "photo", "team-portrait.webp"))
    html = f"""<!doctype html><html><head><style>
@font-face{{font-family:'CG';src:url(data:font/ttf;base64,{b64(os.path.join(FONTS, 'CormorantGaramond-Light.ttf'))});}}
@font-face{{font-family:'CG';font-style:italic;src:url(data:font/ttf;base64,{b64(os.path.join(FONTS, 'CormorantGaramond-LightItalic.ttf'))});}}
@font-face{{font-family:'MR';src:url(data:font/ttf;base64,{b64(os.path.join(FONTS, 'Manrope-ExtraLight.ttf'))});}}
*{{box-sizing:border-box;margin:0}}
body{{width:1200px;height:630px;background:#071D2D;overflow:hidden}}
.frame{{position:absolute;inset:22px;border:1px solid #B49A65;outline:1px solid rgba(180,154,101,.45);outline-offset:6px}}
.copy{{position:absolute;left:78px;top:0;bottom:0;width:560px;display:flex;flex-direction:column;justify-content:center}}
.logo{{width:330px;display:block;margin-left:-9px}}
.powered{{margin-top:14px;font:600 13px/1 'MR',sans-serif;letter-spacing:.26em;text-transform:uppercase;color:#A3AEB3}}
.kicker{{margin-top:58px;font:600 15px/1 'MR',sans-serif;letter-spacing:.24em;text-transform:uppercase;color:#B49A65}}
.display{{margin-top:18px;font:300 72px/1.02 'CG',serif;color:#F7F3EC;letter-spacing:-.01em}}
.display em{{color:#D9C9A5}}
.line{{margin-top:22px;font:italic 300 25px/1.35 'CG',serif;color:#D9C9A5}}
.photo{{position:absolute;right:78px;top:68px;width:410px;height:494px}}
.photo::after{{content:"";position:absolute;inset:12px -12px -12px 12px;border:1px solid #B49A65}}
.photo img{{position:relative;z-index:1;width:100%;height:100%;object-fit:cover;object-position:50% 18%;display:block}}
</style></head><body><div class="frame"></div>
<div class="copy">
  <img class="logo" src="data:image/svg+xml;base64,{logo}" alt="">
  <p class="powered">Powered by REWAP</p>
  <p class="kicker">Massachusetts real estate</p>
  <p class="display">Luxury at<br><em>every price point.</em></p>
  <p class="line">Exceptional homes. Thoughtful representation.<br>Personal attention.</p>
</div>
<div class="photo"><img src="data:image/webp;base64,{photo}" alt=""></div>
</body></html>"""

    async def run():
        async with async_playwright() as p:
            b = await p.chromium.launch()
            page = await b.new_page(viewport={"width": 1200, "height": 630}, device_scale_factor=1)
            await page.set_content(html)
            await page.wait_for_timeout(400)
            await page.screenshot(path=OUT, type="jpeg", quality=86, clip={"x": 0, "y": 0, "width": 1200, "height": 630})
            await b.close()
    asyncio.run(run())
    print("wrote", os.path.relpath(OUT, ROOT), os.path.getsize(OUT), "bytes")


if __name__ == "__main__":
    main()
