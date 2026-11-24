# Development Roadmap — Real-Time Ticketing System

Dokumen ini memetakan rencana implementasi harian untuk membangun aplikasi **Real-Time Ticketing System** menggunakan **Laravel**, **Lighthouse GraphQL**, **Laravel Reverb**, dan **Vue 3 (Pinia + Tailwind CSS)** berdasarkan `docs/PRD.md` dan `docs/UI_SPECIFICATION.md`.

> **Status & keputusan terbaru:** baca `docs/STATUS.md`. **Struktur kode & penempatan file:** baca `docs/ARCHITECTURE.md`.

---

## 0. Keputusan Arsitektur yang Berlaku untuk Seluruh Roadmap Ini

Keputusan berikut sudah final dan **tidak boleh ditawar-tawar lagi oleh agent**. Jika kode atau dokumen lain bertentangan, ikuti keputusan ini dan laporkan konfliknya.

| Topik | Keputusan | Alasan |
| :--- | :--- | :--- |
| **Autentikasi** | **Laravel Fortify + Laravel Sanctum, mode cookie/session (SPA), bukan token `Bearer`** | DIPUTUSKAN 2026-11-24. Konsekuensi: `login`/`logout` memakai endpoint REST Fortify, **bukan** mutation GraphQL. Hanya `me` yang jadi query GraphQL. Jangan menulis `Authorization: Bearer <token>` di Apollo Client. |
| **Database** | **MySQL** (bukan SQLite) | PRD §Tech Stack. `.env` masih SQLite dan **wajib diganti pada Day 1**. |
| **Waktu install Reverb** | **Sejak Day 2** | Dipakai penuh mulai Week 3, tapi konfigurasikan sejak awal agar tidak ada kejutan di tengah. |
| **Tailwind CSS** | Terpasang di **`frontend/`**, bukan `backend/` | `backend/package.json` sekarang punya Tailwind yang tidak berguna untuk SPA. Pindahkan. |
| **Library PDF** | **`barryvdh/laravel-dompdf`** | DIPUTUSKAN 2026-11-24 (PRD §5.1 menyebut dua opsi; sudah dipilih salah satu agar Day 23 tidak buntu). |
| **Nama mutation** | Ikuti **`docs/PRD.md` §6** persis | Kontrak API adalah sumber kebenaran. Nama lain yang beredar di catatan lama dianggap salah. |

**Masih terbuka (jangan diimplementasikan sebelum dijawab):**

* Status **`closed`** — PRD §3 tidak punya transisi menuju `closed`. Hanya `resolved` yang punya jalur. Lihat `docs/STATUS.md` D7.

---

## 1. Ringkasan Timeline & Target Rilis

Estimasi total pengerjaan **4 minggu / 26 hari kerja**.

| Minggu | Hari | Fokus | PRD Phase |
| :--- | :--- | :--- | :--- |
| **Minggu 1** | Day 1–6 | Backend, Autentikasi & GraphQL Core | Phase 1 + 2 |
| **Minggu 2** | Day 7–13 | Fondasi Vue 3, Layout & 3 Role Dashboard | Phase 2 |
| **Minggu 3** | Day 14–20 | Real-Time Chat ala WhatsApp & Detail Tiket | Phase 3 |
| **Minggu 4** | Day 21–26 | Notifikasi Real-Time, Export PDF & Final Polish | Phase 4 + 5 |

**Aturan wajib setiap hari:** sebelum menutup hari, perbarui `docs/STATUS.md` (tandai hari selesai, pindahkan *next actions*, ubah tanggal *Last updated*). Roadmap ini tanpa pembaruan STATUS akan basi dan menyesatkan agent di sesi berikutnya.

**Aturan sinkronisasi:** setiap kali perilaku produk berubah, `docs/PRD.md`, `docs/UI_SPECIFICATION.md`, dan `docs/ARCHITECTURE.md` ikut diperbarui dalam commit yang sama.

---

