<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Services\DailyReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ReportsDailyCommand extends Command
{
    protected $signature = 'reports:daily {--date= : Date (Y-m-d) for report, default yesterday}';

    protected $description = 'Send daily J-1 report by email to admin';

    public function handle(DailyReportService $reportService): int
    {
        $dateStr = $this->option('date');
        $date = $dateStr ? Carbon::parse($dateStr) : Carbon::yesterday();

        $report = $reportService->getReportForDate($date);

        $adminEmail = config('mail.admin_email');
        if (!$adminEmail) {
            $this->warn('ADMIN_EMAIL not set. Skipping send.');
            return self::SUCCESS;
        }

        Mail::to($adminEmail)->send(new DailyReportMail($report));
        $this->info('Daily report sent to ' . $adminEmail . ' for ' . $report['date'] . '.');

        return self::SUCCESS;
    }
}
