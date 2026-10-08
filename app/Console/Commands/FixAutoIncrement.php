<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixAutoIncrement extends Command
{
    protected $signature = 'db:fix-auto-increment';

    protected $description = 'Fix AUTO_INCREMENT for numeric id columns across all tables';

    public function handle()
    {
        Schema::disableForeignKeyConstraints();

        $columns = DB::select("
        SELECT TABLE_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME = 'id'
        AND DATA_TYPE IN ('int', 'bigint', 'mediumint', 'smallint', 'tinyint')
        AND EXTRA NOT LIKE '%auto_increment%'
    ");

        if (empty($columns)) {
            $this->info('جميع الجداول الرقمية سليمة وتحتوي على AUTO_INCREMENT بالفعل! 🎉');
            Schema::enableForeignKeyConstraints();

            return;
        }

        foreach ($columns as $col) {
            $table = $col->TABLE_NAME;
            $type = strtolower($col->DATA_TYPE);

            $this->info("جاري إصلاح الجدول: {$table} ...");

            try {
                // إضافة Primary Key أولاً في حال عدم وجوده ثم إضافة AUTO_INCREMENT
                if ($type === 'bigint') {
                    DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY IF NOT EXISTS (`id`);");
                    DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` BIGINT UNSIGNED AUTO_INCREMENT;");
                } else {
                    DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY IF NOT EXISTS (`id`);");
                    DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` INT UNSIGNED AUTO_INCREMENT;");
                }
            } catch (\Exception $e) {
                // تجربة التعديل المباشر في حال كان Primary Key موجود بالفعل وMariaDB/MySQL أخرج خطأ syntax لـ IF NOT EXISTS
                try {
                    if ($type === 'bigint') {
                        DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY;");
                    } else {
                        DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY;");
                    }
                } catch (\Exception $ex) {
                    $this->error("تعذر إصلاح الجدول {$table}: ".$ex->getMessage());
                }
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->info('تمت عملية فحص وإصلاح كافة الجداول بنجاح! 🚀');
    }
}