## 2. Detail Per Hari

### Minggu 1: Backend, Autentikasi & GraphQL Core (Day 1–6)

#### Day 1 — Inisialisasi Backend & Auth Dasar

*Backend:*

* **Ganti `DB_CONNECTION=mysql` di `.env`** beserta kredensialnya, sesuai keputusan di §0. Jalankan `php artisan migrate` untuk memastikan koneksi benar sebelum menulis migrasi produk.
* Pasang **Laravel Fortify** (`composer require laravel/fortify` → `php artisan fortify:install`) dan **Sanctum** (`php artisan install:api`).
* Set `'views' => false` di `config/fortify.php` (Fortify headless — halaman login dibuat di Vue).
* Aktifkan `$middleware->statefulApi()` di `bootstrap/app.php`.
* Tambahkan kolom `role` ke tabel `users` dengan enum `'user'`, `'admin'`, `'agent'` (ikut PRD §5.2).
* Seeder: **1 Admin, 2 Agent, 2 User** — cukup untuk uji lintas role nanti.

*Selesai jika:* `php artisan migrate:fresh --seed` jalan tanpa error, akun ketiga role bisa login lewat `POST /login`, dan `config/fortify.php` menunjukkan `views => false`.

#### Day 2 — Setup Lighthouse GraphQL & Laravel Reverb

*Backend:*

* Instal `nuwave/lighthouse` (sudah ada — **verifikasi saja**, jangan install ulang), publikasikan `config/lighthouse.php`.
* Instal `laravel/reverb`, publikasikan konfigurasinya, isi `.env` Reverb, lalu jalankan server Reverb dan pastikan berjalan.
* Pastikan GraphiQL bisa diakses (`/graphiql`).
* **Autentikasi:**
  * `login` dan `logout` memakai **endpoint REST Fortify** — `POST /login`, `POST /logout`. Jangan dibuat jadi mutation GraphQL.
  * Buat query GraphQL **`me`** yang mengembalikan profil dan `role`.
  * Uji bahwa `@guard`/`@can` bekerja dengan **sesi cookie** (guard `web`), bukan token.
  * Buat `routes/channels.php` kosong + daftarkan lewat `withRouting(...)->withBroadcasting(...)` di `bootstrap/app.php` dengan middleware `['api', 'auth:sanctum']` — persiapan Day 14.

*Selesai jika:* `me` mengembalikan `role` yang benar setelah `POST /login`, dan request tanpa sesi ditolak.

#### Day 3 — Skema Database, Relasi Eloquent & Dokumentasi Skema

*Backend:*

* Migrasi + model Eloquent beserta **foreign key dan indeks**: `tickets`, `ticket_milestones`, `conversations`, `messages` (kolom lengkap ikut PRD §5.2; `personal_access_tokens` sudah dibuat Sanctum, `notifications` dibuat Day 21).
* Indeks wajib: `tickets.ticket_number` **unique**, index komposit `(status, priority)` untuk filter dashboard, `messages(conversation_id, created_at)` untuk riwayat chat.
* Factory + seeder data tiket realistis (campur 3 role, semua status).

* **Tulis `docs/DATABASE.MD` hari ini** — hari paling tepat untuk mengisinya. Isi minimal: daftar tabel + relasi, enum, dan **tabel transisi status legal + role yang boleh memicu**. Setelah selesai, tandai D3 di `docs/STATUS.md` sebagai selesai.

*Selesai jika:* migrasi bisa `fresh` lalu `seed`, dan `DATABASE.MD` sudah memuat relasi + tabel transisi status.

#### Day 4 — GraphQL Queries & Create Ticket Flow

*Backend:*

* Definisikan tipe GraphQL: `User`, `Ticket`, `TicketMilestone`, `Conversation`, `Message`.
* Query `tickets` dengan filter **`search`, `status`, `priority`, `dateFrom`, `dateTo`** (rentang tanggal wajib — PRD §4.2) + paginasi, sudah dibatasi hak akses per role.
* Query `ticket(id)` dengan relasi `milestones` dan `conversations`.
* Mutation `createTicket(input)`: status `open`, nomor tiket unik (`TK-YYYYMM-NNN`), otomatis membuat `Conversation` bertipe `user_admin`.

