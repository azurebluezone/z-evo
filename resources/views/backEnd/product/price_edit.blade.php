@extends('backEnd.layouts.app')
@section('title','Product Price Manage')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('products.create')}}" class="btn btn-danger rounded-pill"><i class="fe-shopping-cart"></i> Add Product</a>
                </div>
                <h4 class="page-title">Product Price Manage</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <form action="{{route('products.price_update')}}" method="POST">
                @csrf
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table nowrap w-100">
                    <thead>
                        <tr>
                            <th style="width:5%">SL</th>
                                    </div></th>
                            <th style="width:50%">Name</th>
                            <th style="width:15%">Old Price</th>
                            <th style="width:15%">New Price</th>
                            <th style="width:15%">Stock</th></th>
                        </tr>
                    </thead>               
                
                    <tbody>
                        @foreach($products as $key=>$value)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <input type="hidden" value="{{$value->id}}" name="ids[]">
                            <td>{{$value->name}}</td>
                            <td><input value="{{$value->old_price}}" name="old_price[]"></td>
                            <td><input value="{{$value->new_price}}" name="new_price[]"></td>
                            <td><input value="{{$value->stock}}" name="stock[]"></td>
                        </tr>
                        @endforeach
                     </tbody>
                     <tfoot>
                         <tr>
                             <td colspan="4"></td>
                             <td>
                                 <x-admin.button variant="success">Update Price</x-admin.button>
                             </td>
                         </tr>
                     </tfoot>
                    </table>
                </div>
            </div> <!-- end card body-->
            </form>
        </div> <!-- end card -->
    </div><!-- end col-->
   </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery) return;
    var $ = window.jQuery;

    function toast(icon, title) {
      if (!window.Swal || !window.Swal.fire) return;
      try {
        window.Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          icon: icon,
          title: title
        });
      } catch (e) {
        // ignore
      }
    }

    $(".checkall").on('change', function () {
      $(".checkbox").prop('checked', $(this).is(":checked"));
    });

    $(document).on('click', '.hotdeal_update', function (e) {
      e.preventDefault();
      var url = $(this).attr('href');
      var product = $('input.checkbox:checked').map(function () {
        return $(this).val();
      });
      var product_ids = product.get();
      if (product_ids.length == 0) {
        toast('error', 'Please Select A Product First !');
        return;
      }
      $.ajax({
        type: 'GET',
        url: url,
        data: { product_ids },
        success: function (res) {
          if (res.status == 'success') {
            toast('success', res.message);
            window.location.reload();
          } else {
            toast('error', 'Failed something wrong');
          }
        }
      });
    });

    $(document).on('click', '.update_status', function (e) {
      e.preventDefault();
      var url = $(this).attr('href');
      var product = $('input.checkbox:checked').map(function () {
        return $(this).val();
      });
      var product_ids = product.get();
      if (product_ids.length == 0) {
        toast('error', 'Please Select A Product First !');
        return;
      }
      $.ajax({
        type: 'GET',
        url: url,
        data: { product_ids },
        success: function (res) {
          if (res.status == 'success') {
            toast('success', res.message);
            window.location.reload();
          } else {
            toast('error', 'Failed something wrong');
          }
        }
      });
    });
  });
</script>
@endsection
