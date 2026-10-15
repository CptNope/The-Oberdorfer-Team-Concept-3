"""Screenshot helper for design review (needs Python Playwright + Chromium).

Usage: python3 tools/screenshot.py <url-or-path> <out.png> [width] [--full] [--height H]
Serve the repo first:  python3 -m http.server 8765

If Google Fonts are unreachable (sandboxed environments), requests to fonts.googleapis.com
are answered with @font-face rules that point at the Cormorant/Manrope subsets
extracted from the source PDF (single light weights; heavier weights are
synthesized by the browser, so screenshots slightly under-represent weight).
"""
import sys, base64, os, asyncio
from playwright.async_api import async_playwright

HERE = os.path.dirname(os.path.abspath(__file__))
F = os.path.join(HERE, "fonts")


def b64(n):
    return base64.b64encode(open(os.path.join(F, n), "rb").read()).decode()


CSS = f"""
@font-face{{font-family:'Cormorant Garamond';font-style:normal;font-weight:300 700;src:url(data:font/ttf;base64,{b64('CormorantGaramond-Light.ttf')}) format('truetype');}}
@font-face{{font-family:'Cormorant Garamond';font-style:italic;font-weight:300 700;src:url(data:font/ttf;base64,{b64('CormorantGaramond-LightItalic.ttf')}) format('truetype');}}
@font-face{{font-family:'Manrope';font-style:normal;font-weight:200 800;src:url(data:font/ttf;base64,{b64('Manrope-ExtraLight.ttf')}) format('truetype');}}
"""


async def main():
    a = sys.argv[1:]
    url, out = a[0], a[1]
    width = int(a[2]) if len(a) > 2 and a[2].isdigit() else 1440
    full = "--full" in a
    height = int(a[a.index("--height") + 1]) if "--height" in a else 900
    if not url.startswith("http"):
        url = "file://" + os.path.abspath(url)
    async with async_playwright() as p:
        b = await p.chromium.launch()
        pg = await b.new_page(viewport={"width": width, "height": height}, device_scale_factor=1,
                              reduced_motion="reduce")
        await pg.route("https://fonts.googleapis.com/**", lambda r: r.fulfill(status=200, content_type="text/css", body=CSS))
        await pg.route("https://fonts.gstatic.com/**", lambda r: r.abort())
        msgs = []
        pg.on("console", lambda m: msgs.append(f"{m.type}: {m.text}"))
        pg.on("pageerror", lambda e: msgs.append(f"pageerror: {e}"))
        await pg.goto(url, wait_until="networkidle")
        await pg.evaluate("document.fonts.ready")
        if full:
            await pg.evaluate("""async () => { document.querySelectorAll('img[loading=lazy]').forEach(i => i.loading='eager');
              for (let y=0; y<document.body.scrollHeight; y+=700) { window.scrollTo(0,y); await new Promise(r=>setTimeout(r,60)); }
              window.scrollTo(0,0); await Promise.all([...document.images].map(i => i.complete ? 0 : new Promise(r => { i.onload = i.onerror = r; }))); }""")
        await pg.wait_for_timeout(400)
        await pg.screenshot(path=out, full_page=full)
        ow = await pg.evaluate("[document.documentElement.scrollWidth, document.documentElement.clientWidth]")
        print(out, "scrollWidth/clientWidth", ow)
        for m in msgs:
            print(m)
        await b.close()

asyncio.run(main())
