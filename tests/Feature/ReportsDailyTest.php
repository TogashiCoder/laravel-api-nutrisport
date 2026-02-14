<?php

namespace Tests\Feature;

use App\Mail\DailyReportMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportsDailyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.admin_email' => 'admin@test.com']);
        $this->seed(\Database\Seeders\SiteSeeder::class);
    }

    public function test_reports_daily_command_sends_email_with_report(): void
    {
        Mail::fake();

        $site = Site::query()->first();
        $user = User::query()->create([
            'name' => 'Customer',
            'email' => 'c@example.com',
            'password' => bcrypt('pass'),
            'site_id' => $site->id,
        ]);
        $address = \App\Models\Address::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Customer',
            'address_line' => 'Rue Test',
            'city' => 'Paris',
            'country' => 'France',
        ]);
        $product = Product::query()->create([
            'name' => 'Whey',
            'stock' => 10,
            'is_available' => true,
        ]);
        $reportDate = '2025-02-10';
        $orderCreatedAt = \Carbon\Carbon::parse($reportDate)->setTime(12, 0, 0);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'address_id' => $address->id,
            'total' => 59.98,
            'status' => 'pending',
            'remaining_amount' => 59.98,
        ]);
        $order->created_at = $orderCreatedAt;
        $order->save();
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Whey',
            'price' => 29.99,
            'quantity' => 2,
            'subtotal' => 59.98,
        ]);

        $this->artisan('reports:daily', ['--date' => $reportDate])->assertSuccessful();

        Mail::assertSent(DailyReportMail::class);
        $sent = Mail::sent(DailyReportMail::class)->first();
        $this->assertNotNull($sent);
        $report = $sent->report;
        $this->assertSame($reportDate, $report['date']);
        $this->assertNotNull($report['most_sold_product'], 'Report should have orders for this date');
        $this->assertSame('Whey', $report['most_sold_product']['product_name']);
        $this->assertSame(2, $report['most_sold_product']['quantity']);
        $this->assertNotEmpty($report['revenue_by_site']);
    }

    public function test_reports_daily_with_no_orders_sends_empty_report(): void
    {
        Mail::fake();

        $this->artisan('reports:daily', ['--date' => now()->subDays(2)->format('Y-m-d')])->assertSuccessful();

        Mail::assertSent(DailyReportMail::class, function ($mail) {
            return $mail->report['most_sold_product'] === null
                && $mail->report['revenue_by_site'] === [];
        });
    }
}
