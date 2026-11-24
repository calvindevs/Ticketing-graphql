# UI & Frontend Specification — Real-Time Ticketing System

Dokumen ini menjadi panduan resmi desain UI, sistem warna, tata letak (_layout_), dan perilaku komponen (_behavior_) untuk pengembangan _frontend_ (Vue 3 + Tailwind CSS) yang terintegrasi dengan Laravel GraphQL & Laravel Reverb.

---

## 1. Design System & Color Palette

Gaya visual mengadopsi tampilan _modern SaaS dashboard_ dengan kontras tinggi antara _sidebar_ gelap dan kanvas utama yang terang serta bersih.

| Token / Peran                | Kode Hex  | Penggunaan Utama                                                                                                                              |
| :--------------------------- | :-------- | :-------------------------------------------------------------------------------------------------------------------------------------------- |
| **Dark Sidebar Surface**     | `#0f172a` | Latar belakang _left sidebar_, teks utama (_heading_ & angka metrik), dan kontras tinggi.                                                     |
| **Main Canvas Background**   | `#f1f5f9` | Latar belakang halaman utama dan _bubble chat_ masuk (_incoming_).                                                                            |
| **Card & Modal Surface**     | `#ffffff` | Latar belakang _card_, tabel, _form_, dan panel kerja utama (sudut membulat `16px` / `rounded-2xl`).                                          |
| **Primary Brand & CTA**      | `#2563eb` | Tombol aksi utama (`+ Ticket`, `Simpan`, `Send`, `Ajukan Selesai`), menu _sidebar_ aktif, _tab_ aktif, dan _bubble chat_ keluar (_outgoing_). |
| **Secondary Accent & Hover** | `#3b82f6` | _Hover state_ tombol utama, garis _chart_, indikator progres, dan aksen sekunder.                                                             |
| **Soft Outline & Border**    | `#93c5fd` | _Border_ fokus input, garis pembatas elemen aktif, dan aksen _chart_.                                                                         |
| **Soft Tint & Icon Badge**   | `#e0f2fe` | Latar belakang _icon badge_ bulat di _metric card_, _badge_ status/info ringan, dan _hover_ baris tabel.                                      |

---

## 2. Global Layout & Shared Components

Semua halaman terautentikasi menggunakan **1 SPA Vue 3** dengan struktur _layout_ terpadu yang menyesuaikan menu dan hak akses berdasarkan `role` pengguna (`user`, `admin`, `agent`):

1. **Left Sidebar (`#0f172a`, lebar tetap `240px`):**
   - **Header:** Logo & nama aplikasi **"Ticketing"**.
   - **Navigation Menu:** Item aktif menggunakan _highlight_ `#2563eb` dengan sudut membulat (`rounded-xl`) dan teks putih.
   - **Bottom Card:** Kartu ringkas info akun/role dan tombol **Logout**.
2. **Top Header Bar:**
   - **Kiri:** Judul halaman aktif dan _subtitle_ singkat.
   - **Kanan:** Tombol pencarian cepat, ikon **Notification Bell** dengan _badge_ jumlah notifikasi belum dibaca (`#2563eb`), serta profil/avatar pengguna.
3. **Reusable Components:**
   - `MetricCard.vue`: Kartu statistik dengan angka tebal (`#0f172a`), keterangan tren/status, dan _icon badge_ bulat (`#e0f2fe`).
   - `ChatBox.vue`: Komponen _chat real-time_ bergaya WhatsApp (mendukung _typing indicator_, _read receipts_, dan pemisahan _tab_ percakapan).
   - `MilestonePanel.vue`: Daftar target kerja per tiket (_read-only_ untuk `user` & `agent`, dapat ditambah/dicentang oleh `admin`).

---

## 3. Spesifikasi Halaman per Role

### A. Admin Dashboard & Ticket Management

- **Menu Sidebar:** `Dashboard`, `Ticket`.
- **Halaman Dashboard (`/admin/dashboard`):**
  - **Baris 1 (4 Metric Cards):** _Open Tickets_, _In Progress_, _Pending Review_, dan _Resolved_.
  - **Baris 2 (Grid 2 Kolom — 65% : 35%):**
    - Kiri: _Chart_ tren volume & penyelesaian tiket (`#2563eb` & `#93c5fd`) dengan _dropdown_ filter waktu.
    - Kanan: _Status Distribution_ dengan _horizontal progress bar_.
  - **Baris 3 (Grid 2 Kolom):**
    - Kiri: _Tiket Terbaru_ (daftar tiket masuk terbaru dengan _icon badge_ `#e0f2fe`, status, dan waktu relatif).
    - Kanan: _Action Banner Card_ (`#2563eb`) dengan tombol putih menuju antrean tiket yang butuh ditugaskan/direview.
