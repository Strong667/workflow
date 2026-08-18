<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
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

        if (Employee::count() === 0) {
            Employee::factory(48)->create();
        }

        if (Task::count() === 0) {
            Task::factory(70)->create();

            // Нормализуем порядок карточек внутри каждой колонки канбана.
            foreach (Task::STATUSES as $status) {
                Task::where('status', $status)
                    ->orderBy('id')
                    ->get()
                    ->each(fn (Task $task, int $index) => $task->updateQuietly(['position' => $index]));
            }
        }

        if (ActivityLog::count() === 0) {
            $admin = User::where('role', User::ROLE_ADMIN)->first();

            Task::latest()->limit(15)->get()->each(function (Task $task, int $i) use ($admin) {
                ActivityLog::create([
                    'user_id'     => $admin?->id,
                    'action'      => 'create',
                    'entity'      => 'Task',
                    'entity_id'   => $task->id,
                    'description' => "Создана задача «{$task->title}»",
                    'created_at'  => now()->subHours($i * 3),
                ]);
            });
        }
    }
}
