# Mandala

Mandala adalah aplikasi berbasis Laravel yang dikembangkan sebagai fondasi sistem internal.
Fokus utama proyek ini adalah keteraturan data, alur kerja yang jelas, dan kesiapan untuk dikembangkan secara bertahap.

Project ini masih berada dalam tahap pengembangan aktif.

---

## Tech Stack

- Laravel
- Blade
- Vite
- MySQL
- Composer
- NPM

---

## Installation

1. Clone repository

   ```bash
   git clone https://github.com/gabrielahensky/mandala.git
   cd mandala

2. Install dependencies
   
   ```bash
   composer install
   npm install
   
3. Setup environment

   ```bash
   cp .env.example .env
   php artisan key:generate

5. Jalankan migrasi database

   ```bash
   php artisan migrate
   
7. Alternatif jika port bermasalah:
   ```bash
   php -S localhost:9000 -t public

---

