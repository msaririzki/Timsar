<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cell_towers', function (Blueprint $table): void {
            $table->id();
            $table->string('identity_key', 64)->unique();
            $table->string('radio_type', 16);
            $table->string('operator_name')->nullable();
            $table->string('operator_label')->nullable();
            $table->string('network_operator_code', 20)->nullable();
            $table->string('mcc', 10)->nullable();
            $table->string('mnc', 10)->nullable();
            $table->string('cell_id', 64);
            $table->string('tac_or_lac', 64)->nullable();
            $table->string('pci_or_psc', 64)->nullable();
            $table->decimal('estimated_latitude', 10, 7)->nullable();
            $table->decimal('estimated_longitude', 10, 7)->nullable();
            $table->unsignedInteger('observation_count')->default(0);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();
        });

        Schema::create('cell_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cell_tower_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_log_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->integer('signal_dbm')->nullable();
            $table->integer('rsrp_dbm')->nullable();
            $table->decimal('rsrq_db', 8, 2)->nullable();
            $table->decimal('sinr_db', 8, 2)->nullable();
            $table->boolean('is_registered')->default(true);
            $table->timestamp('observed_at')->index();
            $table->timestamps();
            $table->index(['assignment_id', 'observed_at']);
            $table->index(['user_id', 'observed_at']);
        });

        Schema::create('cell_handover_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_cell_tower_id')->constrained('cell_towers')->cascadeOnDelete();
            $table->foreignId('to_cell_tower_id')->constrained('cell_towers')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('observed_at')->index();
            $table->timestamps();
            $table->index(['assignment_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cell_handover_events');
        Schema::dropIfExists('cell_observations');
        Schema::dropIfExists('cell_towers');
    }
};
