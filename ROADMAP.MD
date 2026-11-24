# Development Roadmap — Real-Time Ticketing System

Dokumen ini memetakan rencana implementasi bertahap untuk membangun aplikasi **Real-Time Ticketing System** menggunakan **Laravel**, **Lighthouse GraphQL**, **Laravel Reverb**, dan **Vue 3 (Pinia + Tailwind CSS)** berdasarkan `docs/PRD.md` dan `docs/UI_SPECIFICATION.md`.

## 1. Ringkasan Timeline & Target Rilis

Estimasi total pengerjaan adalah **3–6 minggu** (tergantung ritme kerja harian dan kompleksitas *debugging* WebSocket/UI).

* **Minggu 1 (Fase 1):** Fondasi proyek, autentikasi 3 *role*, skema *database*, dan *layout* dasar *frontend*.
* **Minggu 2 (Fase 2):** Alur inti tiket (*create*, *assign*, *submit completion*, *resolve*), *milestone*, dan *dashboard* 3 *role* (**Target MVP Core**).
* **Minggu 3–4 (Fase 3):** *Real-time chat* ala WhatsApp via Laravel Reverb (*private channel*, *typing indicator*, *read receipts*).
* **Minggu 4–5 (Fase 4):** Sistem notifikasi *real-time* (lonceng UI + *broadcast*) dan fitur *export* laporan PDF.
* **Minggu 5–6 (Fase 5):** *End-to-end testing* lintas *role*, penanganan *edge cases*, dan *polishing* UI.

---

## 2. Detail Fase Pengembangan

### Fase 1: Foundation, Auth & Database (Hari 1–5)

**Tujuan:** Memastikan *backend* GraphQL, server WebSocket Reverb, *database*, dan kerangka SPA Vue 3 berjalan mulus serta aman berdasarkan `role`.

* **Backend (Laravel):**
  * Inisialisasi proyek Laravel, konfigurasi koneksi MySQL, dan instalasi `laravel/sanctum`, `nuwave/lighthouse`, serta `laravel/reverb`.
  * Membuat migrasi & model Eloquent untuk `users`, `tickets`, `ticket_milestones`, `conversations`, `messages`, dan `notifications`.
  * Membuat *seeder* data awal (akun *Admin*, *Agent*, *User*, dan beberapa tiket sampel).
  * Menyusun skema dasar GraphQL (`auth`, `me`, `tickets`, `ticket`) dan *policy/directive* pembatasan akses per *role*.
* **Frontend (Vue 3):**
  * Setup Vue 3 + Vite + Tailwind CSS + Pinia + Vue Router + `@vue/apollo-composable`.
  * Menerapkan sistem warna (`#0f172a`, `#f1f5f9`, `#ffffff`, `#2563eb`, `#3b82f6`, `#93c5fd`, `#e0f2fe`) dan *layout* utama sesuai `docs/UI_SPECIFICATION.md`.
  * Membuat halaman *Login* dan *Route Guard* yang mengarahkan pengguna ke *dashboard* sesuai `role` (`admin`, `agent`, `user`).
* **Kriteria Selesai (Definition of Done):**
  * Bisa *login* sebagai *Admin*, *Agent*, dan *User*, lalu masuk ke *route* masing-masing tanpa akses silang yang tidak sah.
  * Query `me` dan `tickets` berhasil dijalankan melalui GraphQL.

---

### Fase 2: Core Ticketing, Milestones & Role Dashboards (Hari 6–13)

**Tujuan:** Menyelesaikan alur kerja utama tiket dari pembuatan oleh *User* hingga verifikasi penyelesaian oleh *Admin*, termasuk pengelolaan *milestone*.

* **Backend (Laravel):**
  * Implementasi *mutation* `createTicket` (otomatis membuat nomor tiket unik dan *conversation* tipe `user_admin`).
  * Implementasi *mutation* `assignTicket` (mengisi `admin_id` & `agent_id`, mengubah status menjadi `in_progress`, dan membuat *conversation* tipe `admin_agent`).
  * Implementasi *mutation* CRUD *milestone* (`createMilestone`, `toggleMilestone`) khusus *Admin*.
  * Implementasi *state machine* status: `submitTicketCompletion` (*Agent* $\rightarrow$ `pending_review`) dan `resolveTicket` / `reopenTicket` (*Admin* $\rightarrow$ `resolved` / `in_progress`).
  * Implementasi *query* `dashboardStats` serta filter pencarian (`search`, `status`, `priority`, `date`).
