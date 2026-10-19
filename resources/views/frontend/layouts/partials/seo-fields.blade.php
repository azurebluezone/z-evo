<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">SEO Meta Tags</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="meta_title" class="form-label">Meta Title</label>
                    <input type="text" 
                           name="seo_meta[meta_title]" 
                           id="meta_title" 
                           class="form-control" 
                           value="{{ old('seo_meta.meta_title', $model->seoMeta->meta_title ?? '') }}"
                           maxlength="60"
                           placeholder="e.g. Premium Wireless Bluetooth Headphones">
                    <div class="form-text"></div>
                </div>
            </div>
            
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description</label>
                    <textarea name="seo_meta[meta_description]" 
                              id="meta_description" 
                              class="form-control" 
                              rows="3" 
                              maxlength="160"
                              placeholder="e.g. High-quality Bluetooth headphones with noise cancellation and premium sound quality.">{{ old('seo_meta.meta_description', $model->seoMeta->meta_description ?? '') }}</textarea>
                    <div class="form-text"></div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                    <input type="text" 
                           name="seo_meta[meta_keywords]" 
                           id="meta_keywords" 
                           class="form-control" 
                           value="{{ old('seo_meta.meta_keywords', $model->seoMeta->meta_keywords ?? '') }}"
                           placeholder="e.g. headphones, bluetooth, wireless, audio">
                    <div class="form-text">Comma-separated keywords for SEO</div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="robots" class="form-label">Robots</label>
                    <select name="seo_meta[robots]" id="robots" class="form-control">
                        <option value="index,follow" {{ (old('seo_meta.robots', $model->seoMeta->robots ?? 'index,follow') == 'index,follow') ? 'selected' : '' }}>Index, Follow</option>
                        <option value="noindex,follow" {{ (old('seo_meta.robots', $model->seoMeta->robots ?? '') == 'noindex,follow') ? 'selected' : '' }}>No Index, Follow</option>
                        <option value="index,nofollow" {{ (old('seo_meta.robots', $model->seoMeta->robots ?? '') == 'index,nofollow') ? 'selected' : '' }}>Index, No Follow</option>
                        <option value="noindex,nofollow" {{ (old('seo_meta.robots', $model->seoMeta->robots ?? '') == 'noindex,nofollow') ? 'selected' : '' }}>No Index, No Follow</option>
                    </select>
                    <div class="form-text">Search engine crawling instructions</div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="meta_image" class="form-label">Meta Image</label>
                    <input type="file" 
                           name="seo_meta[meta_image]" 
                           id="meta_image" 
                           class="form-control"
                           accept="image/*">
                    <div class="form-text">Image for social sharing (recommended: 1200x630 pixels)</div>
                    
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
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="canonical_url" class="form-label">Canonical URL</label>
                    <input type="url" 
                           name="seo_meta[canonical_url]" 
                           id="canonical_url" 
                           class="form-control" 
                           value="{{ old('seo_meta.canonical_url', $model->seoMeta->canonical_url ?? '') }}"
                           placeholder="https://example.com/page">
                    <div class="form-text">Preferred URL for this content</div>
                </div>
            </div>
        </div>
        
        <hr>
        <h6>Open Graph (Facebook) Tags</h6>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="og_title" class="form-label">OG Title</label>
                    <input type="text" 
                           name="seo_meta[og_title]" 
                           id="og_title" 
                           class="form-control" 
                           value="{{ old('seo_meta.og_title', $model->seoMeta->og_title ?? '') }}"
                           placeholder="Title for social sharing">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="og_image" class="form-label">OG Image</label>
                    <input type="text" 
                           name="seo_meta[og_image]" 
                           id="og_image" 
                           class="form-control" 
                           value="{{ old('seo_meta.og_image', $model->seoMeta->og_image ?? '') }}"
                           placeholder="URL to social image">
                </div>
            </div>
            
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="og_description" class="form-label">OG Description</label>
                    <textarea name="seo_meta[og_description]" 
                              id="og_description" 
                              class="form-control" 
                              rows="2"
                              placeholder="Description for social sharing">{{ old('seo_meta.og_description', $model->seoMeta->og_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
        
        <hr>
        <h6>Twitter Card Tags</h6>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="twitter_title" class="form-label">Twitter Title</label>
                    <input type="text" 
                           name="seo_meta[twitter_title]" 
                           id="twitter_title" 
                           class="form-control" 
                           value="{{ old('seo_meta.twitter_title', $model->seoMeta->twitter_title ?? '') }}"
                           placeholder="Title for Twitter cards">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="twitter_image" class="form-label">Twitter Image</label>
                    <input type="text" 
                           name="seo_meta[twitter_image]" 
                           id="twitter_image" 
                           class="form-control" 
                           value="{{ old('seo_meta.twitter_image', $model->seoMeta->twitter_image ?? '') }}"
                           placeholder="URL to Twitter image">
                </div>
            </div>
            
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="twitter_description" class="form-label">Twitter Description</label>
                    <textarea name="seo_meta[twitter_description]" 
                              id="twitter_description" 
                              class="form-control" 
                              rows="2"
                              placeholder="Description for Twitter cards">{{ old('seo_meta.twitter_description', $model->seoMeta->twitter_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>