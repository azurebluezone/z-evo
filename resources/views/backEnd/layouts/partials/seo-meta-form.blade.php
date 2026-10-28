@php
    $seoAdvancedCollapseId = 'seoAdvancedCollapse_' . substr(md5((string) get_class($model) . '|' . (string) ($model->id ?? 'new')), 0, 10);

    // The field names are array keys (seo_meta[meta_title]). The x-admin
    // components resolve old()/errors by the literal name, and Laravel's
    // old() reads dot notation, so the previous value is resolved here and
    // passed in as :value / :selected. There are no validation rules on
    // these fields today, so the bracket name cannot miss a message.
    $seoValue = function (string $key, $default = '') use ($model) {
        return old('seo_meta.' . $key, $model->seoMeta->{$key} ?? $default);
    };
@endphp

<div class="row">
    <div class="col-md-12">
        <x-admin.input name="seo_meta[meta_title]" id="meta_title" label="Meta Title"
                       :value="$seoValue('meta_title')"
                       maxlength="60"
                       placeholder="e.g. Premium Wireless Bluetooth Headphones" />
    </div>

    <div class="col-md-12">
        <x-admin.textarea name="seo_meta[meta_description]" id="meta_description" label="Meta Description"
                          :value="$seoValue('meta_description')"
                          rows="3" maxlength="160"
                          placeholder="e.g. High-quality Bluetooth headphones with noise cancellation and premium sound quality." />
    </div>

    <div class="col-md-6">
        <x-admin.input name="seo_meta[meta_keywords]" id="meta_keywords" label="Meta Keywords"
                       :value="$seoValue('meta_keywords')"
                       help="Comma-separated keywords for SEO"
                       placeholder="e.g. headphones, bluetooth, wireless, audio" />
    </div>

    <div class="col-md-6">
        <x-admin.select name="seo_meta[robots]" id="robots" label="Robots"
                        :options="[
                            'index,follow' => 'Index, Follow',
                            'noindex,follow' => 'No Index, Follow',
                            'index,nofollow' => 'Index, No Follow',
                            'noindex,nofollow' => 'No Index, No Follow',
                        ]"
                        :selected="$seoValue('robots', 'index,follow')"
                        help="Search engine crawling instructions" />
    </div>

    <div class="col-md-6">
        <x-admin.input name="seo_meta[meta_image]" id="meta_image" label="Meta Image" type="file"
                       accept="image/*"
                       help="Image for social sharing (recommended: 1200x630 pixels)" />

        @if($model->seoMeta->meta_image ?? false)
            <div class="mt-2">
                <img src="{{ asset($model->seoMeta->meta_image) }}"
                     alt="Current SEO image"
                     class="img-thumbnail"
                     style="max-width: 200px;">
                <br>
                <small class="text-muted">Current image</small>
            </div>
        @endif
    </div>

    <div class="col-md-6">
        <x-admin.input name="seo_meta[canonical_url]" id="canonical_url" label="Canonical URL" type="url"
                       :value="$seoValue('canonical_url')"
                       help="Preferred URL for this content"
                       placeholder="https://example.com/page" />
    </div>

    <div class="col-12">
        <button
            class="btn btn-outline-secondary btn-sm btn-square"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $seoAdvancedCollapseId }}"
            aria-expanded="false"
            aria-controls="{{ $seoAdvancedCollapseId }}">
            Advanced Social
        </button>
    </div>

    <div class="col-12">
        <div class="collapse mt-3" id="{{ $seoAdvancedCollapseId }}">
            <div class="row">
                <div class="col-12">
                    <hr>
                    <h6 class="mb-3">Open Graph (Facebook) Tags</h6>
                </div>

                <div class="col-md-6">
                    <x-admin.input name="seo_meta[og_title]" id="og_title" label="OG Title"
                                   :value="$seoValue('og_title')"
                                   placeholder="Title for social sharing" />
                </div>

                <div class="col-md-6">
                    <x-admin.input name="seo_meta[og_image]" id="og_image" label="OG Image"
                                   :value="$seoValue('og_image')"
                                   placeholder="URL to social image" />
                </div>

                <div class="col-md-12">
                    <x-admin.textarea name="seo_meta[og_description]" id="og_description" label="OG Description"
                                      :value="$seoValue('og_description')"
                                      rows="2"
                                      placeholder="Description for social sharing" />
                </div>

                <div class="col-12">
                    <hr>
                    <h6 class="mb-3">Twitter Card Tags</h6>
                </div>

                <div class="col-md-6">
                    <x-admin.input name="seo_meta[twitter_title]" id="twitter_title" label="Twitter Title"
                                   :value="$seoValue('twitter_title')"
                                   placeholder="Title for Twitter cards" />
                </div>

                <div class="col-md-6">
                    <x-admin.input name="seo_meta[twitter_image]" id="twitter_image" label="Twitter Image"
                                   :value="$seoValue('twitter_image')"
                                   placeholder="URL to Twitter image" />
                </div>

                <div class="col-md-12">
                    <x-admin.textarea name="seo_meta[twitter_description]" id="twitter_description" label="Twitter Description"
                                      :value="$seoValue('twitter_description')"
                                      rows="2"
                                      placeholder="Description for Twitter cards" />
                </div>
            </div>
        </div>
    </div>
</div>
