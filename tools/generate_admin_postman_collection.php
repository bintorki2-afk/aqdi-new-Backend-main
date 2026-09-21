<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-API.postman_collection.json
 * and postman/AQDI-Admin-API.postman_environment.json
 *
 * Run: php tools/generate_admin_postman_collection.php
 */
$basePath = dirname(__DIR__);

function uuid4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function hdr(bool $bearer, bool $json = false): array
{
    $h = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ];
    if ($bearer) {
        $h[] = ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'];
    }
    if ($json) {
        $h[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }

    return $h;
}

/**
 * Postman v2.1 requires url as object (raw + host + path), not a string.
 *
 * @param  array<string, scalar|null>  $query
 * @return array<string, mixed>
 */
function buildUrl(string $adminPath, array $query = []): array
{
    $adminPath = '/'.trim($adminPath, '/');
    $segments = array_values(array_filter(explode('/', $adminPath)));

    $path = array_merge(['api', 'admin'], $segments);
    $queryItems = [];
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $queryItems[] = [
            'key' => (string) $key,
            'value' => (string) $value,
        ];
    }

    $raw = '{{baseUrl}}/'.implode('/', $path);
    if ($queryItems !== []) {
        $raw .= '?'.http_build_query(array_column($queryItems, 'value', 'key'));
    }

    $url = [
        'raw' => $raw,
        'host' => ['{{baseUrl}}'],
        'path' => $path,
    ];

    if ($queryItems !== []) {
        $url['query'] = $queryItems;
    }

    return $url;
}

function req(string $name, string $method, string $path, array $opts = []): array
{
    $bearer = $opts['bearer'] ?? false;
    $query = $opts['query'] ?? [];
    $body = $opts['body'] ?? null;
    $method = strtoupper($method);

    $request = [
        'method' => $method,
        'header' => hdr($bearer, $body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)),
        'url' => buildUrl($path, $query),
    ];

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    $item = [
        'name' => $name,
        'request' => $request,
        'response' => [],
    ];

    if (! empty($opts['description'])) {
        $item['request']['description'] = $opts['description'];
    }

    if (! empty($opts['save_token'])) {
        $item['event'] = [
            [
                'listen' => 'test',
                'script' => [
                    'exec' => [
                        'const json = pm.response.json();',
                        'const token = json?.data?.token ?? json?.token;',
                        'const refreshToken = json?.data?.refresh_token;',
                        'if (token) {',
                        '  pm.collectionVariables.set("employee_token", token);',
                        '  pm.environment.set("employee_token", token);',
                        '}',
                        'if (refreshToken) {',
                        '  pm.collectionVariables.set("employee_refresh_token", refreshToken);',
                        '  pm.environment.set("employee_refresh_token", refreshToken);',
                        '}',
                    ],
                    'type' => 'text/javascript',
                ],
            ],
        ];
    }

    return $item;
}

