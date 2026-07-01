<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MemberLocation;
use App\Models\Report;
use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@timsar.test'],
            [
                'name' => 'Admin Posko TIMSAR',
                'password' => 'password',
                'role' => 'admin',
                'phone' => '081111111111',
                'status' => 'online',
            ],
        );

        $members = [
            ['Andi Pratama', 'andi@timsar.test', '081222222201', -8.583350, 116.095780, 'wifi'],
            ['Budi Santoso', 'budi@timsar.test', '081222222202', -8.588900, 116.117210, '4g'],
            ['Sari Wulandari', 'sari@timsar.test', '081222222203', -8.573650, 116.103300, '4g'],
            ['Raka Mahendra', 'raka@timsar.test', '081222222204', -8.607420, 116.085700, 'unknown'],
            ['Nadia Lestari', 'nadia@timsar.test', '081222222205', -8.548500, 116.071900, 'wifi'],
        ];

        $createdMembers = collect($members)->map(function (array $data): User {
            [$name, $email, $phone, $lat, $lng, $network] = $data;
            $member = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'password',
                    'role' => 'member',
                    'phone' => $phone,
                    'status' => 'online',
                ],
            );

            MemberLocation::query()->updateOrCreate(
                ['user_id' => $member->id],
                [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'accuracy' => 12,
                    'speed' => 0,
                    'network_type' => $network,
                    'is_online' => true,
                    'last_seen_at' => now(),
                ],
            );

            return $member;
        });

        $team = Team::query()->firstOrCreate(
            ['team_code' => 'TSR-NTB-01'],
            [
                'team_name' => 'Regu Cepat Mataram',
                'leader_id' => $createdMembers[0]->id,
                'vehicle_type' => 'Mobil Rescue',
                'member_count' => 3,
                'status' => 'available',
            ],
        );

        foreach ($createdMembers->take(3) as $index => $member) {
            $team->members()->syncWithoutDetaching([
                $member->id => [
                    'position' => $index === 0 ? 'ketua' : 'anggota',
                    'is_leader' => $index === 0,
                ],
            ]);
        }

        Report::query()->firstOrCreate(
            ['tracking_code' => 'TSR-DEMO-001'],
            [
                'reporter_name' => 'Warga Demo',
                'reporter_phone' => '081333333333',
                'incident_type' => 'Kecelakaan ringan',
                'description' => 'Korban membutuhkan bantuan evakuasi awal di area Mataram.',
                'latitude' => -8.586020,
                'longitude' => 116.100780,
                'accuracy' => 18,
                'status' => 'new',
                'priority' => 'high',
            ],
        );
    }
}
