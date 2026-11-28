<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report {{ $label }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f9;font-family:Arial,Helvetica,sans-serif;color:#2b2c40;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f9;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border:1px solid #e7e9f0;border-radius:8px;">
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #e7e9f0;">
                        <h1 style="margin:0;font-size:20px;line-height:28px;">Report summary &middot; {{ $label }}</h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e7e9f0;">
                        <h2 style="margin:0 0 8px;font-size:15px;">Orders placed</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Orders</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['placed_count'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Order value</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['placed_amount'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Awaiting payment</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['placed_pending_payment'], 0) }}</td>
                            </tr>
                            @foreach ($summary['placed_by_status'] as $status)
                                <tr>
                                    <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">{{ $status['name'] }}</td>
                                    <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($status['orders'], 0) }} x BDT {{ number_format($status['total'], 0) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;">
                        <h2 style="margin:0 0 8px;font-size:15px;">Sales and profit</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Delivered orders</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['delivered_count'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Revenue (orders.amount)</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['revenue'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">COGS</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">- BDT {{ number_format($summary['cogs'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Courier cost</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">- BDT {{ number_format($summary['courier_cost'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Refunds paid</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">- BDT {{ number_format($summary['refund_paid'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;font-weight:bold;">Net profit</td>
                                <td style="padding:8px 0;text-align:right;font-weight:bold;">BDT {{ number_format($summary['profit'], 0) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e7e9f0;">
                        <h2 style="margin:0 0 8px;font-size:15px;">Payments</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Collected</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['paid'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Pending</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['pending'], 0) }}</td>
                            </tr>
                            @foreach ($summary['payment_methods'] as $method)
                                <tr>
                                    <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">{{ $method->method }}</td>
                                    <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format((int) $method->rows_count, 0) }} x BDT {{ number_format((float) $method->total, 0) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e7e9f0;">
                        <h2 style="margin:0 0 8px;font-size:15px;">Courier</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Shipments</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['courier_count'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Delivered</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['courier_delivered'], 0) }} ({{ number_format($summary['courier_rate'], 1) }}%)</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Cost</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['courier_cost'], 0) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e7e9f0;">
                        <h2 style="margin:0 0 8px;font-size:15px;">Returns</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Returns</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['return_count'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Refund required</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['refund_required'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Refunded</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['refunded'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Still pending</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['refund_pending'], 0) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e7e9f0;">
                        <h2 style="margin:0 0 8px;font-size:15px;">Coupons</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Usages</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['coupon_usages'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Discount given</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['coupon_discount'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Revenue on coupon orders</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">BDT {{ number_format($summary['coupon_revenue'], 0) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e7e9f0;">
                        <h2 style="margin:0 0 8px;font-size:15px;">Stock</h2>
                        <p style="margin:0 0 8px;font-size:12px;color:#697a8d;">
                            Warning at {{ $summary['stock']['warning'] }} pcs · remaining badge at {{ $summary['stock']['remaining'] }} pcs
                        </p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Products in the warning batch</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['stock']['count'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">Out of stock</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['stock']['out'], 0) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">At or below warning</td>
                                <td style="padding:6px 0;border-bottom:1px solid #f0f1f5;text-align:right;">{{ number_format($summary['stock']['low'], 0) }}</td>
                            </tr>
                        </table>
                        @if (count($summary['stock']['items']) > 0)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;border-collapse:collapse;margin-top:8px;">
                                <tr>
                                    <th align="left" style="padding:6px 0;border-bottom:1px solid #e7e9f0;color:#697a8d;">Product</th>
                                    <th align="left" style="padding:6px 0;border-bottom:1px solid #e7e9f0;color:#697a8d;">SKU</th>
                                    <th align="right" style="padding:6px 0;border-bottom:1px solid #e7e9f0;color:#697a8d;">Available</th>
                                </tr>
                                @foreach ($summary['stock']['items'] as $item)
                                    <tr>
                                        <td style="padding:5px 0;border-bottom:1px solid #f0f1f5;">{{ $item['product_name'] !== '' ? $item['product_name'] : 'missing product' }}</td>
                                        <td style="padding:5px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">{{ $item['sku'] !== '' ? $item['sku'] : 'missing variant' }}</td>
                                        <td align="right" style="padding:5px 0;border-bottom:1px solid #f0f1f5;">{{ number_format($item['available'], 0) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e7e9f0;">
                        <h2 style="margin:0 0 8px;font-size:15px;">Recent orders</h2>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;border-collapse:collapse;">
                            <tr>
                                <th align="left" style="padding:6px 0;border-bottom:1px solid #e7e9f0;color:#697a8d;">Order</th>
                                <th align="left" style="padding:6px 0;border-bottom:1px solid #e7e9f0;color:#697a8d;">Status</th>
                                <th align="right" style="padding:6px 0;border-bottom:1px solid #e7e9f0;color:#697a8d;">Amount</th>
                            </tr>
                            @foreach ($summary['recent_orders'] as $order)
                                <tr>
                                    <td style="padding:5px 0;border-bottom:1px solid #f0f1f5;">#{{ $order->id }}</td>
                                    <td style="padding:5px 0;border-bottom:1px solid #f0f1f5;color:#697a8d;">{{ $order->status_name }}</td>
                                    <td align="right" style="padding:5px 0;border-bottom:1px solid #f0f1f5;">BDT {{ number_format((float) $order->amount, 0) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e7e9f0;background:#fafafc;border-radius:0 0 8px 8px;">
                        <p style="margin:0;font-size:11px;color:#a1acb8;">
                            Z EVO admin report
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
