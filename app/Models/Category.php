<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeoMeta;

class Category extends Model
{
    use HasSeoMeta;
    
        /**
     * Allow-list instead of `$guarded = []`.
     * The controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Columns: every writable one except id/created_at/updated_at.
     * Supersedes the narrower `$fillable` that used to sit alongside the
     * open `$guarded = []` (with both present, guard wins and nothing was
     * actually protected).
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'image',
        'meta_title',
        'meta_decription',
        'status',
        'is_featured',
        'featured_sort_order',
        'home_page',
        'front_view',
    ];
    
    public function getRouteKeyName() {
        return 'slug';
    }
    public function categories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function category()
    {
        return $this->hasOne(Category::class, 'id','parent_id');
    }

    public function subcategories() {
        return $this->hasMany(Subcategory::class, 'category_id')->where('status', 1);
    }
    
    public function menusubcategories() {
        return $this->hasMany(Subcategory::class, 'category_id')->select('id','slug','subcategoryName','category_id')->where('status', 1);
    }

    public function childrenCategories()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('categories');
    }
    
    public function menuchildcategories()
    {
        return $this->hasMany(Childcategory::class, 'subcategory_id')->select('id','slug','subcategory_id','childcategoryName')->where('status',1);
    }
    
    
    public function homeproducts()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
    public function menuproducts()
    {
        return $this->hasMany(Product::class, 'category_id')->limit(8);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id')->select('id', 'name', 'slug', 'category_id', 'new_price', 'old_price')->orderBy('id','DESC');
    }
    
    


}
