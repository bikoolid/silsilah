# Karuhun - Aplikasi Silsilah & Visualisasi Pohon Keluarga

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-9553E9?style=for-the-badge&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

**Karuhun** adalah aplikasi web berbasis *full-stack* untuk mengelola, memvisualisasikan, dan mengarsipkan data silsilah keluarga secara interaktif. Dilengkapi dengan grafik pohon keluarga (*D3.js*), pemetaan lokasi makam (*Leaflet*), serta pembatasan hak akses pengelola berbasis ruang lingkup keluarga.

---

## 🚀 Fitur Utama

* **Visualisasi Pohon Keluarga (`/silsilah`):** Rendering canvas D3.js dengan fitur *zoom*, *pan*, *fit view*, perentangan generasi, serta penanda relasi pasangan dan anak.
* **Peta Makam Keluarga (`/peta-makam`):** Pemetaan lokasi makam anggota keluarga secara interaktif menggunakan Leaflet.js.
* **Dashboard Ringkasan (`/dashboard`):** Statistik jumlah anggota, rasio gender, sebaran generasi, serta pengingat ulang tahun dan haul mendatang.
* **Ekspor & Cetak Visual:** Unduh tampilan pohon keluarga aktif ke dalam format file **PNG** dan **PDF**.
* **Manajemen Akses Berbasis Role (RBAC):**
  * `administrator`: Akses penuh ke seluruh data, sistem, dan pengaturan.
  * `manager`: Akses terbatas hanya pada **ruang lingkup keluarganya sendiri** (diri sendiri, pasangan, orang tua, dan keturunan).
* **Cadangan & Pemulihan Data:** Fitur ekspor/impor seluruh basis data silsilah ke dalam format file **JSON**.

---

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi |
| :--- | :--- |
| **Backend Framework** | Laravel 13 (PHP 8.2+) |
| **Frontend Framework** | Vue 3 (Composition API) + Inertia.js |
| **Database** | MySQL 8.0+ / MariaDB 10.4+ |
| **Styling** | Tailwind CSS |
| **Graph Visualization** | D3.js (v7) |
| **Interactive Map** | Leaflet.js |

---

## 📋 Persyaratan Sistem (Prerequisites)

Pastikan sistem atau server lokal telah memenuhi kebutuhan berikut:

* **PHP** $\ge$ 8.2 (ekstensi wajib: `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `xml`)
* **Composer** $\ge$ 2.0
* **Node.js** $\ge$ 18.x & **NPM**
* **MySQL** $\ge$ 8.0 atau **MariaDB** $\ge$ 10.4

---

## 📥 Langkah Instalasi & Konfigurasi

### 1. Clone Repository & Install Dependencies

Buka terminal dan jalankan perintah berikut:

```bash
# Clone repositori
git clone [https://github.com/bikoolid/silsilah.git](https://github.com/bikoolid/silsilah.git)
cd silsilah

# Install dependensi PHP (Backend)
composer install

# Install dependensi JavaScript (Frontend)
npm install
