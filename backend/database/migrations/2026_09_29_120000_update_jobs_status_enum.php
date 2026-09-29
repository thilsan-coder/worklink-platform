<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE jobs MODIFY COLUMN status ENUM('REQUESTED','ACCEPTED','SCHEDULED','IN_PROGRESS','ON_THE_WAY','ARRIVED','WORK_STARTED','WORK_COMPLETED','CUSTOMER_CONFIRMED','COMPLETED','CANCELLED','REJECTED','DISPUTED') NOT NULL DEFAULT 'REQUESTED'");
        }
    }

    public function down(): void
    {
        // Safe no-op
    }
};
