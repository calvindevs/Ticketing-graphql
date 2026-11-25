# Product Requirements Document (PRD) — Real-Time Ticketing System

| Metadata | Detail |
| :--- | :--- |
| **Nama Produk** | Real-Time Ticketing & Support System |
| **Versi Dokumen** | 1.0 |
| **Status** | Ready for Development |
| **Tech Stack** | Laravel (Backend), Lighthouse GraphQL, Laravel Reverb (WebSocket), Vue 3 + Pinia + Tailwind CSS (Frontend), MySQL |
| **Referensi UI** | `docs/UI_SPECIFICATION.md` |

---

## 1. Latar Belakang & Tujuan Produk

### 1.1 Masalah yang Diselesaikan
Proses penanganan kendala (*issue/support*) sering terhambat karena komunikasi yang terpisah antara pelapor (*User*), koordinator (*Admin*), dan tim teknis (*Agent*), serta kurangnya transparansi progres pengerjaan.

### 1.2 Tujuan Utama
Membangun aplikasi *ticketing system* berbasis web yang terintegrasi dengan komunikasi *real-time* bergaya WhatsApp, pelacakan *milestone* pengerjaan, notifikasi instan, dan ekspor laporan tiket ke PDF—dengan pemisahan hak akses dan ruang obrolan yang jelas untuk 3 peran (*User*, *Admin*, *Agent*).

---

## 2. Peran Pengguna (*User Roles & Permissions*)

| Role | Tanggung Jawab Utama | Hak Akses Utama |
| :--- | :--- | :--- |
| **User** | Melaporkan kendala dan memantau progres penyelesaian tiket. | Membuat tiket, melihat tiket miliknya, *chat* dengan Admin (`user_admin`), melihat *milestone* (*read-only*), menerima notifikasi, dan *export* PDF tiket miliknya. |
| **Admin** | Melakukan triase tiket masuk, mendelegasikan tugas, mengatur *milestone*, dan memverifikasi hasil kerja. | Melihat semua tiket, menerima & menugaskan tiket ke Agent, mengelola *milestone* (CRUD & centang selesai), *chat* terpisah dengan User (`user_admin`) dan Agent (`agent_admin`), menyetujui penyelesaian (`resolved`), dan *export* PDF. |
| **Agent** | Mengeksekusi masalah teknis yang ditugaskan oleh Admin. | Melihat tiket yang ditugaskan kepadanya, *chat* internal dengan Admin (`agent_admin`), memantau *milestone*, dan mengajukan penyelesaian tiket ke Admin (`pending_review`). |

---

## 3. Alur Kerja Sistem (*End-to-End Workflow* & *State Machine*)

Setiap tiket memiliki siklus status yang ketat untuk mencegah perubahan status yang tidak sah:

1. **`open` (Pembuatan Tiket oleh User):**
   * User mengisi formulir pembuatan tiket (`judul`, `deskripsi`, `priority`).
   * Sistem otomatis membuat nomor tiket unik (misal: `TK-202611-001`), membuat ruang obrolan `user_admin`, dan mengirim notifikasi ke semua Admin.
2. **`in_progress` (Penerimaan & Penugasan oleh Admin):**
   * Admin meninjau tiket masuk, menerima tiket, dan memilih *Agent* yang bertugas.
   * Sistem mengisi `admin_id` dan `agent_id`, mengubah status tiket menjadi `in_progress`, membuat ruang obrolan kedua `agent_admin`, serta mengirim notifikasi ke Agent dan User.
   * Admin membuat daftar *milestone* pengerjaan sebagai panduan teknis.
3. **`pending_review` (Pengajuan Selesai oleh Agent):**
   * Agent mengerjakan kendala sambil berkoordinasi dengan Admin melalui *tab* `Agent Chat`.
   * Setelah selesai, Agent menekan tombol **"Ajukan Selesai"** (*Apply to Admin*).
   * Status tiket berubah menjadi `pending_review` dan sistem mengirim notifikasi ke Admin.
4. **`resolved` / `closed` (Verifikasi & Penyelesaian oleh Admin):**
   * Admin memeriksa hasil kerja Agent dan menandai *milestone* yang relevan sebagai selesai.
   * Jika sesuai, Admin mengubah status tiket menjadi `resolved` (atau mengembalikannya ke `in_progress` jika perlu revisi), lalu menginformasikan penyelesaian kepada User melalui *tab* `User Chat`.

---

