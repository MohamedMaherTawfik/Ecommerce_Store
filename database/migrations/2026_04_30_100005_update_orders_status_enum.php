<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->updateSqliteOrderStatusConstraint(includePaid: true);
        } else {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','paid','confirmed','shipped','delivered','cancelled') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->updateSqliteOrderStatusConstraint(includePaid: false);
        } else {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending'");
        }
    }

    private function updateSqliteOrderStatusConstraint(bool $includePaid): void
    {
        $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'orders'");

        if (! $row?->sql) {
            return;
        }

        $withPaid = "'pending', 'paid', 'confirmed', 'shipped', 'delivered', 'cancelled'";
        $withoutPaid = "'pending', 'confirmed', 'shipped', 'delivered', 'cancelled'";
        $sql = $includePaid
            ? str_replace($withoutPaid, $withPaid, $row->sql)
            : str_replace($withPaid, $withoutPaid, $row->sql);

        if ($sql === $row->sql) {
            return;
        }

        DB::statement('PRAGMA writable_schema = ON');
        DB::update("UPDATE sqlite_master SET sql = ? WHERE type = 'table' AND name = 'orders'", [$sql]);
        DB::statement('PRAGMA writable_schema = OFF');
    }
};
