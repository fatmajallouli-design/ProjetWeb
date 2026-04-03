from pathlib import Path
import re
root = Path('c:/Users/USERµ/Desktop/ProjetWeb')
for file in root.rglob('*.php'):
    txt = file.read_text(encoding='utf-8')
    new = txt.replace("header('Location: /.php')", "header('Location: /login.php')")
    new = re.sub(r"header\('Location: \.\./html/([^']+)'\)", r"header('Location: /\1')", new)
    new = re.sub(r"header\('Location: \.\./php/([^']+)'\)", r"header('Location: /php/\1')", new)
    new = new.replace("header('Location: /mon compte.php')", "header('Location: /mon%20compte.php')")
    if new != txt:
        file.write_text(new, encoding='utf-8')
        print('updated', file)
