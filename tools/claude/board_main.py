"""FSE canvas helper. Replace a board's <main> with a site page's markup, tagging each section with an fse-tag.
usage: board_main.py <board file> <site page> <tags.json>"""
import json, re, subprocess, sys
board, page, tags = sys.argv[1], sys.argv[2], json.load(open(sys.argv[3]))
REPO = "/home/claude/the-oberdorfer-team-concept-3"
main = subprocess.run(["python3", f"{REPO}/tools/claude/fse_markup.py", page], capture_output=True, text=True, cwd=REPO).stdout
main = main[main.index("<main"):main.index("</main>") + 7]
it = iter(tags)
def tag(m):
    open_tag = m.group(0)
    t = next(it)
    if 'class="' in open_tag:
        open_tag = open_tag.replace('class="', 'class="fse-scope ', 1) if 'fse-scope' not in open_tag else open_tag
    return open_tag + f'\n<span class="fse-tag">{t}</span>'
main = re.sub(r'<section\b[^>]*>', tag, main)
left = list(it)
assert not left, f"{len(left)} tags unused"
s = open(board).read()
a = s.index("<main"); b = s.index("</main>") + 7
open(board, "w").write(s[:a] + main + s[b:])
print("sections tagged:", len(tags))
