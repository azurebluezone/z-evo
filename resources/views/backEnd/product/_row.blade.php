{{--
    backEnd/product/_row - one row of the products grid.

    Same job as backEnd/order/_row: the blade page and the DataTables JSON
    endpoint both need this markup, and two copies of a row is how an edit
    button ends up on one and not the other.

        $value  App\Models\Product with image and category already loaded

    The controller checks the cell count against the grid's column count, so a
    change here that adds or removes a cell fails the request loudly instead of
    shifting every column one place to the left.
--}}

                <tr>
                    <td>
                        <div class="form-check m-0">
                            <input type="checkbox" class="checkbox form-check-input rounded-0" value="{{$value->id}}">
                        </div>
                    </td>
                    <td class="fw-medium">{{$value->name ?? 'N/A'}}</td>
                    <td class="d-none d-md-table-cell">{{ $value->category ? $value->category->name : 'Uncategorized' }}</td>
                    <td class="d-none d-md-table-cell">
                        @php
                            $productImg = $value->image && $value->image->image ? $value->image->image : null;
                            $productImgPath = $productImg ? preg_replace('~^public/~', '', $productImg) : null;
                            $productImgPath = $productImgPath ? ltrim($productImgPath, '/') : null;
                            $fallbackImg = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                            $fallbackImg = preg_replace('~^public/~', '', $fallbackImg);
                            $fallbackImg = ltrim($fallbackImg, '/');
                        @endphp
                        <img
                            src="{{ $productImgPath && file_exists(public_path($productImgPath)) ? asset($productImgPath) : asset($fallbackImg) }}"
                            class="backend-image"
                            style="max-width: 50px; max-height: 50px; object-fit: cover;"

                            alt="{{ $value->name }}"
                            onerror="this.src='{{ asset('assets/images/empty.png') }}'">
                    </td>
                    <td class="d-none d-sm-table-cell fw-semibold text-primary">
                        {{-- Click the price to edit it in place. The value is
                             rendered twice: once as text for reading, once as
                             the input the click swaps in. admin-product-inline.js
                             owns the swap and the save. --}}
                        <span class="js-inline-text" data-inline-price
                              data-product-id="{{ $value->id }}">৳{{$value->new_price ?? 0}}</span>
                        {{-- step=1 and min=0, not 0.01: the column is an INT. Accepting
                             777.25 here would store 777 and repaint 777 on the
                             next draw, so the input matches what can be kept. --}}
                        <input type="number" step="1" min="0"
                               class="js-inline-input form-control form-control-sm d-none fw-semibold"
                               data-inline-price-input
                               data-inline-url="{{ route('products.quick_update') }}"
                               data-product-id="{{ $value->id }}"
                               value="{{ $value->new_price ?? 0 }}"
                               aria-label="Edit price">
                    </td>

                    <td class="d-none d-sm-table-cell">
                        {{-- Same pattern for stock; the colour badge re-appears
                             once the save returns the new number. --}}
                        @if(($value->stock ?? 0) <= 5)
                            <span class="js-inline-text badge bg-label-danger rounded-0" data-inline-stock
                                  data-product-id="{{ $value->id }}">{{$value->stock ?? 0}}</span>
                        @else
                            <span class="js-inline-text badge bg-label-success rounded-0" data-inline-stock
                                  data-product-id="{{ $value->id }}">{{$value->stock ?? 0}}</span>
                        @endif
                        <input type="number" step="1" min="0"
                               class="js-inline-input form-control form-control-sm d-none"
                               data-inline-stock-input
                               data-inline-url="{{ route('products.quick_update') }}"
                               data-product-id="{{ $value->id }}"
                               value="{{ $value->stock ?? 0 }}"
                               aria-label="Edit stock">
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="form-check form-switch custom-switch-sharp m-0" title="Hot Deal">
                                <input
                                    type="checkbox"
                                    class="form-check-input rounded-0 js-product-flag"
                                    data-url-on="{{ route('products.update_deals') }}"
                                    data-url-off="{{ route('products.update_deals') }}"
                                    data-status-on="1"
                                    data-status-off="0"
                                    data-product-id="{{ $value->id }}"
                                    {{ ($value->topsale ?? 0) == 1 ? 'checked' : '' }}>
                            </div>

                            <div class="form-check form-switch custom-switch-sharp m-0" title="Top Feature">
                                <input
                                    type="checkbox"
                                    class="form-check-input rounded-0 js-product-flag"
                                    data-url-on="{{ route('products.update_feature') }}"
                                    data-url-off="{{ route('products.update_feature') }}"
                                    data-status-on="1"
                                    data-status-off="0"
                                    data-product-id="{{ $value->id }}"
                                    {{ ($value->feature_product ?? 0) == 1 ? 'checked' : '' }}>
                            </div>

                            @if(($value->is_recommended ?? 0) == 1)
                                <form method="post" action="{{ route('admin.recommendations.update') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="id" value="{{$value->id}}">
                                    <input type="hidden" name="is_recommended" value="0">
                                    <div class="form-check form-switch custom-switch-sharp m-0" title="Recommended">
                                        <input type="checkbox" class="form-check-input rounded-0 js-auto-submit" checked>
                                    </div>
                                </form>
                            @else
                                <form method="post" action="{{ route('admin.recommendations.update') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="id" value="{{$value->id}}">
                                    <input type="hidden" name="is_recommended" value="1">
                                    <div class="form-check form-switch custom-switch-sharp m-0" title="Not Recommended">
                                        <input type="checkbox" class="form-check-input rounded-0 js-auto-submit">
                                    </div>
                                </form>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if(($value->status ?? 0) == 1)
                            <form method="post" action="{{route('products.inactive')}}" class="d-inline">
                                @csrf
                                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                <span class="badge bg-label-success rounded-0 cursor-pointer" onclick="this.parentNode.submit()">Active</span>
                            </form>
                        @else
                            <form method="post" action="{{route('products.active')}}" class="d-inline">
                                @csrf
                                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                <span class="badge bg-label-secondary rounded-0 cursor-pointer" onclick="this.parentNode.submit()">Inactive</span>
                            </form>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <a class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" href="{{route('products.edit',$value->id)}}" title="Edit">
                                <i class="icon-base ri ri-edit-box-line"></i>
                            </a>

                            <form method="post" action="{{route('products.destroy')}}" class="d-inline">
                                @csrf
                                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                <button type="submit" class="btn btn-icon btn-text-danger btn-sm btn-square waves-effect delete-confirm" title="Delete">
                                    <i class="icon-base ri ri-delete-bin-7-line"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