* **Frontend (Vue 3):**
  * Membangun **User Dashboard** + **Ticket Form** (`judul tiket`, `deskripsi masalah`, prioritas `low`/`medium`/`high`).
  * Membangun **Admin Dashboard & Ticket Table** (4 *metric cards*, *Tiket Terbaru*, *Distribusi status*, tabel tiket, dan aksi *Assign Agent* / *Resolve*).
  * Membangun **Agent Dashboard** (4 *metric cards*, tabel `Daftar Tiket`, panel `Milestones`, dan tombol **Ajukan Selesai**).
  * Membangun komponen `MilestonePanel.vue` dengan mode edit untuk *Admin* dan *read-only* untuk *User*/*Agent*.
* **Kriteria Selesai:**
  * Seluruh transisi status `open` $\rightarrow$ `in_progress` $\rightarrow$ `pending_review` $\rightarrow$ `resolved` berjalan valid sesuai *role*.
  * *Milestone* tampil dan tersinkronisasi dengan benar di halaman detail tiket.

---

### Fase 3: Real-Time Chat Engine ala WhatsApp (Hari 14–22)

**Tujuan:** Menghadirkan obrolan *real-time* yang terpisah antara ruang `user_admin` dan `admin_agent` dengan pengalaman responsif seperti aplikasi pesan instan.

* **Backend (Laravel):**
  * Konfigurasi *private channel* di `routes/channels.php` untuk `conversation.{conversationId}` dengan validasi partisipan tiket (`user_id`, `admin_id`, `agent_id`).
  * Membuat *event* `MessageSent` (`ShouldBroadcast`) yang dipicu saat *mutation* `sendMessage` dijalankan.
  * Membuat *mutation* `markConversationAsRead` dan *event* `MessagesRead` untuk memperbarui status terbaca (`read_at`).
* **Frontend (Vue 3):**
  * Konfigurasi `laravel-echo` dan `pusher-js` (terhubung ke Laravel Reverb) dengan autentikasi token Sanctum.
  * Membangun komponen `ChatBox.vue` dengan *tab* `User Chat` dan `Agent Chat` (menyesuaikan *role* pengguna).
  * Menampilkan *bubble* pesan masuk (`#f1f5f9`) dan keluar (`#2563eb`), *timestamp*, *auto-scroll* ke pesan terbaru, *typing indicator* via `.whisper('typing', ...)`, serta *read receipts*.
* **Kriteria Selesai:**
  * Pesan terkirim dan diterima secara *real-time* ($< 1$ detik) antar *tab/browser* berbeda tanpa *refresh*.
  * *User* tidak bisa melihat atau mengakses ruang obrolan `admin_agent`.

---

### Fase 4: Real-Time Notifications & PDF Export (Hari 23–28)

**Tujuan:** Melengkapi sistem dengan pemberitahuan instan di *header* dan fitur unduh ringkasan tiket dalam format PDF.

* **Backend (Laravel):**
  * Membuat kelas Notifikasi Laravel (`TicketActivityNotification`) dengan *channel* `['database', 'broadcast']` untuk *channel* pribadi `App.Models.User.{id}`.
  * Memicu notifikasi pada momen kunci: tiket baru, tiket di-*assign*, *agent* mengajukan selesai, tiket *resolved*, dan *milestone* diperbarui.
  * Membuat *endpoint* REST terproteksi `GET /api/tickets/{ticket}/export-pdf` menggunakan `barryvdh/laravel-dompdf` atau `spatie/laravel-pdf` beserta *template* Blade yang rapi.
* **Frontend (Vue 3):**
  * Mengintegrasikan *bell notification* di *top header* (menampilkan jumlah belum dibaca, daftar riwayat, aksi *mark as read*, dan *toast alert* *real-time*).
  * Menambahkan tombol **Export PDF** pada detail tiket dan tabel tiket yang mengunduh file `.pdf` secara langsung.
* **Kriteria Selesai:**
  * Notifikasi muncul *real-time* saat ada *event* baru dan tersimpan di riwayat *database*.
  * File PDF terunduh dengan memuat detail tiket, *role* terkait, *milestone*, dan status terkini.

---

### Fase 5: QA, Edge Cases & Polishing (Hari 29–35)

**Tujuan:** Memastikan stabilitas sistem sebelum digunakan atau didemokan.

* **Pengujian & Perbaikan:**
  * Menguji skenario multi-sesi (membuka *Admin*, *Agent*, dan *User* bersamaan) untuk memastikan tidak ada *state* yang tertukar atau duplikasi pesan.
  * Menambahkan *loading state*, *empty state*, dan pesan *error* yang jelas pada *form*, tabel, dan panel *chat*.
  * Mengecek responsivitas tampilan di ukuran layar *desktop* dan *tablet/mobile* serta kesesuaian akhir dengan `docs/UI_SPECIFICATION.md`.
* **Kriteria Selesai:**
  * Alur utama dari buat tiket sampai *export* PDF bebas *error* fatal dan siap rilis versi 1.0.

---

## 3. Aturan Prioritas Jika Waktu Terbatas

Jika tenggat waktu semakin dekat, pertahankan urutan prioritas berikut:
1. **Wajib ada (MVP):** *Auth* 3 *role*, *create/assign/submit/resolve* tiket, *milestone*, dan *chat real-time* dasar (`kirim` & `terima` pesan).
2. **Penting (Fase 4):** Notifikasi *real-time* dan *export* PDF.
3. **Penyempurnaan (Fase 5):** *Typing indicator*, *read receipts*, lampiran file *chat*, dan analitik grafik lanjutan.