*Selesai jika:* query `tickets` mengembalikan data berbeda sesuai role yang login, dan `createTicket` menghasilkan tiket + ruang `user_admin` dalam satu langkah.

#### Day 5 — State Machine & Mutation Aksi Role

*Backend (nama mutation **wajib** sama dengan PRD §6):*

* `assignTicket(ticketId, agentId)` — Admin. Mengisi `admin_id`/`agent_id`, status → `in_progress`, membuat `Conversation` `admin_agent`.
* `submitTicketCompletion(ticketId, notes)` — Agent → `pending_review`.
* `resolveTicket(ticketId)` — Admin → `resolved`.
* **`reopenTicket(ticketId)` — Admin → `in_progress`.** Jangan dilewatkannya: tanpa ini, tiket yang salah resolve tidak punya jalan keluar (PRD §3).
* Policy + validasi transisi status **di sisi server**: role yang tidak berhak, atau transisi di luar tabel di `DATABASE.MD`, harus ditolak dengan pesan jelas — bukan hanya tombol disembunyikan di UI (PRD §7).
* **Tulis feature test** untuk setiap transisi legal dan minimal 3 transisi ilegal.

*Selesai jika:* seluruh jalur `open → in_progress → pending_review → resolved` **dan** `resolved → in_progress` lolos test; transisi acak ditolak.

#### Day 6 — CRUD Milestone & Uji Coba API Menyeluruh

*Backend:*

* `createMilestone(ticketId, input)`, `updateMilestone(milestoneId, input)`, `toggleMilestone(milestoneId)`, `deleteMilestone(milestoneId)` — **khusus Admin**.
* **Catatan keputusan:** `updateMilestone`/`deleteMilestone` adalah tambahan di luar PRD §6 (PRD §4.4 hanya menyebut "menambahkan, mengubah, menandai"). Keduanya ditambahkan ke kontrak PRD §6 pada sinkronisasi ini.
* **Agent dan User tetap read-only** pada milestone (PRD §4.4). Jangan menambahkan izin centang untuk Agent.
* Uji seluruh alur dari pembuatan tiket sampai `resolved` lewat GraphiQL.
* Rapikan respons error GraphQL (pesan konsisten, kode error terbaca, tanpa kebocoran stack trace).

*Selesai jika:* seluruh mutation di PRD §6 terbukti berfungsi lewat GraphiQL, dan `docs/STATUS.md` menandai Minggu 1 selesai.

---

### Minggu 2: Fondasi Vue 3, Layout & 3 Role Dashboard (Day 7–13)

#### Day 7 — Setup Proyek Vue 3, Apollo & Pinia

*Frontend:*

* Init Vue 3 + Vite + **Tailwind CSS di `frontend/`** (sesuai §0 — pindahkan dari `backend/package.json`), lalu `vue-router`, `pinia`, `@apollo/client`, `@vue/apollo-composable`.
* **Konfigurasi Apollo dengan cookie, bukan token:** setiap request memakai `credentials: 'include'` / `withCredentials: true` + header `Accept: application/json`. **Jangan** menulis `Authorization: Bearer <token>` — keputusan §0.
* Tambahkan **proxy Vite** untuk `/login`, `/logout`, `/sanctum`, `/graphql`, `/broadcasting` agar cookie dianggap same-origin (hindari CORS lintas port di mode dev).

*Selesai jika:* query `me` dari Apollo berhasil mengembalikan data user setelah login.

#### Day 8 — Halaman Login, Route Guard & Main Layout

*Frontend:*

