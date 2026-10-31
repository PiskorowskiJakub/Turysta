-- Awatary f2, m1, m2, x1, x2 to pliki SVG.
UPDATE avatars SET image_url = REPLACE(image_url, '.png', '.svg') WHERE image_url IN ('/img/avatars/f2.png', '/img/avatars/m1.png', '/img/avatars/m2.png', '/img/avatars/x1.png', '/img/avatars/x2.png');