$folders = [
    '00 — Auth & setup' => [
        req('Employee login (save token)', 'POST', '/employees/login', [
            'body' => [
                'email' => 'admin@example.com',
                'password' => 'password',
                'remember_me' => true,
            ],
            'description' => 'Auto-saves access and refresh tokens. The response also returns role_id, role, role_title, is_system_admin, permissions, and permission_matrix.',
            'save_token' => true,
        ]),
        req('Refresh employee token', 'POST', '/employees/refresh-token', [
            'body' => [
                'refresh_token' => '{{employee_refresh_token}}',
            ],
            'description' => 'Rotates and auto-saves the new tokens. The response refreshes the employee role and effective permissions snapshot.',
            'save_token' => true,
        ]),
        req('My profile (permissions)', 'GET', '/employees/me', [
            'bearer' => true,
            'description' => 'Returns the authenticated employee with is_system_admin, permissions, permission_matrix, and permission_modules.',
        ]),
        req('My profile alias', 'GET', '/employees/profile', [
            'bearer' => true,
            'description' => 'Alias of GET /employees/me.',
        ]),
        req('App content overview', 'GET', '/app-content/overview'),
    ],
    'Employees' => [
        req('List employees', 'GET', '/employees', ['bearer' => true, 'query' => ['per_page' => 20]]),
        req('Employee salary list', 'GET', '/employees/employee-salary', ['bearer' => true]),
        req('Employee notes list', 'GET', '/employees/employee-notes', ['bearer' => true]),
        req('Create employee', 'POST', '/employees', [
            'bearer' => true,
            'body' => [
                'name' => 'موظف تجريبي',
                'email' => 'employee@example.com',
                'password' => 'password123',
                'phone' => '0500000000',
                'role_id' => 1,
                'work_period' => 'evening',
                'is_active' => true,
            ],
        ]),
        req('Show employee', 'GET', '/employees/{{employee_id}}', ['bearer' => true]),
        req('Update employee', 'POST', '/employees/{{employee_id}}', [
            'bearer' => true,
            'body' => ['name' => 'موظف محدّث', 'is_active' => true, 'work_period' => 'morning'],
        ]),
        req('Store salary', 'POST', '/employees/{{employee_id}}/salary', [
            'bearer' => true,
            'body' => [
                'basic_salary' => 5000,
                'deduction' => 0,
                'bonus' => 500,
                'total' => 5500,
                'notes' => 'راتب شهر مايو',
            ],
        ]),
        req('Store note', 'POST', '/employees/{{employee_id}}/note', [
            'bearer' => true,
            'body' => ['note' => 'ملاحظة على الموظف', 'addition_date' => '2026-05-23'],
        ]),
        req('Toggle status', 'POST', '/employees/{{employee_id}}/toggle-status', ['bearer' => true, 'body' => []]),
        req('Block employee', 'POST', '/employees/{{employee_id}}/block', ['bearer' => true]),
        req('Unblock employee', 'POST', '/employees/{{employee_id}}/unblock', ['bearer' => true]),
        req('Delete employee', 'POST', '/employees/{{employee_id}}/delete', ['bearer' => true]),
        req('Logout', 'POST', '/employees/logout', [
            'bearer' => true,
            'body' => [
                'refresh_token' => '{{employee_refresh_token}}',
            ],
        ]),
    ],
    'Analytics — dashboard' => [
        req('Analytics', 'GET', '/analytics'),
        req('Analytics all', 'GET', '/analytics/all'),
        req('Dashboard analytics', 'GET', '/dashboard-analytics'),
        req('Location analytics — cities', 'GET', '/analytics/locations/cities'),
        req('Location analytics', 'GET', '/analytics/locations'),
        req('User activity rate', 'GET', '/analytics/user-activity-rate'),
    ],
    'Analytics — customers' => [
        req('Top customers — completed orders', 'GET', '/analytics/top-customers/completed-orders'),
        req('Top customers — incomplete orders', 'GET', '/analytics/top-customers/incomplete-orders'),
        req('Top customers — orders', 'GET', '/analytics/top-customers/orders'),
        req('Top customers — returns', 'GET', '/analytics/top-customers/returns'),
        req('Top customers — real estates', 'GET', '/analytics/top-customers/real-estates'),
        req('Top customers — units', 'GET', '/analytics/top-customers/units'),
        req('Clients — completed orders', 'GET', '/analytics/clients/completed-orders'),
        req('Clients — orders', 'GET', '/analytics/clients/orders'),
    ],
    'Analytics — employees & refunds' => [
        req('Most received orders', 'GET', '/analytics/employees/most-received-orders'),
        req('Most returns', 'GET', '/analytics/employees/most-returns'),
        req('Most documented orders', 'GET', '/analytics/employees/most-documented-orders'),
        req('Employees count', 'GET', '/analytics/employees/count'),
        req('Most unpaid orders', 'GET', '/analytics/employees/most-unpaid-orders'),
        req('Refund contracts list', 'GET', '/analytics/refunds/contracts', ['query' => ['period' => 'month', 'per_page' => 20]]),
        req('Refund contract show', 'GET', '/analytics/refunds/contracts/{{id}}'),
    ],
    'Reports' => [
        req('Orders report', 'GET', '/reports/orders', ['query' => ['period' => 'last_30_days']]),
        req('Sales report', 'GET', '/reports/sales', ['query' => ['period' => 'last_30_days']]),
        req('Profits report — all periods (project-wide)', 'GET', '/reports/profits', [
            'bearer' => true,
            'query' => ['period' => 'all'],
            'description' => 'P&L for the whole project. Returns pnl[], kpis (incl. operating_profit_per_contract, monthly_break_even_contracts, cac, proration_days), collected_breakdown, unit_economics, source_summary, service_profitability. Fixed monthly costs (salaries, operating_budget, marketing_budget) are prorated by period days / 30.',
        ]),
        req('Profits report — last 30 days', 'GET', '/reports/profits', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
        ]),
        req('Customers report — all periods (project-wide)', 'GET', '/reports/customers', [
            'bearer' => true,
            'query' => ['period' => 'all'],
            'description' => 'Customers tab for the whole project. KPIs: total, new, returning, avg_contracts_per_customer, incomplete. Chart: segments (new vs returning). Table: top_customers (name, mobile, contracts_count, paid_count, total_spending). Optional query: contract_type=housing|commercial, employee_id.',
        ]),
        req('Customers report — last 30 days', 'GET', '/reports/customers', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
        ]),
        req('Customers report — housing', 'GET', '/reports/customers', [
            'bearer' => true,
            'query' => ['period' => 'all', 'contract_type' => 'housing'],
        ]),
        req('Performance report — all periods (project-wide)', 'GET', '/reports/performance', [
            'bearer' => true,
            'query' => ['period' => 'all'],
            'description' => 'Performance tab (لوحة الأداء) in one response. kpis (total_count, documented_count, working_count, canceled_count, refunded_count, revenue), conversion_funnel[] with from_previous_pct, conversion_leakage, conversion_rates[], daily_orders[], orders_by_status[] (non-zero only), by_contract_type[], by_employee[], operational_metrics (receive queue + 15-minute SLA), revenue_by_payment_method[], pnl[], unit_economics[], financial_summary[], by_document_type[] (grouped by instrument_type), correction_errors[] (always empty — no correction entity yet), refund_requests_by_status[], refund_requests_total. Optional query: contract_type=housing|commercial, employee_id. Salary lines in pnl[] only with employee_salaries.view.',
        ]),
        req('Performance report — last 30 days', 'GET', '/reports/performance', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
        ]),
        req('Performance report — housing, single employee', 'GET', '/reports/performance', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days', 'contract_type' => 'housing', 'employee_id' => 1],
        ]),
        req('Performance report — custom range', 'GET', '/reports/performance', [
            'bearer' => true,
            'query' => ['period' => 'custom', 'date_from' => '2026-08-01', 'date_to' => '2026-08-25'],
            'description' => 'date_from and date_to must be sent together (YYYY-MM-DD), otherwise 422.',
        ]),
        req('Profit settings — show', 'GET', '/reports/profit-settings', [
            'bearer' => true,
            'description' => 'Moyasar mada/credit percents + fixed fee, marketing_budget, operating_budget, meter_transfer_fee. monthly_salaries only if employee_salaries.view. Fixed costs prorate by period days / proration_month_days (30).',
        ]),
        req('Profit settings — update', 'PUT', '/reports/profit-settings', [
            'bearer' => true,
            'body' => [
                'moyasar_mada_percent' => 1.75,
                'moyasar_credit_percent' => 2.5,
                'moyasar_fixed_fee' => 1,
                'moyasar_fee_percent' => 2.5,
                'marketing_budget' => 6500,
                'operating_budget' => null,
                'monthly_salaries' => 13000,
                'meter_transfer_fee' => 10,
            ],
            'description' => 'moyasar_fee_percent is an alias for moyasar_credit_percent. monthly_salaries requires employee_salaries.edit. Advertising (marketing_budget) is project-wide. meter_transfer_fee is the catalog نقل العداد price.',
        ]),
        req('Marketing dashboard', 'GET', '/reports/marketing', ['query' => ['period' => 'last_30_days']]),
        req('Marketing UTM template', 'GET', '/reports/marketing/utm-template'),
        req('Import ad spend', 'POST', '/reports/marketing/spend', [
            'body' => [
                'rows' => [
                    [
                        'spent_on' => '2026-08-01',
                        'platform' => 'google',
                        'campaign_id' => '123',
                        'campaign_name' => 'Google - Awareness Campaign (Display)',
                        'spend' => 9300,
                        'currency' => 'SAR',
                    ],
                ],
            ],
        ]),
        req('Sync ad spend from ad accounts', 'POST', '/reports/marketing/sync', [
            'body' => ['days' => 7, 'platform' => 'google'],
        ]),
    ],
    'Marketing tracking' => [
        req('Overview (ROAS + widgets)', 'GET', '/marketing-tracking', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
            'description' => 'UI payload: summary (ROAS/spend/revenue/profit), kpis, spend-vs-revenue chart, top keywords/pages/campaigns, best/weakest campaign. Permission: analytics.view.',
        ]),
        req('Keyword ranking table', 'GET', '/marketing-tracking/keywords', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
            'description' => 'Summary cards + keyword rows (rank, previous, search volume, competition, status, revenue). Merges Search Console + UTM revenue.',
        ]),
        req('Funnel and paid channels', 'GET', '/marketing-tracking/channels', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
            'description' => 'Marketing funnel (impressions → clicks → leads → conversions) and paid-channel ROI table (spend, revenue, ROAS, CAC, profit).',
        ]),
    ],
    'Marketing content & reports' => [
        req('Service pages', 'GET', '/marketing/service-pages', [
            'bearer' => true,
            'description' => 'Content tab: landing pages list + summary counts. Permission: analytics.view.',
        ]),
        req('Create service page', 'POST', '/marketing/service-pages', [
            'bearer' => true,
            'body' => [
                'title' => 'توثيق عقد إيجار سكني',
                'path' => '/residential',
                'target_keyword' => 'عقد إيجار سكني',
                'status' => 'published',
            ],
        ]),
        req('Update service page', 'PUT', '/marketing/service-pages/{{id}}', [
            'bearer' => true,
            'body' => ['status' => 'published'],
        ]),
        req('Delete service page', 'DELETE', '/marketing/service-pages/{{id}}', ['bearer' => true]),
        req('Articles (marketing view)', 'GET', '/marketing/articles', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
            'description' => 'Blog list plus views/leads/revenue and editorial_queue. CRUD is /admin/blogs. Permission: blogs.view.',
        ]),
        req('Marketing report overview', 'GET', '/marketing/reports', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
        ]),
        req('Marketing report channels', 'GET', '/marketing/reports/channels', [
            'bearer' => true,
            'query' => ['period' => 'last_30_days'],
        ]),
        req('Export marketing report', 'POST', '/marketing/reports/export', [
            'bearer' => true,
            'body' => [
                'format' => 'csv',
                'period' => 'last_30_days',
                'channel' => 'all',
            ],
        ]),
    ],
    'SEO crawl' => [
        req('Dashboard (latest scan)', 'GET', '/seo-crawl', [
            'bearer' => true,
            'description' => 'Summary cards + category grid + data.progress (current/max/percent/current_url). Poll after POST /seo-crawl/run until status is completed, stopped, or failed.',
        ]),
        req('Dashboard by run_id', 'GET', '/seo-crawl', [
            'bearer' => true,
            'query' => ['run_id' => '{{seo_crawl_run_id}}'],
        ]),
        req('Start site scan', 'POST', '/seo-crawl/run', [
            'bearer' => true,
            'body' => [
                'url' => 'https://aqdi.sa',
                'max_pages' => 400,
            ],
            'description' => 'Queues a crawl. Returns 202 (queued). POST /seo-crawl is the same. 409 if a scan is already running. Permission: seo_crawl.create.',
        ]),
        req('Stop site scan', 'POST', '/seo-crawl/stop', [
            'bearer' => true,
            'body' => [
                'run_id' => '{{seo_crawl_run_id}}',
            ],
            'description' => 'Stops the in-progress crawl. Omit run_id to stop the current scan. 409 if nothing is running.',
        ]),
        req('Issues table', 'GET', '/seo-crawl/issues', [
            'bearer' => true,
            'query' => ['page' => 1, 'per_page' => 20],
            'description' => 'Issue rows: page, problem, severity. Optional query: type, severity=high|medium|low, search, run_id.',
        ]),
        req('Issues — 404 only', 'GET', '/seo-crawl/issues', [
            'bearer' => true,
            'query' => ['type' => 'page_404', 'severity' => 'high'],
        ]),
        req('Google SEO status', 'GET', '/seo-google/status', [
            'bearer' => true,
            'description' => 'Whether a real Google account is linked for Search Console + Analytics (read-only).',
        ]),
        req('Connect Google Search Console', 'POST', '/seo-google/connect', [
            'bearer' => true,
            'body' => (object) [],
            'description' => 'Returns data.auth_url. Open it, sign in with the Google account that owns Console, then Google redirects to the admin frontend.',
        ]),
        req('Disconnect Google Search Console', 'POST', '/seo-google/disconnect', [
            'bearer' => true,
            'body' => (object) [],
        ]),
        req('Search Console overview', 'GET', '/seo-google/search-console', [
            'bearer' => true,
            'query' => ['from' => '2026-08-01', 'to' => '2026-08-28'],
            'description' => 'Totals: clicks, impressions, CTR, average position. Permission: seo_crawl.view.',
        ]),
        req('Search Console sites', 'GET', '/seo-google/search-console/sites', [
            'bearer' => true,
            'description' => 'List Search Console properties on the connected Google account.',
        ]),
        req('Select Search Console site', 'POST', '/seo-google/search-console/sites', [
            'bearer' => true,
            'body' => ['site_url' => 'https://aqdi.sa/'],
            'description' => 'Persist the Search Console property used for reports. Permission: seo_crawl.create.',
        ]),
        req('Search Console queries', 'GET', '/seo-google/search-console/queries', [
            'bearer' => true,
            'query' => ['from' => '2026-08-01', 'to' => '2026-08-28', 'limit' => 25],
        ]),
        req('Search Console pages', 'GET', '/seo-google/search-console/pages', [
            'bearer' => true,
            'query' => ['from' => '2026-08-01', 'to' => '2026-08-28', 'limit' => 25],
        ]),
        req('Search Console countries', 'GET', '/seo-google/search-console/countries', [
            'bearer' => true,
            'query' => ['from' => '2026-08-01', 'to' => '2026-08-28'],
        ]),
        req('Search Console devices', 'GET', '/seo-google/search-console/devices', [
            'bearer' => true,
            'query' => ['from' => '2026-08-01', 'to' => '2026-08-28'],
        ]),
        req('Search Console dates', 'GET', '/seo-google/search-console/dates', [
            'bearer' => true,
            'query' => ['from' => '2026-08-01', 'to' => '2026-08-28'],
        ]),
        req('Search Console sitemaps', 'GET', '/seo-google/search-console/sitemaps', [
            'bearer' => true,
        ]),
    ],
    'Orders' => [
        req('List orders', 'GET', '/orders', ['query' => ['per_page' => 20, 'page' => 1]]),
        req('List orders — is_received', 'GET', '/orders', ['query' => ['is_received' => 1, 'per_page' => 20]]),
        req('Return orders', 'GET', '/orders/return', ['query' => ['per_page' => 20]]),
        req('Incomplete orders', 'GET', '/orders/incomplete/list', ['query' => ['per_page' => 20]]),
        req('Complete orders', 'GET', '/orders/complete/list', ['query' => ['per_page' => 20]]),
        req('Filter orders by date', 'GET', '/orders/filter', ['query' => ['created_at' => 'month', 'per_page' => 20]]),
        req('Show order (contract detail)', 'GET', '/orders/{{contract_id}}'),
        req('Update contract (partial) — JSON', 'POST', '/orders/{{contract_id}}', [
            'body' => [
                'annual_rent_amount_for_the_unit' => '334',
                'notes_edits' => 'تعديل من الأدمن',
            ],
            'description' => 'Send only fields to change. All contract columns optional except id, uuid, timestamps.',
        ]),
        req('Update contract status', 'POST', '/orders/{{contract_id}}/contract-status', [
            'body' => ['contract_status_id' => 1],
        ]),
        req('Return contract status (accept / reject)', 'POST', '/orders/{{contract_id}}/return-contract-status', [
            'bearer' => true,
            'body' => ['accept_retrun_contract' => true],
            'description' => 'Return order only (contract_status_id = 2). JSON: accept_retrun_contract true = accept, false = reject.',
        ]),
    ],
    'Orders — comments (Bearer)' => [
        req('List comments', 'GET', '/orders/{{contract_id}}/comments', ['bearer' => true, 'query' => ['per_page' => 20]]),
        req('Create comment', 'POST', '/orders/{{contract_id}}/comments', [
            'bearer' => true,
            'body' => ['body' => 'تعليق من الموظف على الطلب'],
        ]),
        req('Update comment', 'POST', '/orders/{{contract_id}}/comments/{{comment_id}}', [
            'bearer' => true,
            'body' => ['body' => 'تعليق محدّث'],
        ]),
        req('Delete comment', 'POST', '/orders/{{contract_id}}/comments/{{comment_id}}/delete', ['bearer' => true]),
    ],
    'Received contracts (Bearer)' => [
        req('Register receipt', 'POST', '/received-contracts', [
            'bearer' => true,
            'body' => [
                'contract_id' => '{{contract_id}}',
                'status' => 'pending',
                'date_of_received' => '2026-05-23',
                'notes' => 'تم الاستلام',
            ],
        ]),
        req('Show receipt', 'GET', '/received-contracts/{{contract_id}}', ['bearer' => true]),
        req('Update receipt — finish', 'PATCH', '/received-contracts/{{contract_id}}', [
            'bearer' => true,
            'body' => ['status' => 'finish', 'date_of_received' => '2026-05-23'],
        ]),
    ],
    'Users' => [
        req('All users', 'GET', '/users', ['query' => ['per_page' => 20, 'page' => 1, 'search' => '']]),
        req('Export users (CSV)', 'GET', '/users/export', [
            'query' => ['search' => '', 'platform' => 'website'],
            'description' => 'Same filters as GET /users. File download (CSV, UTF-8 BOM). Omit page to export all matching rows (max 10000).',
        ]),
        req('Show user (with contracts)', 'GET', '/users/{{user_id}}'),
        req('User properties / units', 'GET', '/users/{{user_id}}/properties', ['query' => ['per_page' => 20, 'page' => 1]]),
        req('Download property deed', 'GET', '/users/{{user_id}}/properties/{{property_id}}/deed'),
        req('Delete user property', 'DELETE', '/users/{{user_id}}/properties/{{property_id}}', [
            'description' => 'Blocked with 422 if the property or its units are linked to contracts.',
        ]),
        req('Delete user unit', 'DELETE', '/users/{{user_id}}/units/{{unit_id}}', [
            'description' => 'Blocked with 422 if the unit is linked to contracts.',
        ]),
        req('Apply custom discount / waiver', 'POST', '/users/{{user_id}}/discount', [
            'bearer' => true,
            'body' => [
                'contract_id' => '{{contract_id}}',
                'type' => 'percentage',
                'value' => 10,
                'reason' => 'خصم مخصص من الإدارة',
            ],
            'description' => 'type: percentage | fixed | waiver. value required unless waiver. Applies to an unpaid contract of this user and creates a coupon usage so payment totals update.',
        ]),
        req('Assign custom coupon to user', 'POST', '/users/{{user_id}}/coupons', [
            'bearer' => true,
            'body' => [
                'type' => 'percentage',
                'value' => 10,
                'applies_to' => 'all',
                'expires_at' => '2026-12-31',
                'reason' => 'عميل مميز',
                'notify_on_login' => true,
                'notification_message' => 'تهانينا! حصلت على خصم خاص على رسوم السنة الأولى.',
            ],
            'description' => 'User-scoped secret coupon on first-year fees. type: percentage|fixed. applies_to: all|housing|commercial. Returns secret_code. Shown as login_notification on next client login.',
        ]),
        req('List user custom coupons', 'GET', '/users/{{user_id}}/coupons', ['bearer' => true]),
        req('Show user custom coupon', 'GET', '/users/{{user_id}}/coupons/{{user_coupon_id}}', ['bearer' => true]),
        req('Deactivate user custom coupon', 'POST', '/users/{{user_id}}/coupons/{{user_coupon_id}}/deactivate', [
            'bearer' => true,
            'body' => [],
        ]),
        req('New users today', 'GET', '/users/new', ['query' => ['per_page' => 20]]),
        req('Users with completed contracts', 'GET', '/users/contracts-complete'),
        req('Toggle user block / unblock', 'POST', '/users/{{user_id}}/block', [
            'description' => 'If user is active → blocks. If blocked → unblocks. Response includes is_active.',
        ]),
        req('Delete user', 'POST', '/users/{{user_id}}/delete'),
    ],
    'Payments' => [
        req('List payments', 'GET', '/payments', ['query' => ['per_page' => 20, 'filter' => 'month']]),
        req('Show payment', 'GET', '/payments/{{payment_id}}'),
    ],
    'Payment types (طرق الدفع)' => [
        req('List payment types', 'GET', '/payment-types', ['query' => ['per_page' => 20, 'contract_type' => 'housing']]),
        req('Create payment type', 'POST', '/payment-types', [
            'body' => [
                'name_ar' => 'دفع شهري',
                'name_en' => 'Monthly Payment',
                'contract_type' => 'housing',
            ],
        ]),
        req('Show payment type', 'GET', '/payment-types/{{payment_type_id}}'),
        req('Update payment type', 'POST', '/payment-types/{{payment_type_id}}', [
            'body' => ['name_ar' => 'دفع شهري محدّث', 'name_en' => 'Monthly Updated'],
        ]),
        req('Delete payment type', 'POST', '/payment-types/{{payment_type_id}}/delete'),
    ],
    'Finance — expenses' => [
        req('List expenses', 'GET', '/finance/expenses', ['query' => ['created_at' => 'month']]),
        req('Create expense', 'POST', '/finance/expenses', [
            'body' => ['amount' => 250.5, 'notes' => 'مصروف مكتب', 'employee_id' => 1],
        ]),
        req('Show expense', 'GET', '/finance/expenses/{{expense_id}}'),
        req('Update expense', 'PUT', '/finance/expenses/{{expense_id}}', [
            'body' => ['amount' => 300, 'notes' => 'مصروف محدّث'],
        ]),
        req('Delete expense', 'DELETE', '/finance/expenses/{{expense_id}}'),
    ],
    'Finance — operating expenses' => [
        req('List operating expenses', 'GET', '/operating-expenses', ['query' => ['page' => 1, 'per_page' => 20]]),
        req('Create operating expense', 'POST', '/operating-expenses', [
            'body' => ['expense' => 'إيجار المكتب', 'amount' => 4500],
        ]),
        req('Show operating expense', 'GET', '/operating-expenses/{{operating_expense_id}}'),
        req('Update operating expense', 'POST', '/operating-expenses/{{operating_expense_id}}', [
            'body' => ['expense' => 'إيجار المكتب', 'amount' => 4800],
        ]),
        req('Delete operating expense', 'POST', '/operating-expenses/{{operating_expense_id}}/delete'),
    ],
    'Regions & cities' => [
        req('List regions', 'GET', '/regions', ['query' => ['per_page' => 20]]),
        req('Create region', 'POST', '/regions', ['body' => ['name_ar' => 'منطقة الرياض', 'name_en' => 'Riyadh Region']]),
        req('Show region', 'GET', '/regions/{{id}}'),
        req('Update region', 'POST', '/regions/{{id}}', ['body' => ['name_ar' => 'الرياض']]),
        req('Delete region', 'POST', '/regions/{{id}}/delete'),
        req('List cities', 'GET', '/cities', ['query' => ['per_page' => 20]]),
        req('Create city', 'POST', '/cities', ['body' => ['name_ar' => 'الرياض', 'name_en' => 'Riyadh', 'region_id' => 1]]),
        req('Show city', 'GET', '/cities/{{id}}'),
        req('Update city', 'POST', '/cities/{{id}}', ['body' => ['name_ar' => 'الرياض']]),
        req('Delete city', 'POST', '/cities/{{id}}/delete'),
    ],
    'Real estates' => [
        req('List real estates', 'GET', '/real-estates', ['query' => ['per_page' => 20, 'contract_type' => 'housing']]),
        req('Show real estate', 'GET', '/real-estates/{{id}}'),
        req('List real estate types', 'GET', '/real-estate-types'),
        req('Create real estate type', 'POST', '/real-estate-types', ['body' => ['name_ar' => 'شقة', 'name_en' => 'Apartment']]),
        req('Update real estate type', 'POST', '/real-estate-types/{{id}}', ['body' => []]),
        req('Delete real estate type', 'POST', '/real-estate-types/{{id}}/delete'),
        req('List real estate usages', 'GET', '/real-estate-usages', ['query' => ['per_page' => 20]]),
        req('Create real estate usage', 'POST', '/real-estate-usages', ['body' => ['name_ar' => 'سكني', 'name_en' => 'Residential']]),
        req('Show real estate usage', 'GET', '/real-estate-usages/{{id}}'),
        req('Update real estate usage', 'POST', '/real-estate-usages/{{id}}', ['body' => []]),
        req('Delete real estate usage', 'POST', '/real-estate-usages/{{id}}/delete'),
    ],
    'Units' => [
        req('List units', 'GET', '/unit-real-estates', ['query' => ['per_page' => 20, 'user_id' => 1]]),
        req('Create unit', 'POST', '/unit-real-estates', [
            'body' => [
                'user_id' => 1,
                'real_estates_units_id' => 1,
                'unit_number' => '101',
                'unit_area' => '120',
                'unit_type_id' => 1,
                'unit_usage_id' => 1,
            ],
        ]),
        req('Update unit', 'POST', '/unit-real-estates/{{id}}', ['body' => ['unit_number' => '102']]),
        req('Delete unit', 'POST', '/unit-real-estates/{{id}}/delete'),
        req('Unit types — search', 'GET', '/unit-types/search', ['query' => ['q' => 'شقة']]),
        req('Unit types — create form', 'GET', '/unit-types/create'),
        req('List unit types', 'GET', '/unit-types', ['query' => ['per_page' => 20]]),
        req('Create unit type', 'POST', '/unit-types', [
            'body' => ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'contract_type' => 'housing', 'rooms' => 'Room'],
        ]),
        req('Show unit type', 'GET', '/unit-types/{{id}}'),
        req('Update unit type', 'POST', '/unit-types/{{id}}', ['body' => ['name_ar' => 'شقة محدّثة']]),
        req('Delete unit type', 'POST', '/unit-types/{{id}}/delete'),
        req('Unit usages — create form', 'GET', '/unit-usages/create'),
        req('List unit usages', 'GET', '/unit-usages', ['query' => ['per_page' => 20]]),
        req('Create unit usage', 'POST', '/unit-usages', ['body' => ['name_ar' => 'سكني', 'name_en' => 'Residential']]),
        req('Show unit usage', 'GET', '/unit-usages/{{id}}'),
        req('Update unit usage', 'POST', '/unit-usages/{{id}}', ['body' => []]),
        req('Delete unit usage', 'POST', '/unit-usages/{{id}}/delete'),
    ],
    'Roles & permissions' => [
        req('Roles — create form', 'GET', '/roles/create'),
        req('List roles', 'GET', '/roles', ['query' => ['per_page' => 20]]),
        req('Create role', 'POST', '/roles', ['body' => [
            'name' => 'manager',
            'title_ar' => 'مدير',
            'title_en' => 'Manager',
            'is_active' => true,
            'permission_matrix' => [
                'all_requests' => ['view', 'edit'],
                'employees' => ['view'],
            ],
        ]]),
        req('Show role', 'GET', '/roles/{{id}}'),
        req('Update role', 'POST', '/roles/{{id}}', ['body' => ['title_ar' => 'مدير النظام']]),
        req('Assign permissions', 'POST', '/roles/{{id}}/assign-permissions', ['body' => [
            'permission_matrix' => [
                'all_requests' => ['view', 'edit'],
                'employees' => ['view'],
                'roles' => ['view'],
            ],
        ]]),
        req('Delete role', 'POST', '/roles/{{id}}/delete'),
        req('Permissions by section', 'GET', '/permissions/by-section'),
        req('Permissions — create form', 'GET', '/permissions/create'),
        req('List permissions', 'GET', '/permissions', ['query' => ['per_page' => 20]]),
        req('Create permission', 'POST', '/permissions', ['body' => ['section' => 'orders', 'actions' => ['view', 'edit']]]),
        req('Show permission', 'GET', '/permissions/{{id}}'),
        req('Update permission', 'POST', '/permissions/{{id}}', ['body' => []]),
        req('Delete permission', 'POST', '/permissions/{{id}}/delete'),
    ],
    'Contract config' => [
        req('Active contract statuses', 'GET', '/contract-statuses/active'),
        req('List contract statuses', 'GET', '/contract-statuses', ['query' => ['per_page' => 20]]),
        req('Create contract status', 'POST', '/contract-statuses', [
            'body' => ['name' => 'قيد المعالجة', 'color' => '#FFA500', 'is_active' => true],
        ]),
        req('Show contract status', 'GET', '/contract-statuses/{{id}}'),
        req('Update contract status', 'POST', '/contract-statuses/{{id}}', ['body' => ['name' => 'مكتمل']]),
        req('Delete contract status', 'POST', '/contract-statuses/{{id}}/delete'),
        req('List contract periods', 'GET', '/contract-periods', ['query' => ['per_page' => 20]]),
        req('Contract periods create helper', 'POST', '/contract-periods/create', ['body' => []]),
        req('Create contract period', 'POST', '/contract-periods', ['body' => ['period' => 'سنة واحدة', 'years' => 1]]),
        req('Show contract period', 'GET', '/contract-periods/{{id}}'),
        req('Update contract period', 'POST', '/contract-periods/{{id}}', ['body' => []]),
        req('Delete contract period', 'POST', '/contract-periods/{{id}}/delete'),
        req('Contract WhatsApp list', 'GET', '/contract-whatsapp', ['query' => ['per_page' => 20]]),
        req('Contract WhatsApp — complete', 'POST', '/contract-whatsapp/complete', ['body' => []]),
        req('Contract WhatsApp — incomplete', 'POST', '/contract-whatsapp/incomplete', ['body' => []]),
    ],
    'Coupons & paperwork & FAQs' => [
        req('List coupons', 'GET', '/coupons', ['query' => ['per_page' => 20]]),
        req('Create coupon', 'POST', '/coupons', [
            'body' => ['code' => 'SAVE10', 'discount' => 10, 'type' => 'percentage', 'is_active' => true],
        ]),
        req('Show coupon', 'GET', '/coupons/{{id}}'),
        req('Update coupon', 'POST', '/coupons/{{id}}', ['body' => []]),
        req('Activate coupon', 'POST', '/coupons/{{id}}/activate'),
        req('Deactivate coupon', 'POST', '/coupons/{{id}}/inactive'),
        req('Delete coupon', 'POST', '/coupons/{{id}}/delete'),
        req('List paperworks', 'GET', '/paperworks', ['query' => ['per_page' => 20]]),
        req('Create paperwork', 'POST', '/paperworks', ['body' => ['name_ar' => 'مستند', 'name_en' => 'Document']]),
        req('Show paperwork', 'GET', '/paperworks/{{id}}'),
        req('Update paperwork', 'POST', '/paperworks/{{id}}', ['body' => []]),
        req('Delete paperwork', 'POST', '/paperworks/{{id}}/delete'),
        req('List FAQs', 'GET', '/faqs', ['query' => ['per_page' => 20]]),
        req('Create FAQ', 'POST', '/faqs', [
            'body' => [
                'title_ar' => 'سؤال شائع',
                'title_en' => 'FAQ',
                'answer_ar' => 'الإجابة',
                'answer_en' => 'Answer',
            ],
        ]),
        req('Show FAQ', 'GET', '/faqs/{{id}}'),
        req('Update FAQ', 'POST', '/faqs/{{id}}', ['body' => []]),
        req('Delete FAQ', 'POST', '/faqs/{{id}}/delete'),
    ],
    'Customer messages (رسائل التطبيق للعميل)' => [
        req('Overview', 'GET', '/customer-messages/overview'),
        req('All (tree)', 'GET', '/customer-messages/all'),
        req('Create form', 'GET', '/customer-messages/create'),
        req('List messages', 'GET', '/customer-messages', ['query' => ['per_page' => 20]]),
        req('Create message', 'POST', '/customer-messages', [
            'body' => [
                'message_alert_section_id' => 1,
                'message_alert_section_item_id' => 1,
                'message' => 'نص رسالة توضيحية للعميل',
            ],
        ]),
        req('Show message', 'GET', '/customer-messages/{{message_alert_id}}'),
        req('Update message', 'POST', '/customer-messages/{{message_alert_id}}', [
            'body' => ['message' => 'نص محدّث'],
        ]),
        req('Delete message', 'POST', '/customer-messages/{{message_alert_id}}/delete'),
    ],
    'Message alerts — sections & items' => [
        req('Section options', 'GET', '/message-alert-sections/options/list', ['query' => ['type' => 'client']]),
        req('List sections', 'GET', '/message-alert-sections', ['query' => ['type' => 'client', 'per_page' => 20]]),
        req('Create section', 'POST', '/message-alert-sections', [
            'body' => ['name_ar' => 'قسم جديد', 'name_en' => 'New section', 'sort_order' => 0, 'type' => 'client'],
        ]),
        req('Show section', 'GET', '/message-alert-sections/{{message_alert_section_id}}'),
        req('Update section', 'POST', '/message-alert-sections/{{message_alert_section_id}}', ['body' => ['name_ar' => 'قسم محدّث']]),
        req('Delete section', 'POST', '/message-alert-sections/{{message_alert_section_id}}/delete'),
        req('Items under section', 'GET', '/message-alert-sections/{{message_alert_section_id}}/items', ['query' => ['per_page' => 20]]),
        req('Create item under section', 'POST', '/message-alert-sections/{{message_alert_section_id}}/items', [
            'body' => ['name_ar' => 'عنصر', 'name_en' => 'Item', 'sort_order' => 0],
        ]),
        req('Section item options', 'GET', '/message-alert-section-items/options/list', [
            'query' => ['message_alert_section_id' => '{{message_alert_section_id}}', 'type' => 'client'],
        ]),
        req('List section items (flat)', 'GET', '/message-alert-section-items', [
            'query' => ['type' => 'client', 'message_alert_section_id' => '{{message_alert_section_id}}'],
        ]),
        req('Create section item', 'POST', '/message-alert-section-items', [
            'body' => ['message_alert_section_id' => 1, 'name_ar' => 'عنصر', 'sort_order' => 0],
        ]),
        req('Show section item', 'GET', '/message-alert-section-items/{{message_alert_section_item_id}}'),
        req('Update section item', 'POST', '/message-alert-section-items/{{message_alert_section_item_id}}', ['body' => []]),
        req('Delete section item', 'POST', '/message-alert-section-items/{{message_alert_section_item_id}}/delete'),
    ],
    'Message alerts (legacy routes)' => [
        req('Types overview', 'GET', '/message-alerts/types'),
        req('All messages', 'GET', '/message-alerts/all', ['query' => ['type' => 'client']]),
        req('Client — create form', 'GET', '/message-alerts/client/create'),
        req('Client — list', 'GET', '/message-alerts/client', ['query' => ['per_page' => 20]]),
        req('Client — create', 'POST', '/message-alerts/client', [
            'body' => [
                'message_alert_section_id' => 1,
                'message_alert_section_item_id' => 1,
                'message' => 'رسالة للعميل',
            ],
        ]),
        req('Client — show', 'GET', '/message-alerts/client/{{message_alert_id}}'),
        req('Client — update', 'POST', '/message-alerts/client/{{message_alert_id}}', ['body' => ['message' => 'محدّث']]),
        req('Client — delete', 'POST', '/message-alerts/client/{{message_alert_id}}/delete'),
    ],
    'App status (open/close + mobile version)' => [
        req('Show website/mobile open flags and store versions', 'GET', '/settings/app-status', [
            'bearer' => true,
            'description' => 'website.is_open and mobile.is_open close/open the website and mobile app. ios/android hold latest_version, min_version, force_update, store_url.',
        ]),
        req('Close website', 'PUT', '/settings/app-status', [
            'bearer' => true,
            'body' => [
                'website' => ['is_open' => false],
            ],
        ]),
        req('Open website', 'PUT', '/settings/app-status', [
            'bearer' => true,
            'body' => [
                'website' => ['is_open' => true],
            ],
        ]),
        req('Close mobile app', 'PUT', '/settings/app-status', [
            'bearer' => true,
            'body' => [
                'mobile' => ['is_open' => false],
            ],
        ]),
        req('Open mobile app', 'PUT', '/settings/app-status', [
            'bearer' => true,
            'body' => [
                'mobile' => ['is_open' => true],
            ],
        ]),
        req('Update iOS version gate', 'PUT', '/settings/app-status', [
            'bearer' => true,
            'body' => [
                'ios' => [
                    'latest_version' => '1.2.0',
                    'min_version' => '1.1.0',
                    'force_update' => false,
                    'store_url' => 'https://apps.apple.com/app/aqdi',
                    'message_ar' => 'يرجى تحديث التطبيق لمتابعة الاستخدام',
                    'message_en' => 'Please update the app to continue',
                ],
            ],
        ]),
        req('Update Android version gate', 'PUT', '/settings/app-status', [
            'bearer' => true,
            'body' => [
                'android' => [
                    'latest_version' => '1.2.0',
                    'min_version' => '1.1.0',
                    'force_update' => false,
                    'store_url' => 'https://play.google.com/store/apps/details?id=sa.aqdi',
                    'message_ar' => 'يرجى تحديث التطبيق لمتابعة الاستخدام',
                    'message_en' => 'Please update the app to continue',
                ],
            ],
        ]),
        req('Toggle website_status via general settings', 'PUT', '/settings/general/website_status', [
            'bearer' => true,
            'body' => ['enabled' => false],
        ]),
        req('Toggle mobile_status via general settings', 'PUT', '/settings/general/mobile_status', [
            'bearer' => true,
            'body' => ['enabled' => false],
        ]),
    ],
    'Legal content (terms & privacy)' => [
        req('Legal pages (both)', 'GET', '/content/legal-pages'),
        req('Get terms', 'GET', '/content/terms-and-conditions'),
        req('Update terms', 'POST', '/content/terms-and-conditions', [
            'body' => [
                'description_ar' => '<p>الشروط والأحكام</p>',
                'description_en' => '<p>Terms and conditions</p>',
            ],
        ]),
        req('Get privacy', 'GET', '/content/privacy'),
        req('Update privacy', 'POST', '/content/privacy', [
            'body' => [
                'description_ar' => '<p>سياسة الخصوصية</p>',
                'description_en' => '<p>Privacy policy</p>',
            ],
        ]),
    ],
    'Blogs' => [
        req('List blogs', 'GET', '/blogs', ['query' => ['per_page' => 10, 'page' => 1]]),
        req('Blog statistics', 'GET', '/blogs/statistics'),
        req('Create blog', 'POST', '/blogs', [
            'body' => [
                'title' => 'عنوان المقال',
                'description' => 'محتوى المقال',
                'status' => 'published',
                'meta_title' => 'SEO title',
                'meta_description' => 'SEO description',
            ],
        ]),
        req('Show blog', 'GET', '/blogs/{{id}}'),
        req('Update blog', 'PUT', '/blogs/{{id}}', [
            'body' => ['title' => 'عنوان محدّث', 'description' => 'محتوى محدّث', 'status' => 'published'],
        ]),
        req('Toggle blog active', 'POST', '/blogs/{{id}}/toggle-active', ['body' => []]),
        req('Delete blog', 'DELETE', '/blogs/{{id}}'),
    ],
    'Ads' => [
        req('List ads', 'GET', '/ads', ['query' => ['per_page' => 20]]),
        req('Create ad', 'POST', '/ads', ['body' => ['title_ar' => 'إعلان', 'title_en' => 'Ad', 'is_active' => true]]),
        req('Show ad', 'GET', '/ads/{{id}}'),
        req('Update ad', 'POST', '/ads/{{id}}', ['body' => []]),
        req('Delete ad', 'POST', '/ads/{{id}}/delete'),
    ],
];