* Alur login: `GET /sanctum/csrf-cookie` → `POST /login` (Fortify) → arahkan ke dashboard sesuai `role`. Tangani 419/401 dengan redirect kembali ke login.
* Navigation guard Vue Router untuk `/user/...`, `/admin/...`, `/agent/...`.
* Kerangka layout sesuai `docs/UI_SPECIFICATION.md`: sidebar `#0f172a` lebar `240px`, kanvas `#f1f5f9`, kartu `#ffffff` (`rounded-2xl`), aksen `#2563eb`/`#3b82f6`/`#93c5fd`/`#e0f2fe`, header atas dengan lonceng notifikasi.

*Selesai jika:* ketiga role login → masuk ke route masing-masing, refresh halaman tetap login, logout benar-benar mengakhiri sesi.

#### Day 9 — Komponen UI Reusable

`StatusBadge.vue`, `PriorityBadge.vue`, `MetricCard.vue`, `TicketFilterBar.vue` (lengkap dengan pencarian, status, prioritas, **rentang tanggal**), `EmptyState.vue`. Pisahkan layout dari logika state dan query (UI_SPECIFICATION §4.3).

*Selesai jika:* komponen bisa dipakai di dua halaman berbeda tanpa duplikasi template.

#### Day 10 — User Dashboard & Form Buat Tiket

* User Dashboard ("My Ticket"): pencarian, filter `status`/`priority`/tanggal, daftar kartu tiket yang dapat diklik.
* Ticket Form: `judul tiket`, `deskripsi masalah`, prioritas `low`/`medium`/`high`, tombol `cancel` + `simpan` (`#2563eb`).
* Sambungkan ke mutation `createTicket`.

*Selesai jika:* User bisa membuat tiket dan melihatnya muncul di daftarnya.

#### Day 11 — Admin Dashboard (Ringkasan & Grafik)

* 4 *metric card* (Open, In Progress, Pending Review, Resolved), daftar **Tiket Terbaru**, panel **Distribusi Status** (horizontal progress bar).
* **Grafik tren volume & penyelesaian tiket** dengan filter waktu (UI_SPECIFICATION Baris 2 kiri) — bagian ini sering terlewat, wajib ada.
* Query GraphQL agregasi `dashboardStats` agar angka tampil dinamis dari database.

*Selesai jika:* keempat angka metric + grafik tren tersalin benar dari data di database.

#### Day 12 — Admin Ticket Management Table

* Tabel dengan kolom `id`, `judul`, `report by`, `status`, `priority`, `date`, `aksi`.
* Modal/dropdown **Assign Agent** (→ `assignTicket`) dan aksi verifikasi (**Resolve** → `resolveTicket`, **Reopen** → `reopenTicket`).
* Toolbar pencarian + filter `status`, `priority`, tanggal.
* Tombol **Export PDF** (tombolnya boleh sudah ada, fungsinya disambung Day 24).

*Selesai jika:* Admin bisa assign dan resolve dari tabel, dan aksi yang tidak sah menghasilkan error dari server (bukan sekadar tombol tersembunyi).

#### Day 13 — Agent Dashboard

* 4 *metric card* tugas agent, tabel **Daftar Tiket** (filter + pencarian), panel **Milestones** tiket terpilih (read-only), tombol **Ajukan Selesai** (→ `submitTicketCompletion`).

*Selesai jika:* Agent melihat **hanya** tiket yang ditugaskan kepadanya, dan tombol Ajukan Selesai mengubah status ke `pending_review`.

---

### Minggu 3: Real-Time Chat Ala WhatsApp & Detail Tiket (Day 14–20)

#### Day 14 — Konfigurasi Laravel Echo & Private Channel

* Instal `laravel-echo` + `pusher-js` di `frontend/`, hubungkan ke Reverb.
* Isi `routes/channels.php` untuk `conversation.{conversationId}` dengan validasi partisipan (`user_id`, `admin_id`, `agent_id`) — sudah disiapkan Day 2.
* **Verifikasi client event `whisper` didukung** oleh konfigurasi Reverb kamu sebelum lanjut ke Day 18. Ini titik gagal yang umum dan lebih murah ditemukan sekarang.