## 4. Spesifikasi Fitur Fungsional

### 4.1 Autentikasi & Otorisasi
* Autentikasi memakai **Laravel Fortify + Laravel Sanctum** dalam mode **cookie/session (SPA)** — *bukan* token `Authorization: Bearer`.
  * `POST /login` dan `POST/GET /logout` ditangani endpoint REST Fortify (`config/fortify.php`: `'views' => false`, halaman login dibuat di Vue).
  * Inisialisasi CSRF melalui `GET /sanctum/csrf-cookie`; sesi cookie kemudian dipakai oleh *request* GraphQL, REST *endpoint* PDF, dan otorisasi *private channel* WebSocket.
* *Route guard* pada Vue Router untuk mengarahkan pengguna ke *dashboard* masing-masing (`/user/...`, `/admin/...`, `/agent/...`).

### 4.2 Manajemen Tiket & Filter
* **Pembuatan Tiket (User):** Input judul tiket, deskripsi masalah, dan pemilihan prioritas (`low`, `medium`, `high`).
* **Pencarian & Filter (Semua Role):** Pencarian berdasarkan nomor/judul tiket (`Cari Tiket`), filter berdasarkan `status`, `priority`, dan rentang tanggal (`date`).
* **Analitik Dashboard (Admin & Agent):** Kartu ringkasan metrik (*Open*, *In Progress*, *Pending Review*, *Resolved*), grafik tren tiket, dan distribusi status.

### 4.3 Real-Time Chat ala WhatsApp (GraphQL + Laravel Reverb)
* **Pemisahan Ruang Obrolan:**
  * `user_admin`: Hanya dapat diakses oleh User pembuat tiket dan Admin yang menangani.
  * `agent_admin`: Hanya dapat diakses oleh Admin dan Agent yang ditugaskan (percakapan internal tidak terlihat oleh User).
* **Fitur Interaktif Real-Time:**
  * Pengiriman pesan teks (dan lampiran opsional) melalui GraphQL *mutation* `sendMessage` yang memicu *broadcast* ke Laravel Reverb *private channel* (`conversation.{id}`).
  * *Typing indicator* (*"Sedang mengetik..."*) menggunakan fitur *whisper* Laravel Echo tanpa membebani *database*.
  * *Read receipts* (penanda pesan sudah dibaca / `read_at`) yang diperbarui otomatis saat jendela *chat* aktif.

### 4.4 Manajemen Milestone
* Admin dapat menambahkan, mengubah, dan menandai *milestone* selesai pada suatu tiket.
* Agent dapat melihat *checklist milestone* sebagai acuan pengerjaan sebelum menekan **Ajukan Selesai**.
* User dapat memantau progres *milestone* secara *read-only* untuk transparansi status pengerjaan.

### 4.5 Sistem Notifikasi Real-Time
* Menggunakan kombinasi penyimpanan *database* (riwayat lonceng notifikasi di *top header*) dan *broadcast* via Laravel Reverb (`App.Models.User.{id}`).
* Pemicu notifikasi: Tiket baru masuk (ke Admin), tiket ditugaskan (ke Agent), pesan baru masuk (ke lawan bicara), Agent mengajukan selesai (ke Admin), dan status tiket selesai / *milestone* diperbarui (ke User).

### 4.6 Export Laporan Tiket ke PDF
* Tersedia tombol **Export PDF** pada detail tiket dan tabel manajemen tiket.
* Dihasilkan melalui *endpoint* terproteksi (`GET /api/tickets/{id}/export-pdf`) yang merender dokumen PDF berisi informasi tiket, identitas pelapor/admin/agent, daftar *milestone* beserta waktu penyelesaian, dan ringkasan status akhir.

---

## 5. Rancangan Arsitektur & Skema Database

### 5.1 Arsitektur Komunikasi
* **Query & Mutation (CRUD):** Vue 3 (`@vue/apollo-composable`) $\leftrightarrow$ Laravel (`nuwave/lighthouse` GraphQL).
* **Real-Time Events:** Laravel Events (`ShouldBroadcast`) $\rightarrow$ Laravel Reverb Server $\rightarrow$ Vue 3 (`laravel-echo`).
* **Binary File Download:** Vue 3 (Axios `blob`) $\rightarrow$ Laravel REST Controller (`barryvdh/laravel-dompdf` — keputusan 2026-11-24).

