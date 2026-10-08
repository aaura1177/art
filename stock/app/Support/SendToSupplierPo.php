<?php

namespace App\Support;

use App\Notification;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SendToSupplierPo
{
    public static function columnExists(string $table): bool
    {
        return Schema::hasColumn($table, 'send_to_supplier_status');
    }

    /** Month-end POs go to supplier immediately (address_option 100 or M/ pono). */
    public static function isMonthEnd(?int $addressOption, ?string $pono = null): bool
    {
        if ((int) $addressOption === 100) {
            return true;
        }

        return $pono !== null && $pono !== '' && preg_match('/^M\//i', trim($pono));
    }

    /** Value for create(): 1 monthend, else 0. */
    public static function initialStatus(?int $addressOption, ?string $pono = null): int
    {
        return self::isMonthEnd($addressOption, $pono) ? 1 : 0;
    }

    /** Extra attributes for ::create() — empty when column not migrated yet. */
    public static function createAttributes(?int $addressOption, ?string $pono = null, ?string $table = null): array
    {
        if ($table === null || !self::columnExists($table)) {
            return [];
        }

        return ['send_to_supplier_status' => self::initialStatus($addressOption, $pono)];
    }

    public static function isSent(Model $po): bool
    {
        if (!self::columnExists($po->getTable())) {
            return true;
        }

        return (int) ($po->send_to_supplier_status ?? 1) === 1;
    }

    public static function notifySupplier(int $supplierId, string $pono): void
    {
        $user = User::where('supplier_id', $supplierId)->first();
        if (!$user) {
            return;
        }

        Notification::create([
            'user_id' => $user->id,
            'notification' => 'Received new Purchase Order - ' . $pono,
            'is_read' => 0,
        ]);
    }

    public static function markSent(Model $po): bool
    {
        if (!self::columnExists($po->getTable())) {
            return true;
        }

        if ((int) $po->send_to_supplier_status === 1) {
            return true;
        }

        $po->send_to_supplier_status = 1;
        if (!$po->save()) {
            return false;
        }

        self::notifySupplier((int) $po->supplier_id, (string) $po->pono);

        return true;
    }
}