- **Halaman Manajemen Tiket (`/admin/tickets`):**
  - _Toolbar_ pencarian (`Cari Tiket`) serta filter `status`, `priority`, dan `date`.
  - Tabel data dengan kolom: `id`, `judul`, `report by`, `status`, `priority`, `date`, dan `aksi` (_Assign Agent_, _Detail_, _Export PDF_).

### B. Agent Dashboard

- **Menu Sidebar:** `Dashboard`, `Assigned Tickets`.
- **Halaman Utama (`/agent/dashboard`):**
  - **Baris 1 (4 Metric Cards):** _Assigned Tickets_, _In Progress_, _Pending Review_, dan _Completed_.
  - **Baris 2 (Grid 2 Kolom — 65% : 35%):**
    - **Panel Kiri (`Daftar Tiket`):** Pencarian (`Cari Tiket`), filter (`priority`, `status`, `date`), dan tabel tiket yang ditugaskan (`id`, `judul`, `priority`, `status`, `aksi`).
    - **Panel Kanan (`Milestones`):** Daftar _milestone_ tiket terpilih beserta progresnya dan tombol utama `#2563eb` **"Ajukan Selesai"** untuk mengubah status tiket menjadi `pending_review`.

### C. User Dashboard & Create Ticket Form

- **Menu Sidebar:** `Dashboard`, `My Tickets`, `Create Ticket`.
- **Halaman My Ticket (`/user/tickets`):**
  - Header `My Ticket` dengan tombol utama `#2563eb` **"+ Ticket"** di kanan atas.
  - _Filter bar_ (`Cari Tiket`, `status`, `priority`, `date`).
  - Daftar kartu tiket milik pengguna (`ticket_number`, `judul`, `status badge`, `priority`, `date`) yang dapat diklik untuk membuka detail & _chat_ dengan Admin.
- **Form Buat Tiket (`Ticket Form` — modal atau halaman `/user/tickets/create`):**
  - Judul: **Ticket Form** | Subjudul: _"isi form ini untuk membuat pengaduan tiket"_.
  - _Input fields_:
    - `judul tiket` (text input)
    - `deskripsi masalah` (textarea)
    - Pilihan `priority` (`low`, `medium`, `high`)
  - Tombol aksi di bagian bawah: `cancel` (sekunder) dan `simpan` (`#2563eb`).

### D. Shared Ticket Detail, Real-Time Chat & Milestones (`/tickets/:id`)

- **Header Tiket:** Kode & judul tiket (contoh: `Ticket #TK-042 — Server Down`), _status badge_, dan tombol **Export PDF**.
- **Panel Kiri (~65% — Real-Time Chat):**
  - _Tab_ percakapan: `User Chat` (akses User & Admin) dan `Agent Chat` (akses Admin & Agent).
  - _Bubble chat_: Pesan masuk di kiri (`#f1f5f9`, teks `#0f172a`), pesan keluar di kanan (`#2563eb`, teks `#ffffff`), dilengkapi _timestamp_ dan status terbaca (_read receipt_).
  - _Typing indicator_ (_"Sedang mengetik..."_) di atas kolom input, serta _input bar_ + tombol **Send** (`#2563eb`).
- **Panel Kanan (~35% — Milestones):**
  - Daftar _milestone_ tiket dengan indikator selesai, _progress bar_, dan tombol **"+ Add Milestone"** (khusus _role_ `admin`).

---

## 4. Panduan Penggunaan untuk AI Coding Agent

Saat meminta AI _coding agent_ membangun halaman atau komponen Vue 3:

1. Referensikan file ini (`@docs/UI_SPECIFICATION.md`) beserta gambar _mockup_ hasil Stitch yang bersangkutan.
2. Pastikan seluruh warna, _border-radius_ (`rounded-2xl` untuk _card_, `rounded-xl` untuk tombol/input), dan proporsi _grid_ mengikuti aturan pada **Bagian 1** dan **Bagian 2**.
3. Pisahkan _layout_ dan komponen UI dari logika _state_ (Pinia) serta _query/mutation_ GraphQL agar mudah dihubungkan dengan Laravel Lighthouse dan Laravel Reverb.
