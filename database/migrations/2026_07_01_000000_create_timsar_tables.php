<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->string('team_code')->unique();
            $table->string('team_name');
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vehicle_type')->nullable();
            $table->unsignedInteger('member_count')->default(0);
            $table->string('status')->default('available')->index();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('position')->default('anggota');
            $table->boolean('is_leader')->default(false);
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('member_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->string('network_type')->default('unknown');
            $table->boolean('is_online')->default(true)->index();
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->string('tracking_code')->unique();
            $table->string('reporter_name');
            $table->string('reporter_phone');
            $table->string('incident_type');
            $table->text('description');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->string('status')->default('new')->index();
            $table->string('priority')->default('high')->index();
            $table->foreignId('assigned_member_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_member_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_type')->default('individual');
            $table->string('status')->default('assigned')->index();
            $table->timestamp('assigned_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('route_geometry_json')->nullable();
            $table->json('route_steps_json')->nullable();
            $table->timestamps();
        });

        Schema::create('location_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->string('network_type')->default('unknown');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info');
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('location_logs');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('member_locations');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
