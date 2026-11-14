<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $employeeNumbers = [
        '堀内' => '2019',
        '金子' => '1003',
        '只川' => '2008',
        '星' => '3013',
        '安井' => '2023',
        '池上' => '5032',
        '安藤' => '5071',
        '権守' => '2014',
        '稲葉' => '2012',
        '久保田奈々' => '5083',
        '久保田' => '5053',
        '島貫' => '5090',
    ];

    public function up(): void
    {
        $this->addEmployeeNumbers();
        $this->createSubjects();
        $this->attachSubjects();
        $this->normalizeStatuses();
        $this->createBalls();
        $this->extendHistory();
        $this->dropParentLink();
        $this->requireSubject();
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_history', 'ball_id')) {
            Schema::table('project_history', function (Blueprint $table) {
                $table->dropConstrainedForeignId('ball_id');
            });
        }

        if (Schema::hasColumn('project_history', 'addressed_to')) {
            Schema::table('project_history', function (Blueprint $table) {
                $table->dropColumn(['kind', 'addressed_to']);
            });
        }

        Schema::dropIfExists('project_balls');

        if (Schema::hasColumn('projects', 'subject_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subject_id');
            });
        }

        Schema::dropIfExists('subjects');

        if (! Schema::hasColumn('projects', 'parent_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->constrained('projects')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('chatwork_members', 'employee_number')) {
            Schema::table('chatwork_members', function (Blueprint $table) {
                $table->dropUnique(['employee_number']);
                $table->dropColumn('employee_number');
            });
        }
    }

    private function addEmployeeNumbers(): void
    {
        if (! Schema::hasColumn('chatwork_members', 'employee_number')) {
            Schema::table('chatwork_members', function (Blueprint $table) {
                $table->string('employee_number', 20)->nullable()->unique()->after('member_name');
            });
        }

        foreach ($this->employeeNumbers as $name => $number) {
            $existing = DB::table('chatwork_members')->where('member_name', $name)->first();

            if ($existing) {
                DB::table('chatwork_members')
                    ->where('id', $existing->id)
                    ->update([
                        'employee_number' => $number,
                        'updated_at' => now(),
                    ]);
                continue;
            }

            $sortOrder = ((int) DB::table('chatwork_members')->max('sort_order')) + 1;

            DB::table('chatwork_members')->insert([
                'member_name' => $name,
                'employee_number' => $number,
                'chatwork_account_id' => '',
                'note' => 'ChatworkアカウントIDは未設定',
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createSubjects(): void
    {
        if (Schema::hasTable('subjects')) {
            return;
        }

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('created_by', 100);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    private function attachSubjects(): void
    {
        if (! Schema::hasColumn('projects', 'subject_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('projects', 'parent_id')) {
            return;
        }

        if (DB::table('projects')->whereNull('subject_id')->doesntExist()) {
            return;
        }

        $projects = DB::table('projects')->orderBy('id')->get()->keyBy('id');
        $subjectIds = [];

        foreach ($projects as $project) {
            $root = $project;

            while ($root->parent_id && isset($projects[$root->parent_id])) {
                $root = $projects[$root->parent_id];
            }

            $name = trim((string) $root->name);

            if ($name === '') {
                $name = '対象'.$root->id;
            }

            if (! isset($subjectIds[$name])) {
                $existing = DB::table('subjects')->where('name', $name)->value('id');
                $subjectIds[$name] = $existing ?: DB::table('subjects')->insertGetId([
                    'name' => $name,
                    'created_by' => '移行',
                    'created_at' => now(),
                ]);
            }

            $department = $project->department;
            $rootDepartment = $root->department;

            $updates = ['subject_id' => $subjectIds[$name]];

            if (
                $project->id !== $root->id
                && $this->isBlankDepartment($department)
                && ! $this->isBlankDepartment($rootDepartment)
            ) {
                $updates['department'] = $rootDepartment;
            }

            DB::table('projects')->where('id', $project->id)->update($updates);
        }
    }

    private function normalizeStatuses(): void
    {
        DB::table('projects')
            ->whereIn('status', ['未着手', 'レビュー中', '保留中'])
            ->update(['status' => '進行中']);

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE projects MODIFY status ENUM('進行中','中止','完了') NOT NULL DEFAULT '進行中'");
        DB::statement('ALTER TABLE project_history MODIFY status VARCHAR(20) NULL');
    }

    private function createBalls(): void
    {
        if (Schema::hasTable('project_balls')) {
            return;
        }

        Schema::create('project_balls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('assignee', 100);
            $table->string('ball_type', 20);
            $table->string('task', 255);
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('created_by', 100);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('done_at')->nullable();
            $table->index(['project_id', 'status']);
        });
    }

    private function extendHistory(): void
    {
        Schema::table('project_history', function (Blueprint $table) {
            if (! Schema::hasColumn('project_history', 'kind')) {
                $table->string('kind', 20)->default('comment');
            }

            if (! Schema::hasColumn('project_history', 'addressed_to')) {
                $table->string('addressed_to', 100)->nullable();
            }

            if (! Schema::hasColumn('project_history', 'ball_id')) {
                $table->foreignId('ball_id')->nullable()->constrained('project_balls')->nullOnDelete();
            }
        });
    }

    private function dropParentLink(): void
    {
        if (! Schema::hasColumn('projects', 'parent_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }

    private function requireSubject(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasColumn('projects', 'subject_id')) {
            return;
        }

        DB::statement('ALTER TABLE projects MODIFY subject_id BIGINT UNSIGNED NOT NULL');
    }

    private function isBlankDepartment(mixed $department): bool
    {
        return $department === null || $department === '' || $department === '選択なし';
    }
};
