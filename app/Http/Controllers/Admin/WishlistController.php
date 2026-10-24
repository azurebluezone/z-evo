<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Admin wishlist management (admin/wishlist).
 *
 * Rows are written by the add-to-wishlist/remove-from-wishlist route
 * closures in routes/web.php for logged-in customers (guard: `customer`).
 * Guests stay session-only and are intentionally invisible here.
 */
class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $query = Wishlist::query()
            ->with(['product.image', 'customer'])
            ->orderBy('id', 'DESC');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('product', function ($pq) use ($like) {
                    $pq->where('name', 'like', $like);
                })->orWhereHas('customer', function ($cq) use ($like) {
                    $cq->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            });
        }

        $show_data = $query->paginate(20)->withQueryString();

        return view('backEnd.wishlist.index', compact('show_data', 'search'));
    }

    public function destroy($id)
    {
        $wishlist = Wishlist::find($id);
        if ($wishlist) {
            $wishlist->delete();
            Toastr::success('Success', 'Wishlist entry deleted');
        }

        return redirect()->back();
    }
}
