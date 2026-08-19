<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Справочник сотрудников стал единым списком людей, поэтому у каждого
     * аккаунта должна быть карточка — иначе админ и менеджеры пропадают
     * со страницы «Сотрудники».
     */
    public function up(): void
    {
        User::doesntHave('employee')->each(function (User $user) {
            $parts = preg_split('/\s+/', trim($user->name), 2) ?: [];

            Employee::create([
                'user_id'    => $user->id,
                'first_name' => $parts[0] ?? $user->name,
                'last_name'  => $parts[1] ?? '',
                'email'      => $user->email,
                'avatar'     => $user->avatar,
                'position'   => match ($user->role) {
                    User::ROLE_ADMIN   => 'Администратор системы',
                    User::ROLE_MANAGER => 'Менеджер',
                    default            => null,
                },
            ]);
        });
    }

    public function down(): void
    {
        Employee::whereNotNull('user_id')->delete();
    }
};
