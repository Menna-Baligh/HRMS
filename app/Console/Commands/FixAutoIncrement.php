<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixAutoIncrement extends Command
{
    protected $signature = 'db:fix-auto-increment';
    protected $description = 'Fix AUTO_INCREMENT for all tables with an id column';

    public function handle()
    {
        Schema::disableForeignKeyConstraints();

        // جلب جميع الجداول التي تمتلك عمود id وبدون auto_increment
        $columns = DB::select("
            SELECT TABLE_NAME, DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND COLUMN_NAME = 'id'
            AND EXTRA NOT LIKE '%auto_increment%'
        ");

        if (empty($columns)) {$this->info('جميع الجداول سليمة وتحتوي على AUTO_INCREMENT بالفعل! 🎉');
            Schema::enableForeignKeyConstraints();
            return;
        }

        foreach ($columns as$col) {
            $table =$col->TABLE_NAME;
            $type = strtolower($col->DATA_TYPE);

            $this->info("جاري إصلاح الجدول: {$table} ...");

            if ($type === 'bigint') {
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` BIGINT UNSIGNED AUTO_INCREMENT;");
            } else {
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` INT UNSIGNED AUTO_INCREMENT;");
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->info('تم إصلاح جميع الجداول بنجاح! 🚀');
    }
}
