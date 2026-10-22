import sys,re
BL={"photo/cover-portrait.webp":"ef73cddd73406c7429ef15bd7289db88","photo/team-portrait.webp":"5b9c3391ef2958e39f5d3f554c757926","photo/lake-quannapowitt.webp":"9859cff439d81d2cd307e34529b3b6f6","photo/breakheart-reservation.webp":"b222e843ea721864207527b672c19b96","rewap-logo.webp":"7ea1d8564b51015dbe9061435f252bec","source-pages/page-1.webp":"6a8f7a702a8ce59b82b8d41f88a95b6c"}
import os
REPO = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
s=open(os.path.join(REPO, 'site', sys.argv[1])).read()
m=s[s.index('<main'):s.index('</main>')+7]
m=re.sub(r'href="(?:\.\./)*assets/img/(?:icons|illustrations)\.svg#([^"]+)"',r'href="#\1"',m)
def img(mm):
    p=mm.group(1)
    for k,v in BL.items():
        if p.endswith(k): return f'src="/_blob/{v}"'
    return mm.group(0)
m=re.sub(r'src="([^"]+)"',img,m)
m=re.sub(r'href="(?:\.\./|tel:|mailto:)[^"]*"','href="#"',m)
m=re.sub(r' data-[a-z-]+(="[^"]*")?','',m)
m=re.sub(r' srcset="[^"]*"| sizes="[^"]*"| loading="lazy"| decoding="async"','',m)
print(m)
print('SYMBOLS:',sorted(set(re.findall(r'href="#([a-z-]+)"',m))))