$items = [];
foreach ($folders as $folderName => $requests) {
    $items[] = [
        'name' => $folderName,
        'item' => $requests,
    ];
}

$collectionId = uuid4();
$environmentId = uuid4();

$collectionVars = [
    ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'default'],
    ['key' => 'employee_token', 'value' => '', 'type' => 'secret'],
    ['key' => 'employee_refresh_token', 'value' => '', 'type' => 'secret'],
    ['key' => 'employee_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'contract_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'comment_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'user_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'payment_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'payment_type_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'expense_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'operating_expense_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'id', 'value' => '1', 'type' => 'default'],
    ['key' => 'message_alert_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'message_alert_section_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'message_alert_section_item_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'seo_crawl_run_id', 'value' => '', 'type' => 'default'],
];

$collection = [
    'info' => [
        '_postman_id' => $collectionId,
        'name' => 'AQDI Admin API',
        'description' => "Complete Admin API under /api/admin.\n\n1. Import AQDI-Admin-API.postman_environment.json\n2. Set baseUrl\n3. Run Employee login to save access and refresh tokens automatically\n4. Login and refresh responses include the employee role and effective permissions matrix\n5. Protected requests require both authentication and the matching section.action permission",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        '_exporter_id' => 'aqdi-blade',
    ],
    'item' => $items,
    'variable' => $collectionVars,
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            ['key' => 'token', 'value' => '{{employee_token}}', 'type' => 'string'],
        ],
    ],
];

$environment = [
    'id' => $environmentId,
    'name' => 'AQDI Admin Local',
    'values' => array_map(
        static fn (array $v) => [
            'key' => $v['key'],
            'value' => $v['value'],
            'type' => $v['type'] ?? 'default',
            'enabled' => true,
        ],
        $collectionVars
    ),
    '_postman_variable_scope' => 'environment',
    '_postman_exported_at' => gmdate('c'),
    '_postman_exported_using' => 'Postman/11.0.0',
];

$postmanDir = $basePath.'/postman';
if (! is_dir($postmanDir)) {
    mkdir($postmanDir, 0755, true);
}

$collectionPath = $postmanDir.'/AQDI-Admin-API.postman_collection.json';
$environmentPath = $postmanDir.'/AQDI-Admin-API.postman_environment.json';

file_put_contents(
    $collectionPath,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

file_put_contents(
    $environmentPath,
    json_encode($environment, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

$requestCount = array_sum(array_map(fn ($f) => count($f), $folders));

echo "Wrote {$collectionPath}\n";
echo "Wrote {$environmentPath}\n";
echo 'Folders: '.count($folders).", Requests: {$requestCount}\n";