### 5.2 Tabel Utama Database
1. **`users`**: `id`, `name`, `email`, `password`, `role` (`user`, `admin`, `agent`), `avatar_url`, `timestamps`.
2. **`tickets`**: `id`, `ticket_number` (unique), `user_id`, `admin_id` (nullable), `agent_id` (nullable), `title`, `description`, `priority` (`low`, `medium`, `high`), `status` (`open`, `in_progress`, `pending_review`, `resolved`, `closed`), `resolved_at` (nullable), `timestamps`.
3. **`ticket_milestones`**: `id`, `ticket_id`, `created_by`, `title`, `description` (nullable), `status` (pending, completed), `due_date` (nullable), `completed_at` (nullable), `timestamps`.
4. **`conversations`**: `id`, `ticket_id`, `type` (`user_admin`, `agent_admin`), `timestamps`.
5. **`messages`**: `id`, `conversation_id`, `sender_id`, `body`, `attachment_url` (nullable), `read_at` (nullable), `timestamps`.
6. **`notifications`**: Tabel bawaan Laravel untuk menyimpan payload notifikasi dan status `read_at`.

---

## 6. Spesifikasi API GraphQL (Ringkasan Kontrak)

* **Queries:**
  * `me`: Mengambil profil dan `role` pengguna aktif.
  * `tickets(search, status, priority, dateFrom, dateTo)`: Mengambil daftar tiket sesuai hak akses *role* (dengan paginasi).
  * `ticket(id)`: Mengambil detail tiket lengkap dengan relasi `milestones` dan `conversations`.
  * `dashboardStats`: Mengambil angka metrik dan distribusi status untuk kartu *dashboard*.
* **Mutations:**
  * `createTicket(input)`: Membuat tiket baru (Role: `user`).
  * `assignTicket(ticketId, agentId)`: Menerima tiket dan menugaskan ke Agent (Role: `admin`).
  * `createMilestone(ticketId, input)`, `updateMilestone(milestoneId, input)`, `deleteMilestone(milestoneId)` & `toggleMilestone(milestoneId)`: Mengelola *milestone* (Role: `admin`).
  * `submitTicketCompletion(ticketId, notes)`: Mengajukan penyelesaian ke Admin (Role: `agent`).
  * `resolveTicket(ticketId)` / `reopenTicket(ticketId)`: Verifikasi akhir penyelesaian tiket (Role: `admin`).
  * `sendMessage(conversationId, body)`: Mengirim pesan *chat* dan memicu *broadcast* Reverb.
  * `markConversationAsRead(conversationId)`: Menandai seluruh pesan belum terbaca sebagai terbaca.

---

## 7. Kebutuhan Non-Fungsional & Kriteria Penerimaan (*Acceptance Criteria*)

* **Keamanan & Privasi:**
  * *Agent* dan *User* tidak dapat mengakses *channel* atau membaca pesan dari percakapan yang bukan haknya (divalidasi di GraphQL Policy dan `routes/channels.php`).
  * Validasi transisi status tiket berjalan di sisi *server* (*backend*), bukan hanya disembunyikan di tombol UI.
* **Performa Real-Time:**
  * Pesan *chat* dan notifikasi baru tampil di layar penerima dalam waktu $< 1$ detik tanpa perlu memuat ulang halaman (*page reload*).
* **Konsistensi UI:**
  * Seluruh tampilan mengikuti token warna (`#0f172a`, `#f1f5f9`, `#ffffff`, `#2563eb`, `#3b82f6`, `#93c5fd`, `#e0f2fe`) dan tata letak yang didefinisikan pada `docs/UI_SPECIFICATION.md`.

---

## 8. Tahapan Rilis (*Milestones* Pengembangan)

1. **Phase 1 — Foundation & Auth:** Setup Laravel, Lighthouse GraphQL, Reverb, migrasi *database*, dan sistem login 3 *role*.
2. **Phase 2 — Core Ticketing & Milestones:** Implementasi pembuatan tiket, *assign agent*, *state machine* status, dan CRUD *milestone*.
3. **Phase 3 — Real-Time Chat Engine:** Integrasi Laravel Echo di Vue 3, *private channel* `user_admin` & `agent_admin`, *typing indicator*, dan *read receipts*.
4. **Phase 4 — Notifications & PDF Export:** Lonceng notifikasi *real-time* dan generator dokumen PDF tiket.
5. **Phase 5 — QA & Polishing:** Pengujian skenario lintas *role* secara bersamaan dan penyempurnaan responsivitas UI.