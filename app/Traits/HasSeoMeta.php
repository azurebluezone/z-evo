<?php

namespace App\Traits;

use App\Models\SeoMeta;

trait HasSeoMeta
{
    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'model');
    }
    
    public function getSeoTitleAttribute()
    {
        return $this->seoMeta?->meta_title ?? $this->getDefaultSeoTitle();
    }
    
    public function getSeoDescriptionAttribute()
    {
        return $this->seoMeta?->meta_description ?? '';
    }
    
    public function getSeoImageAttribute()
    {
        return $this->seoMeta?->meta_image ?? $this->getDefaultSeoImage();
    }
    
    protected function getDefaultSeoTitle()
    {
        if (isset($this->name)) {
            return $this->name;
        }
        if (isset($this->title)) {
            return $this->title;
        }
        return config('app.name', 'Z-Evo-Commerce');
    }
    
    protected function getDefaultSeoImage()
    {
        // Return default image path if needed
        return null;
    }
    
    public function updateSeoMeta($seoData)
    {
        if (!$seoData) {
            return;
        }
        
        $filteredData = array_filter($seoData, function($value) {
            return !is_null($value) && $value !== '';
        });
        
        if (empty($filteredData)) {
            return;
        }
        
        if ($this->seoMeta) {
            $this->seoMeta->update($filteredData);
        } else {
            $this->seoMeta()->create($filteredData);
        }
    }
}