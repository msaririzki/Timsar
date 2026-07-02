<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->timestamp('closed_at')->nullable()->after('status')->index();
            $table->text('closure_notes')->nullable()->after('closed_at');
            $table->foreignId('closed_by')->nullable()->after('closure_notes')->constrained('users')->nullOnDelete();
        });

        DB::table('reports')
            ->whereIn('status', ['completed', 'cancelled'])
            ->whereNull('closed_at')
            ->update(['closed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropForeign(['closed_by']);
            $table->dropIndex(['closed_at']);
            $table->dropColumn(['closed_at', 'closure_notes', 'closed_by']);
        });
    }
};