*Selesai jika:* user yang berhak bisa subscribe, user lain ditolak oleh channel authorization.

#### Day 15 — Backend Real-Time Messaging

* Mutation `sendMessage(conversationId, body)`: simpan ke `messages` + broadcast event `MessageSent` (`ShouldBroadcast`) ke `conversation.{id}`. Cek partisipan sebelum menyimpan.
* Query riwayat pesan per `conversationId` dengan paginasi/cursor.
* Mutation **`markConversationAsRead(conversationId)`** (bukan `markMessagesAsRead` — nama dari PRD §6) + event `MessagesRead`.

*Selesai jika:* pesan tersimpan dan terkirim ke channel, tapi **tidak** tersimpan ketika pengirim bukan partisipan.

#### Day 16 — Halaman Detail Tiket & ChatBox Dasar

* `TicketDetailView.vue`: informasi tiket (kiri/atas), area chat, panel milestone (kanan, ~35%).
* `ChatBox.vue` + `MessageBubble.vue`: bubble masuk `#f1f5f9`, keluar `#2563eb`, timestamp, auto-scroll ke pesan terbaru, pesan baru masuk real-time via `Echo.private('conversation.{id}')`.

*Selesai jika:* dua browser berbeda saling menerima pesan dalam < 1 detik tanpa reload (PRD §7).

#### Day 17 — Pemisahan Dual Chat Room (user_admin vs admin_agent)

* Tab `User Chat` dan `Agent Chat` khusus tampilan Admin.
* User hanya terhubung ke `user_admin`; Agent hanya ke `admin_agent`. Uji aktif: **User tidak boleh bisa membaca `admin_agent`** — ini syarat privasi inti PRD §7.

*Selesai jika:* percakapan internal Admin–Agent tidak terbaca oleh User, baik lewat UI maupun lewat subscribe channel langsung.

#### Day 18 — Fitur WhatsApp: Typing Indicator

* `.whisper('typing', ...)` dan `.listenForWhisper('typing', ...)` di `ChatBox.vue` → indikator "Sedang mengetik..." muncul real-time **tanpa menyentuh database** (bergantung hasil verifikasi Day 14).

*Selesai jika:* indikator muncul saat user mengetik dan hilang saat berhenti, dan tidak ada baris baru di tabel mana pun.

#### Day 19 — Fitur WhatsApp: Read Receipts

* Panggil `markConversationAsRead` saat jendela chat aktif.
* Perbarui ikon status pesan di `MessageBubble.vue` (via event `MessagesRead`) secara instan.

*Selesai jika:* centang berubah saat lawan bicara membuka ruang chat, dan nilai `read_at` tersimpan di database.

#### Day 20 — Integrasi Milestone di Halaman Detail Tiket

* `MilestoneList.vue` di panel kanan `TicketDetailView.vue`.
* Admin: tambah/ubah/centang/hapus. **User dan Agent: read-only** (PRD §4.4), dengan progress bar yang jelas.

*Selesai jika:* perubahan milestone Admin langsung terlihat, dan usaha User/Agent mengubah milestone lewat API ditolak.

---

### Minggu 4: Notifikasi Real-Time, Export PDF & Final Polish (Day 21–26)

#### Day 21 — Backend Notification System

* Migrasi tabel `notifications` (bawaan Laravel) dan kelas notifikasi dengan channel `['database', 'broadcast']` ke `App.Models.User.{id}`:
  * `TicketCreatedNotification` (→ Admin)
  * `TicketAssignedNotification` (→ Agent)
  * **`MessageReceivedNotification` (→ lawan bicara)** — wajib ada (PRD §4.5), sering terlewat.
  * `TicketSubmittedForReviewNotification` (→ Admin)
  * `TicketStatusUpdatedNotification` (→ User; mencakup `resolved` dan pembaruan milestone)
