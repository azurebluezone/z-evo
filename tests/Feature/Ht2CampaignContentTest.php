<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Template;
use App\Models\User;
use App\Support\LandingTemplateVars;
use Tests\TestCase;

/**
 * HT2 premium-collection slice.
 *
 * Pins the admin/visitor outcome of the content fix:
 *   - template labels are registry names only (no "HT2 - ..." or description),
 *   - both campaign forms expose a YouTube Video URL field,
 *   - the trust badge icon is a visual picker with FA previews,
 *   - the public page renders the normalized embed and authentic panjabi/saree
 *     gallery crops instead of the wallet placeholders.
 */
class Ht2CampaignContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
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

    public function test_ht2_page_video_gallery_and_campaign_forms(): void
    {
        $template = Template::query()->where('view_name', 'ht2')->firstOrFail();
        $campaign = Campaign::query()->where('slug', 'ht2-premium-collection')->firstOrFail();

        // 1. Template labels are the registry's human names.
        $this->assertSame('Premium Collection', LandingTemplateVars::displayName($template));

        foreach (Template::query()->whereIn('view_name', ['ht2', 't1', 'th3', 'th4', 'th5', 'th6', 'th7', 'th8', 'th9'])->get() as $design) {
            $label = LandingTemplateVars::displayName($design);
            $this->assertStringNotContainsString(' - ', $label, 'Legacy code prefix leaked into the label: ' . $label);
            $this->assertStringNotContainsString('New landing design.', $label);
        }

        // 2. YouTube URLs are accepted in every common shape.
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ',
            LandingTemplateVars::youtubeEmbedUrl('https://www.youtube.com/watch?v=aqz-KE-bpKQ')
        );
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ',
            LandingTemplateVars::youtubeEmbedUrl('https://www.youtube.com/shorts/aqz-KE-bpKQ')
        );
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ',
            LandingTemplateVars::youtubeEmbedUrl('https://youtu.be/aqz-KE-bpKQ?t=12')
        );
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ',
            LandingTemplateVars::youtubeEmbedUrl('https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ')
        );
        $this->assertSame(
            'https://vimeo.com/123456',
            LandingTemplateVars::youtubeEmbedUrl('https://vimeo.com/123456')
        );

        // 3. The HT2 template enables the video group and stores the video URL.
        $config = LandingTemplateVars::configArray($template);
        $this->assertContains('video_url', $config['enabled_variables']);
        $this->assertContains('video_caption', $config['enabled_variables']);
        $this->assertSame('https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ', $campaign->extra_fields['video_url'] ?? null);

        // 4. The campaign gallery is four authentic square crops.
        $gallery = $campaign->extra_fields['gallery_images'] ?? [];
        $this->assertCount(4, $gallery);

        foreach ($gallery as $path) {
            $this->assertStringStartsWith('frontend/landing/pictures/generated/ht2-gallery-', $path);
            $absolute = public_path($path);
            $this->assertFileExists($absolute);
            $size = getimagesize($absolute);
            $this->assertNotFalse($size);
            $this->assertSame(1000, $size[0], $path . ' is not a 1000px square.');
            $this->assertSame(1000, $size[1], $path . ' is not a 1000px square.');
        }

        // 5. The public page renders the embed and the new gallery, not wallets.
        $page = $this->get('/campaign/ht2-premium-collection');
        $page->assertOk();
        $page->assertSee('youtube-nocookie.com/embed/aqz-KE-bpKQ', false);
        $page->assertSee('ht2-gallery-01.jpg', false);
        $page->assertSee('ht2-gallery-04.jpg', false);
        $page->assertSee('প্রোডাক্ট ভিডিও', false);
        $page->assertDontSee('wallet-front.jpg', false);
        $page->assertDontSee('wallet-detail.jpg', false);

        // 6. Create and edit expose the same Video URL field and icon picker.
        $this->actingAs($this->admin());

        $create = $this->get('/admin/campaign/create');
        $create->assertOk();
        $create->assertSee('name="extra_fields[video_url]"', false);
        $create->assertSee('https://www.youtube.com/watch?v=VIDEO_ID', false);
        $create->assertSee('Premium Collection', false);
        $create->assertDontSee('HT2 - Premium product', false);
        $create->assertDontSee('New landing design.', false);
        $create->assertSee('js-icon-select', false);
        $create->assertSee('fa-solid fa-shield-halved', false);
        $create->assertSee('assets/global/css/all.min.css', false);

        $edit = $this->get('/admin/campaign/' . $campaign->id . '/edit');
        $edit->assertOk();
        $edit->assertSee('name="extra_fields[video_url]"', false);
        $edit->assertSee('https://www.youtube.com/watch?v=VIDEO_ID', false);
        $edit->assertSee('https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ', false);
        $edit->assertSee('js-icon-select', false);
        $edit->assertSee('Premium Collection', false);
        $edit->assertDontSee('HT2 - Premium product', false);
        $edit->assertDontSee('New landing design.', false);

        // 7. The campaign list also uses the clean name.
        $index = $this->get('/admin/campaign/manage');
        $index->assertOk();
        $index->assertSee('Premium Collection', false);
        $index->assertDontSee('HT2 - Premium product', false);

        // 8. The picker offers recognizable Font Awesome choices.
        $options = config('landing_templates.repeaters.trust_items.columns.icon.options', []);
        $this->assertGreaterThanOrEqual(8, count($options));
        $this->assertArrayHasKey('fa-solid fa-shield-halved', $options);
        $this->assertArrayHasKey('fa-solid fa-truck-fast', $options);
    }
}
