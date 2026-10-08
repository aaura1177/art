<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class WholesaleShipmentLog extends Model
{
    public $timestamps = false;

    protected $table = 'wholesale_shipment_logs';

    protected $fillable = [
        'wholesale_shipment_id',
        'buyer_orderno',
        'action',
        'message',
        'meta',
        'user_id',
        'user_name',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public static function record(
        ?int $shipmentId,
        ?string $buyerOrderno,
        string $action,
        string $message,
        array $meta = [],
        ?Request $request = null
    ): self {
        $user = auth()->user();
        $name = null;
        if ($user) {
            $name = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
            if ($name === '') {
                $name = $user->email ?? ('User#' . $user->id);
            }
        }

        return self::create([
            'wholesale_shipment_id' => $shipmentId,
            'buyer_orderno' => $buyerOrderno,
            'action' => $action,
            'message' => $message,
            'meta' => $meta ?: null,
            'user_id' => $user->id ?? null,
            'user_name' => $name,
            'ip_address' => $request ? $request->ip() : (request()->ip() ?? null),
            'created_at' => now(),
        ]);
    }
}
