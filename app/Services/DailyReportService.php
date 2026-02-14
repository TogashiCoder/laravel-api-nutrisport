<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    public function getReportForDate(Carbon $date): array
    {
        $orderIds = Order::query()
            ->whereDate('created_at', $date->format('Y-m-d'))
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return [
                'date' => $date->format('Y-m-d'),
                'most_sold_product' => null,
                'least_sold_product' => null,
                'max_revenue_product' => null,
                'min_revenue_product' => null,
                'revenue_by_site' => [],
            ];
        }

        $quantityByProduct = OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_id', 'product_name')
            ->get();

        $revenueByProduct = OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->select('product_id', 'product_name', DB::raw('SUM(subtotal) as revenue'))
            ->groupBy('product_id', 'product_name')
            ->get();

        $mostSold = $quantityByProduct->sortByDesc('total_quantity')->first();
        $leastSold = $quantityByProduct->sortBy('total_quantity')->first();
        $maxRevenue = $revenueByProduct->sortByDesc('revenue')->first();
        $minRevenue = $revenueByProduct->sortBy('revenue')->first();

        $revenueBySite = Order::query()
            ->whereDate('created_at', $date->format('Y-m-d'))
            ->select('site_id', DB::raw('SUM(total) as total'))
            ->groupBy('site_id')
            ->get();

        $sites = Site::query()->whereIn('id', $revenueBySite->pluck('site_id'))->get()->keyBy('id');

        $revenueBySiteFormatted = $revenueBySite->map(fn ($row) => [
            'site_code' => $sites->get($row->site_id)?->code ?? (string) $row->site_id,
            'total' => (float) $row->total,
        ])->all();

        return [
            'date' => $date->format('Y-m-d'),
            'most_sold_product' => $mostSold ? [
                'product_name' => $mostSold->product_name,
                'quantity' => (int) $mostSold->total_quantity,
            ] : null,
            'least_sold_product' => $leastSold ? [
                'product_name' => $leastSold->product_name,
                'quantity' => (int) $leastSold->total_quantity,
            ] : null,
            'max_revenue_product' => $maxRevenue ? [
                'product_name' => $maxRevenue->product_name,
                'revenue' => (float) $maxRevenue->revenue,
            ] : null,
            'min_revenue_product' => $minRevenue ? [
                'product_name' => $minRevenue->product_name,
                'revenue' => (float) $minRevenue->revenue,
            ] : null,
            'revenue_by_site' => $revenueBySiteFormatted,
        ];
    }
}
