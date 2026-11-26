<?php

namespace Tests\Feature;

use App\Models\Campaign;
use Tests\TestCase;

/**
 * Every landing design shows its OWN order form.
 *
 * The order form is one shared Blade partial (so the fields, the validation
 * and the order writing stay identical), but each design:
 *  - places it where its HTML twin in ~/Videos/lp places it;
 *  - restyles it in its own stylesheet with a scoped
 *    body[data-landing-template="<design>"] block.
 *
 * A page must still show exactly ONE order form, no matter where the design
 * puts it.
 */
class LandingOrderDesignTest extends TestCase
{
    use EnsuresDesignCampaigns;

    private const DESIGN_VIEWS = ['ht2', 't1', 'th3', 'th4', 'th5', 'th6', 'th7', 'th8', 'th9'];

    protected function tearDown(): void
    {
        $this->cleanupDesignCampaignFixtures();
        parent::tearDown();
    }

    /**
     * Where the HTML twin puts its order form, and the marker of the section
     * it has to sit before (or after).
     *
     * @var array<string, array{0: string, 1: string}> design => [position, marker]
     */
    private const POSITIONS = [
        // design => [marker of the section the form must come BEFORE, marker of the section it must come AFTER]
        'ht2' => ['id="faq"', 'id="gallery"'],
        't1' => ['zombie-reviews-section', 'zombie-showcase-section'],
        'th3' => ['id="faq"', 'id="gallery"'],
        'th4' => ['zombie-faq-card', 'zombie-review-card'],
        'th5' => ['zombie-faq-card', 'zombie-review-card'],
        'th6' => ['', 'id="details"'],
        'th7' => ['zombie-faq-section', 'zombie-reviews-section'],
        'th8' => ['', 'data-purpose="faq-accordion"'],
        'th9' => ['', 'data-purpose="faq-accordion"'],
    ];

    public function test_every_design_shows_exactly_one_order_form(): void
    {
        foreach ($this->campaignsByDesign() as $view => $campaign) {
            $html = $this->get('/campaign/' . $campaign->slug)->getContent();

            $this->assertSame(
                1,
                preg_match_all('~action="[^"]*/landing-order"~', $html),
                $view . ' must show exactly one landing order form.'
            );
            $this->assertSame(1, substr_count($html, 'id="landing-order"'));

            foreach (['name', 'address', 'phone', 'area', 'payment_method'] as $field) {
                $this->assertStringContainsString('name="' . $field . '"', $html, $view . ' lost the "' . $field . '" field.');
            }
        }
    }

    public function test_the_order_form_sits_where_the_design_places_it(): void
    {
        foreach ($this->campaignsByDesign() as $view => $campaign) {
            $html = $this->get('/campaign/' . $campaign->slug)->getContent();

            $form = strpos($html, 'id="landing-order"');
            $this->assertNotFalse($form, $view . ' does not render the order form.');

            [$before, $after] = self::POSITIONS[$view];

            if ($before !== '') {
                $marker = strpos($html, $before);
                $this->assertNotFalse($marker, $view . ' does not render the section "' . $before . '".');
                $this->assertLessThan(
                    $marker,
                    $form,
                    $view . ' must show its order form before "' . $before . '", like its HTML twin does.'
                );
            }

            if ($after !== '') {
                $marker = strpos($html, $after);
                $this->assertNotFalse($marker, $view . ' does not render the section "' . $after . '".');
                $this->assertGreaterThan(
                    $marker,
                    $form,
                    $view . ' must show its order form after "' . $after . '", like its HTML twin does.'
                );
            }
        }
    }

    public function test_every_design_stylesheet_carries_its_own_order_form_block(): void
    {
        foreach (self::DESIGN_VIEWS as $view) {
            $path = public_path('frontend/landing/' . $view . '.css');

            $this->assertFileExists($path, $view . ' has no stylesheet of its own.');

            $css = (string) file_get_contents($path);

            $this->assertStringContainsString(
                'body[data-landing-template="' . $view . '"]',
                $css,
                $view . ' must restyle the shared order form inside its own scoped block.'
            );
            $this->assertStringContainsString(
                '.zlt-order-card',
                $css,
                $view . ' must give the order card its own look.'
            );
            $this->assertStringContainsString(
                '.zlt-submit',
                $css,
                $view . ' must give the order button its own look.'
            );
        }
    }

    /**
     * @return \Illuminate\Support\Collection<string, Campaign>
     */
    private function campaignsByDesign()
    {
        $campaigns = collect();

        foreach (self::DESIGN_VIEWS as $view) {
            $campaigns->put($view, $this->activeCampaignForDesign($view));
        }

        return $campaigns;
    }
}
