<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * User testing untuk setiap role.
     * Password default: password123
     */
    protected array $testUsers = [
        [
            'name' => 'Super Admin Zigmath',
            'email' => 'superadmin@zigmath.com',
            'password' => 'password123',
            'phone' => '081234567890',
            'position' => 'Super Administrator',
            'is_active' => true,
            'role' => 'super-admin',
        ],
        [
            'name' => 'Admin Zigmath',
            'email' => 'admin@zigmath.com',
            'password' => 'password123',
            'phone' => '081234567891',
            'position' => 'Administrator',
            'is_active' => true,
            'role' => 'admin',
        ],
        [
            'name' => 'Finance Zigmath',
            'email' => 'finance@zigmath.com',
            'password' => 'password123',
            'phone' => '081234567892',
            'position' => 'Staff Keuangan',
            'is_active' => true,
            'role' => 'finance',
        ],
        [
            'name' => 'Operator Zigmath',
            'email' => 'operator@zigmath.com',
            'password' => 'password123',
            'phone' => '081234567893',
            'position' => 'Staff Operasional',
            'is_active' => true,
            'role' => 'operator',
        ],
    ];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 Membuat user testing untuk setiap role...');
        $this->command->info('');

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($this->testUsers as $userData) {
            $roleName = $userData['role'];

            // ✅ Cek apakah role sudah ada
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                $this->command->warn("⚠️  Role '{$roleName}' belum ada. Jalankan RolePermissionSeeder terlebih dahulu.");
                $skippedCount++;

                continue;
            }

            // ✅ Simpan role name, lalu hapus dari data sebelum create
            unset($userData['role']);

            // ✅ Hash password
            $userData['password'] = Hash::make($userData['password']);

            // ✅ Buat user jika belum ada (firstOrCreate agar idempotent)
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // ✅ Jika user sudah ada, update password & is_active
            if ($user->wasRecentlyCreated === false) {
                $user->update([
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'is_active' => $userData['is_active'],
                    'phone' => $userData['phone'],
                    'position' => $userData['position'],
                ]);
            }

            // ✅ Sync role (hapus role lama, assign role baru)
            $user->syncRoles([$roleName]);

            $this->command->info("✅ User '{$user->name}' ({$user->email}) → Role: {$roleName}");
            $createdCount++;
        }

        $this->command->info('');
        $this->command->info("📊 Hasil: {$createdCount} user dibuat/diupdate, {$skippedCount} dilewati.");
        $this->command->info('');
        $this->command->info('📋 DAFTAR USER TESTING:');
        $this->command->info('┌─────────────────────────────────────────────────────────────────┐');
        $this->command->info('│ 👑 Super Admin : superadmin@zigmath.com  / password123         │');
        $this->command->info('│ 👨‍💼 Admin       : admin@zigmath.com      / password123         │');
        $this->command->info('│ 💰 Finance     : finance@zigmath.com    / password123         │');
        $this->command->info('│ 🎓 Operator    : operator@zigmath.com   / password123         │');
        $this->command->info('└─────────────────────────────────────────────────────────────────┘');
        $this->command->info('');
    }
}
