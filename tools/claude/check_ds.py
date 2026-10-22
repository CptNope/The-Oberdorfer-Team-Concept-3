import json, os, re, sys, asyncio
from playwright.async_api import async_playwright
REPO = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT = os.path.join(REPO, "claude", "design-system"); CK=f"{OUT}/check"
B=json.load(open(f"{OUT}/blobs.json")); id2p={v[0]:f"{OUT}/project/assets/{k}" for k,v in B.items()}
def loc(s): return re.sub(r"/_blob/([0-9a-f]{32})", lambda m: "file://"+id2p[m.group(1)], s)
bundle=loc(open(f"{OUT}/project/components/bundle.css").read())
bundle=re.sub(r"@import url\([^)]*\);","",bundle)
open(f"{CK}/bundle.local.css","w").write(bundle)
names=sys.argv[1:] or [c["name"] for c in json.load(open(f"{CK}/cards.json"))]+["Cover"]
async def main():
    async with async_playwright() as p:
        b=await p.chromium.launch(); pg=await b.new_page(viewport={"width":960,"height":40})
        hp=f"{CK}/heights.json"
        res=json.load(open(hp)) if os.path.exists(hp) else {}   # merge: checking a few cards keeps the rest
        for n in names:
            src=open(f"{CK}/{n}.html").read() if n!="Cover" else open(f"{OUT}/project/components/Cover/preview.html").read()
            theme="light"
            src=loc(src).replace('<html lang="en">',f'<html lang="en" data-theme="{theme}">',1).replace("<head>",'<head><link rel="stylesheet" href="tokens.css"><link rel="stylesheet" href="bundle.local.css">',1)
            f=f"{CK}/_{n}.html"; open(f,"w").write(src)
            await pg.goto("file://"+f); await pg.wait_for_timeout(150)
            h=await pg.evaluate("Math.ceil(document.documentElement.scrollHeight)")
            sw=await pg.evaluate("[document.documentElement.scrollWidth, document.documentElement.clientWidth]")
            res[n]=h
            await pg.screenshot(path=f"{CK}/{n}.png",full_page=True)
            print(n,h,sw)
        json.dump(res,open(f"{CK}/heights.json","w"))
        await b.close()
asyncio.run(main())
