<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Tests\TestCase;

/**
 * Admin landing-design manager slice.
 *
 * Pins the current shape of the module:
 *   - only the nine working design views remain (no legacy Blade/HTML files),
 *   - the manage page is the nine-design grid, not the six legacy landing rows,
 *   - cards carry a real image and View + Edit side by side,
 *   - the variable page is English and free of the old notices/stats,
 *   - every variable toggle persists to templates.config in the database,
 *   - campaign forms match the product form classes and use the full Quill toolbar.
 */
class AdminLandingDesignTest extends TestCase
{
    private array $originalConfigs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->originalConfigs as $id => $config) {
            $template = Template::find($id);
            if ($template) {
                $template->config = $config;
                $template->save();
            }
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user exists, so this test cannot authenticate.');

        return $admin;
    }

    private function designTemplates()
    {
        return Template::query()
            ->whereIn('view_name', ['ht2', 't1', 'th3', 'th4', 'th5', 'th6', 'th7', 'th8', 'th9'])
            ->orderBy('id')
            ->get();
    }

    public function test_only_the_nine_design_views_remain(): void
    {
        foreach (['default', 'herbal-health', 'sample-template', 'template-1', 'template-2', 'template-3'] as $legacy) {
            $this->assertFileDoesNotExist(resource_path('views/templates/' . $legacy . '.blade.php'));
        }

        $this->assertSame([], glob(resource_path('views/templates/*.html')), 'Legacy HTML files are still in the templates directory.');

        foreach (['ht2', 't1', 'th3', 'th4', 'th5', 'th6', 'th7', 'th8', 'th9'] as $view) {
            $this->assertFileExists(resource_path('views/templates/' . $view . '.blade.php'));
        }
    }

    public function test_manage_page_lists_the_nine_designs_with_view_and_edit(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/landing-pages/manage');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(9, substr_count($html, 'data-design-card'));
        $this->assertStringContainsString('Landing Page Designs', $html);
        $this->assertStringContainsString('Premium Collection', $html);
        $this->assertStringContainsString('Rose Editorial Abaya', $html);
        $this->assertStringContainsString('Soft Commerce Hoodie', $html);
        $this->assertStringContainsString('generated/ht2-hero.jpg', $html);
        $this->assertStringContainsString('generated/th9-hero.jpg', $html);
        $this->assertStringContainsString('> View', $html);
        $this->assertStringContainsString('> Edit', $html);
        $this->assertStringNotContainsString('T1 - Funnel checkout', $html);
        $this->assertStringNotContainsString('TH6 - Rose editorial', $html);
    }

    public function test_template_variables_page_is_english_and_clean(): void
    {
        $template = Template::query()->where('view_name', 'th6')->firstOrFail();

        $response = $this->actingAs($this->admin())
            ->get('/admin/landing-pages/' . $template->id . '/template-variables');

        $response->assertOk();
        $response->assertSee('Rose Editorial Abaya');
        $response->assertSee('Hero section');
        $response->assertSee('Trust badges');
        $response->assertSee('Order form');
        $response->assertDontSee('হিরো সেকশন');
        $response->assertDontSee('No saved list yet');
        $response->assertDontSee('Campaign form fields');
        $response->assertDontSee('TH6 - Rose editorial');
    }

    public function test_every_variable_toggle_persists_to_the_template_config(): void
    {
        $groups = (array) config('landing_templates.groups', []);
        $valid = [];

        foreach ($groups as $group) {
            $valid = array_merge($valid, array_keys((array) ($group['variables'] ?? [])));
        }

        $this->assertNotEmpty($valid, 'The landing-template registry has no variables.');

        $admin = $this->admin();
        $templates = $this->designTemplates();

        $this->assertCount(9, $templates);

        foreach ($templates as $template) {
            $this->originalConfigs[$template->id] = $template->config;

            $this->actingAs($admin)
                ->post(route('landing-pages.template-variables.update', $template->id), [
                    'enabled_variables' => $valid,
                ])
                ->assertRedirect(route('landing-pages.template-variables', $template->id));

            $saved = $template->fresh()->config['enabled_variables'] ?? null;
            $this->assertSame($valid, $saved, $template->view_name . ' did not persist every enabled variable.');

            $this->actingAs($admin)
                ->post(route('landing-pages.template-variables.update', $template->id), [
                    'enabled_variables' => [],
                ])
                ->assertRedirect(route('landing-pages.template-variables', $template->id));

            $this->assertSame([], $template->fresh()->config['enabled_variables'] ?? null, $template->view_name . ' did not persist an empty toggle set.');
        }
    }

    public function test_campaign_form_matches_product_form_and_uses_full_quill(): void
    {
        $campaignCreate = file_get_contents(resource_path('views/backEnd/campaign/create.blade.php'));
        $campaignEdit = file_get_contents(resource_path('views/backEnd/campaign/edit.blade.php'));
        $productCreate = file_get_contents(resource_path('views/backEnd/product/create.blade.php'));

        foreach ([$campaignCreate, $campaignEdit] as $html) {
            $this->assertStringContainsString('form-control rounded-0', $html);
            $this->assertStringContainsString('form-select rounded-0', $html);
            $this->assertStringContainsString('select2 form-select rounded-0', $html);
            $this->assertStringContainsString('data-quill-toolbar-preset="full"', $html);
            $this->assertStringNotContainsString('data-quill-toolbar-preset="mini"', $html);
            $this->assertStringNotContainsString('name="show_features"', $html);
            $this->assertStringNotContainsString('Section Visibility', $html);
        }

        $this->assertStringContainsString('form-control rounded-0', $productCreate);
        $this->assertStringContainsString('form-select rounded-0', $productCreate);
        $this->assertStringContainsString('data-quill-toolbar-preset="full"', $productCreate);

        $this->assertStringContainsString('name="campaign_price"', $campaignCreate);
        $this->assertStringContainsString('name="campaign_price_enabled"', $campaignCreate);
        $this->assertStringContainsString('name="allow_coupon"', $campaignCreate);
    }

    public function test_admin_layout_is_fluid_and_page_views_do_not_nest_containers(): void
    {
        $layout = file_get_contents(resource_path('views/backEnd/layouts/app.blade.php'));
        $navbar = file_get_contents(resource_path('views/backEnd/layouts/partials/navbar.blade.php'));
        $footer = file_get_contents(resource_path('views/backEnd/layouts/partials/footer.blade.php'));

        $this->assertStringContainsString('container-fluid flex-grow-1 container-p-y', $layout);
        $this->assertStringNotContainsString('container-xxl flex-grow-1 container-p-y', $layout);
        $this->assertStringContainsString('layout-navbar container-fluid', $navbar);
        $this->assertStringContainsString('<div class="container-fluid">', $footer);

        $offenders = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views/backEnd'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if (str_contains($path, '/layouts/')) {
                continue;
            }

            if (str_contains(file_get_contents($path), 'container-xxl flex-grow-1 container-p-y')) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders, 'Page views still nest the layout container: ' . implode(', ', $offenders));
    }

    public function test_campaign_content_panel_has_no_legacy_hints_or_feature_fields(): void
    {
        $partial = file_get_contents(resource_path('views/backEnd/campaign/partials/content-fields.blade.php'));

        $this->assertStringContainsString('Landing Content', $partial);
        $this->assertStringContainsString("contentFieldsFor", $partial);
        $this->assertStringNotContainsString('One row per item', $partial);
        $this->assertStringNotContainsString("Fields follow the selected template", $partial);
        $this->assertStringNotContainsString("'label' => 'Features'", $partial);
        $this->assertStringNotContainsString("'label' => 'Image URL'", $partial);
        $this->assertStringNotContainsString("Template 1 and 2 use the title", $partial);
    }
}
