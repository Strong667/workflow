<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Демонстрационный набор: сотрудники, задачи и записи журнала.
 * Основной сидер его не запускает — вызывается вручную:
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
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
