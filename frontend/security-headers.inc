# Naglowki bezpieczenstwa (dolaczane w kazdym location, bo add_header w location zastepuje te z serwera).
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "DENY" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=(), payment=()" always;
# CSP na razie tylko raportuje naruszenia w konsoli przegladarki. Po sprawdzeniu, ze nic sie nie psuje,
# zmien nazwe naglowka na Content-Security-Policy, aby ja egzekwowac.
add_header Content-Security-Policy-Report-Only "default-src 'self'; script-src 'self' https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://*.google-analytics.com https://*.googletagmanager.com; connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'" always;
