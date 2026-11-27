<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {

            // Ambil akun dari UserSeeder
            $admin = User::where('role', 'admin')->firstOrFail();
            $agent = User::where('role', 'agent')->firstOrFail();
            $user = User::where('role', 'user')->firstOrFail();

            $tickets = [
                [
                    'number' => 'TKT-2026-0001',
                    'title' => 'Tidak bisa login ke aplikasi',
                    'description' => 'Saya selalu mendapatkan pesan error ketika mencoba login.',
                    'priority' => 'high',
                    'status' => 'open',
                    'agent_id' => null,
                    'resolved_at' => null,
                    'milestones' => [
                        ['title' => 'Verifikasi laporan', 'status' => 'pending'],
                    ],
                ],
                [
                    'number' => 'TKT-2026-0002',
                    'title' => 'Halaman dashboard lambat',
                    'description' => 'Dashboard membutuhkan waktu cukup lama untuk dimuat.',
                    'priority' => 'medium',
                    'status' => 'in_progress',
                    'agent_id' => $agent->id,
                    'resolved_at' => null,
                    'milestones' => [
                        ['title' => 'Analisis masalah', 'status' => 'completed'],
                        ['title' => 'Optimasi dashboard', 'status' => 'pending'],
                    ],
                ],
                [
                    'number' => 'TKT-2026-0003',
                    'title' => 'Kesalahan pada laporan transaksi',
                    'description' => 'Total transaksi tidak sesuai dengan data sebenarnya.',
                    'priority' => 'high',
                    'status' => 'pending_review',
                    'agent_id' => $agent->id,
                    'resolved_at' => null,
                    'milestones' => [
                        ['title' => 'Investigasi masalah', 'status' => 'completed'],
                        ['title' => 'Perbaikan laporan', 'status' => 'completed'],
                        ['title' => 'Review hasil perbaikan', 'status' => 'pending'],
                    ],
                ],
                [
                    'number' => 'TKT-2026-0004',
                    'title' => 'Tidak menerima email notifikasi',
                    'description' => 'Email notifikasi tidak masuk ketika transaksi berhasil.',
                    'priority' => 'low',
                    'status' => 'resolved',
                    'agent_id' => $agent->id,
                    'resolved_at' => now()->subDays(1),
                    'milestones' => [
                        ['title' => 'Pemeriksaan email service', 'status' => 'completed'],
                        ['title' => 'Perbaikan notifikasi', 'status' => 'completed'],
                    ],
                ],
            ];

            foreach ($tickets as $index => $data) {

                // Hindari duplikasi ketika seeder dijalankan ulang
                $exists = DB::table('tickets')
                    ->where('ticket_number', $data['number'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $ticketId = DB::table('tickets')->insertGetId([
                    'ticket_number' => $data['number'],
                    'user_id' => $user->id,
                    'admin_id' => $admin->id,
                    'agent_id' => $data['agent_id'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'priority' => $data['priority'],
                    'status' => $data['status'],
                    'resolved_at' => $data['resolved_at'],
                    'created_at' => now()->subDays(4 - $index),
                    'updated_at' => now(),
                ]);

                // Buat conversation
                $conversationTypes = ['user_admin'];

                if ($data['agent_id'] !== null) {
                    $conversationTypes[] = 'agent_admin';
                }

                foreach ($conversationTypes as $type) {

                    $conversationId = DB::table('conversations')
                        ->insertGetId([
                            'ticket_id' => $ticketId,
                            'type' => $type,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    // Pesan untuk conversation user_admin
                    if ($type === 'user_admin') {
                        $messages = [
                            [
                                'sender_id' => $user->id,
                                'body' => 'Halo, saya ingin melaporkan masalah ini.',
                            ],
                            [
                                'sender_id' => $admin->id,
                                'body' => 'Terima kasih sudah melaporkan. Kami akan memeriksanya.',
                            ],
                        ];
                    }

                    // Pesan untuk conversation agent_admin
                    else {
                        $messages = [
                            [
                                'sender_id' => $agent->id,
                                'body' => 'Saya sudah mulai memeriksa masalah ini.',
                            ],
                            [
                                'sender_id' => $admin->id,
                                'body' => 'Baik, silakan lanjutkan proses penanganannya.',
                            ],
                        ];
                    }

                    foreach ($messages as $message) {
                        DB::table('messages')->insert([
                            'conversation_id' => $conversationId,
                            'sender_id' => $message['sender_id'],
                            'body' => $message['body'],
                            'attachment_url' => null,
                            'read_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Buat milestones
                foreach ($data['milestones'] as $milestone) {

                    $completed = $milestone['status'] === 'completed';

                    DB::table('ticket_milestones')->insert([
                        'ticket_id' => $ticketId,
                        'created_by' => $data['agent_id'] ?? $admin->id,
                        'title' => $milestone['title'],
                        'description' => null,
                        'status' => $milestone['status'],
                        'due_date' => now()->addDays(2)->toDateString(),
                        'completed_at' => $completed ? now() : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
