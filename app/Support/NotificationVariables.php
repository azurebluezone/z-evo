<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderStatus;

/**
 * Builds the placeholder values a notification template can use.
 *
 * Keeping this in one place means a template cannot silently receive a
 * different `{order_number}` from the order event and the payment event.
 */
final class NotificationVariables
{
    /** @var array<string, string>|null */
    private static ?array $base = null;

    /**
     * Store-wide values shared by every template.
     *
     * @return array<string, string>
     */
    public static function base(): array
    {
        if (self::$base !== null) {
            return self::$base;
        }

        $setting = GeneralSetting::query()
            ->where('status', 1)
            ->first(['name', 'support_phone']);

        return self::$base = [
            'site_name' => (string) ($setting->name ?? config('app.name', 'Our store')),
            'support_phone' => (string) ($setting->support_phone ?? ''),
        ];
    }

    /**
     * @param array<string, scalar|null> $extra
     * @return array<string, string>
     */
    public static function forOrder(Order $order, array $extra = []): array
    {
        $order->loadMissing(['customer', 'payment', 'shipping', 'courierShipments']);

        $shipment = $order->courierShipments->sortByDesc('id')->first();
        $statusName = OrderStatus::query()->whereKey($order->order_status)->value('name');

        return array_merge(self::base(), [
            'customer_name' => (string) ($order->customer->name ?? $order->shipping->name ?? 'Customer'),
            'customer_phone' => (string) ($order->shipping->phone ?? $order->customer->phone ?? ''),
            'order_number' => (string) $order->invoice_id,
            'order_total' => number_format((float) $order->amount, 2, '.', ''),
            'order_status' => (string) ($statusName ?? ''),
            'payment_method' => (string) ($order->payment?->payment_method ?? ''),
            'payment_status' => (string) ($order->payment?->payment_status ?? 'pending'),
            'courier' => (string) ($shipment->provider ?? ''),
            'tracking_number' => (string) ($shipment->tracking_code ?? $shipment->consignment_id ?? ''),
            'support_note' => '',
        ], $extra);
    }

    /**
     * @param array<string, scalar|null> $extra
     * @return array<string, string>
     */
    public static function forCustomer(Customer $customer, array $extra = []): array
    {
        return array_merge(self::base(), [
            'customer_name' => (string) ($customer->name ?? 'Customer'),
            'customer_phone' => (string) ($customer->phone ?? ''),
        ], $extra);
    }
}
