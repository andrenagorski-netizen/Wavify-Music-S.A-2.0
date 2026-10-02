import os
import re

files = [
    'wavify_home.php',
    'wavify_discover.html',
    'wavify_my_library.php',
    'wavify_upload.html'
]

links_map = {
    'home': 'wavify_home.php',
    'explore': 'wavify_discover.html',
    'library_music': 'wavify_my_library.php',
    'cloud_upload': 'wavify_upload.html'
}

for filename in files:
    if not os.path.exists(filename):
        continue
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()

    for icon, target_html in links_map.items():
        # Match the standard sidebar links (web & mobile)
        pattern = re.compile(r'(<a[^>]*href=")([^"]*)("[^>]*>\s*<span[^>]*material-symbols-outlined[^>]*>)\s*' + icon + r'\s*(</span>)', re.DOTALL)
        content = pattern.sub(r'\g<1>' + target_html + r'\g<3>' + icon + r'\g<4>', content)
        
    # Inject <script src="main.js"></script> before </body>
    if '<script src="main.js"></script>' not in content:
        content = content.replace('</body>', '    <script src="main.js"></script>\n</body>')
        
    with open(filename, 'w', encoding='utf-8') as f:
        f.write(content)

print("HTML patched successfully!")
