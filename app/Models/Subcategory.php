<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeoMeta;

class Subcategory extends Model
{
    use HasFactory;
    use HasSeoMeta;
    
        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `Subcategory` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'subcategoryName',
        'slug',
        'category_id',
        'image',
        'meta_title',
        'meta_decription',
        'status',
    ];
    public function childcategories() {
        return $this->hasMany(Childcategory::class, 'subcategory_id')->where('status', 1);
    }
    public function category() {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }
    
    public function menuchildcategories()
    {
        return $this->hasMany(Childcategory::class, 'subcategory_id')->select('id','slug','subcategory_id','childcategoryName')->where('status',1);
    }
    
    
}
