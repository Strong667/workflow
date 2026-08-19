<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Администратор', 'email' => 'admin@workflow.test',   'role' => User::ROLE_ADMIN],
            ['name' => 'Менеджер',      'email' => 'manager@workflow.test', 'role' => User::ROLE_MANAGER],
            ['name' => 'Сотрудник',     'email' => 'user@workflow.test',    'role' => User::ROLE_EMPLOYEE],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data + ['password' => 'password', 'language' => 'ru', 'theme' => 'light']
            );
        }

        $departments = [
            'Разработка'   => 'Frontend, backend и мобильная разработка',
            'Дизайн'       => 'UI/UX, графика и брендинг',
            'Маркетинг'    => 'Продвижение, реклама и аналитика',
            'Продажи'      => 'Работа с клиентами и сделками',
            'HR'           => 'Подбор персонала и адаптация',
            'Поддержка'    => 'Техническая поддержка пользователей',
        ];

        foreach ($departments as $name => $description) {
            Department::updateOrCreate(['name' => $name], ['description' => $description]);
        }

        // Сотрудники, задачи и журнал не наполняются демо-данными:
        // записи заводятся через интерфейс. Фабрики остались на месте —
        // при необходимости набор поднимается командой db:seed --class=DemoSeeder.
    }
}