* Query daftar notifikasi + mutation `markNotificationAsRead` / `markAllNotificationsAsRead`.

*Selesai jika:* kelima pemicu di PRD §4.5 terbukti menghasilkan notifikasi tersimpan di database.

#### Day 22 — Frontend Notification Bell & Toast Alert

* Pinia notifications store yang listen ke `App.Models.User.{id}`.
* Ikon lonceng di header: badge jumlah belum dibaca, dropdown riwayat, klik menuju tiket terkait, toast saat event baru.

*Selesai jika:* notifikasi muncul di layar penerima < 1 detik tanpa reload, dan tetap ada riwayatnya setelah refresh.

#### Day 23 — Backend Export PDF

* Instal **`barryvdh/laravel-dompdf`** (keputusan §0).
* Endpoint REST terproteksi: `GET /api/tickets/{ticket}/export-pdf`. **Jalankan policy sendiri** di endpoint ini — jangan mengandalkan sesi GraphQL.
* Template Blade PDF: info tiket, pelapor & admin & agent, daftar milestone + status + waktu selesai, ringkasan status akhir.

*Selesai jika:* PDF terunduh untuk tiket yang berhak diakses, dan **403** untuk tiket yang bukan hak user tersebut.

#### Day 24 — Integrasi Tombol Export PDF di Vue

* Axios dengan `responseType: 'blob'` di Admin Ticket Table dan Ticket Detail View, nama file rapi (mis. `Ticket-TK-042.pdf`), tangani kegagalan dengan pesan yang jelas.

*Selesai jika:* klik tombol menghasilkan file `.pdf` terbuka dengan benar.

#### Day 25 — End-to-End Testing Lintas 3 Role

* Buka 3 sesi browser bersamaan (User, Admin, Agent), uji skenario penuh: buat tiket → chat `user_admin` → assign ke agent → buat milestone → chat `admin_agent` → agent ajukan selesai → admin verifikasi & resolve → (uji `reopenTicket`) → export PDF.
* Catat dan perbaiki: bug sinkronisasi state, duplikasi pesan WebSocket, edge case validasi.
* Pastikan `php artisan test` tetap hijau — jalankan test state machine dari Day 5 kembali.

*Selesai jika:* seluruh alur lolos tanpa error fatal di ketiga sesi, dan test otomatis lulus.

#### Day 26 — UI Polish, Error Handling & Dokumentasi

* Loading skeleton, empty state, pesan error form, responsif laptop + mobile.
* Tulis `README.md`: langkah instalasi, konfigurasi `.env` (termasuk MySQL + Reverb), dan perintah menjalankan Laravel, Reverb, dan Vue bersamaan.
* **Perbarui `docs/STATUS.md` secara menyeluruh** — semua fase selesai, `DATABASE.MD` dan gap di §3 harus sudah terhapus, `ARCHITECTURE.md` §9 harus bersih dari pertanyaan yang terjawab.

*Selesai jika:* README cukup bagi orang baru untuk menjalankan aplikasi dari nol, dan `STATUS.md` tidak lagi menyebut "belum ada fitur".

---

## 3. Aturan Prioritas Jika Waktu Terbatas

Jika tenggat mendekat, pertahankan urutan prioritas berikut (diambil dari PRD §8):

1. **Wajib ada (MVP):** Auth 3 role, `createTicket`/`assignTicket`/`submitTicketCompletion`/`resolveTicket`, milestone, dan chat real-time dasar (kirim & terima pesan).
2. **Penting (Day 21–24):** Notifikasi real-time dan export PDF.
3. **Penyempurnaan:** typing indicator, read receipts, lampiran file chat, dan grafik analitik lanjutan.

**Jangan dikorbankan dalam kondisi apa pun** — ini syarat keamanan PRD §7:

* Validasi transisi status di sisi server.
* Authorization `routes/channels.php` dan policy GraphQL.
* `reopenTicket` (tanpa ini tiket bisa terkunci di `resolved`).
