<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opens every main screen as the super admin and checks it renders (HTTP 200).
 * Runs against the seeded demo data so real relationships are exercised.
 *
 *   php artisan test --filter=SmokeRoutesTest
 */
class SmokeRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_every_page_renders_for_super_admin(): void
    {
        $admin = User::where('email', 'admin@plant.com')->firstOrFail();

        $pages = [
            '/', '/profile', '/notifications',
            '/assets', '/assets/tree', '/assets/create', '/assets/1', '/assets/1/edit', '/assets/scan/CMP-01',
            '/requests', '/requests/create', '/requests/1', '/requests/2', '/requests/3', '/requests/4', '/requests/5', '/requests/6', '/requests/1/edit',
            '/work-orders', '/work-orders/create', '/work-orders/1', '/work-orders/2', '/work-orders/1/print',
            '/pm/plans', '/pm/plans/create', '/pm/plans/1', '/pm/plans/1/edit', '/pm/schedules', '/pm/logs', '/pm/checklists', '/pm/checklists/create', '/pm/checklists/1/edit',
            '/permits', '/permits/create', '/permits/create?work_order_id=1',
            '/spares', '/spares/create', '/spares/1', '/spares/1/edit', '/stock/ledger', '/stock/issue',
            '/purchase-requests', '/purchase-requests/create',
            '/daily-logs', '/daily-logs/create', '/daily-logs/create?log_type_id=1', '/handovers', '/handovers/create',
            '/amc', '/amc/create', '/amc/1', '/amc/1/edit', '/vendors', '/vendors/create', '/vendors/1', '/vendors/1/edit',
            '/compliances', '/compliances/create', '/compliances/1', '/compliances/1/edit',
            '/calibrations', '/calibrations/create', '/calibrations/1', '/calibrations/1/edit',
            '/reports',
            '/admin/users', '/admin/users/create', '/admin/users/2/edit', '/admin/roles', '/admin/roles/create', '/admin/roles/2/edit',
            '/admin/plants', '/admin/departments', '/admin/categories', '/admin/categories?type=spare', '/admin/shifts', '/admin/teams',
            '/admin/log-types', '/admin/log-types/create', '/admin/log-types/1/edit', '/admin/settings', '/admin/audit',
        ];

        foreach (array_keys(\App\Http\Controllers\ReportController::REPORTS) as $report) {
            $pages[] = '/reports/' . $report;
        }

        $failures = [];
        foreach ($pages as $page) {
            $response = $this->actingAs($admin)->get($page);
            if (!in_array($response->getStatusCode(), [200, 302])) {
                $failures[] = $page . ' => ' . $response->getStatusCode();
            }
        }

        $this->assertSame([], $failures, 'Pages that failed: ' . implode(', ', $failures));
    }

    public function test_reports_export_csv_and_pdf(): void
    {
        $admin = User::where('email', 'admin@plant.com')->firstOrFail();

        $csv = $this->actingAs($admin)->get('/reports/machine-capacity?export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('content-type'));

        $pdf = $this->actingAs($admin)->get('/reports/stock?export=pdf');
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/assets')->assertRedirect('/login');
    }
}
