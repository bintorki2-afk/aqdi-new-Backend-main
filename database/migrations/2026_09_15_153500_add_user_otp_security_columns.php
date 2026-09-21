<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'verification_code_expires_at')) {
                $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            }
            if (! Schema::hasColumn('users', 'verification_attempts')) {
                $table->unsignedTinyInteger('verification_attempts')->default(0)->after('verification_code_expires_at');
            }
            if (! Schema::hasColumn('users', 'verification_locked_until')) {
                $table->timestamp('verification_locked_until')->nullable()->after('verification_attempts');
            }
            if (! Schema::hasColumn('users', 'reset_password_code_expires_at')) {
                $table->timestamp('reset_password_code_expires_at')->nullable()->after('reset_password_code');
            }
            if (! Schema::hasColumn('users', 'reset_password_attempts')) {
                $table->unsignedTinyInteger('reset_password_attempts')->default(0)->after('reset_password_code_expires_at');
            }
            if (! Schema::hasColumn('users', 'reset_password_locked_until')) {
                $table->timestamp('reset_password_locked_until')->nullable()->after('reset_password_attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'verification_code_expires_at',
                'verification_attempts',
                'verification_locked_until',
                'reset_password_code_expires_at',
                'reset_password_attempts',
                'reset_password_locked_until',
            ];

            $existing = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('users', $column)));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
