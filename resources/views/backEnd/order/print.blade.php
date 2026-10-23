<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Print</title>
    {{--
        The two stylesheets this file used to link were
        asset('frontEnd/css/bootstrap.min.css') and asset('frontEnd/css/all.min.css').
        Neither file exists in public/frontend/css, so both 404'd and the
        invoice rendered as unstyled markup. The admin's own Bootstrap 5
        bundle is what the rest of the admin uses, and it carries the .table,
        .row, .col-* and .container classes this file relies on.
    --}}
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/fonts/iconify-icons.css') }}" />
</head>
<body>
    <style>
        .customer-invoice {
            page-break-before: always;
        }
        body{
            background:#F1F2F5
        }
    .customer-invoice {
        margin: 25px 0;
    }
    .invoice_btn{
        margin-bottom: 15px;
    }
    p{
        margin:0;
    }
    td{
        font-size: 16px;
    }
   @page { 
    margin:0px;
    }
   @media print {
    .invoice-innter{
        margin-left: -120px !important;
    }
    .invoice_btn{
        margin-bottom: 0 !important;
    }
    td{
        font-size: 18px;
    }
    p{
        margin:0;
    }
    header,footer,.no-print,.left-side-menu,.navbar-custom {
      display: none !important;
    }
  }
</style>
<div class="container">
    <div class="row">
        <div class="col-sm-12 mt-3 text-center">
            <x-admin.button variant="success" size="sm" ripple="true" class="no-print waves-light" onclick="printFunction()"><i class="icon-base ri ri-printer-line"></i></x-admin.button>
        </div>
    </div>
</div>
@foreach($orders as $order)
<section class="customer-invoice ">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 mt-3">
                <div class="invoice-innter" style="width:760px;margin: 0 auto;background: #fff;overflow: hidden;padding: 30px;padding-top: 0;">
                    <table style="width:100%">
                        <tr>
                            <td style="width: 40%; float: left; padding-top: 15px;">
                                <img src="{{asset($generalsetting->white_logo)}}" width="190px" style="margin-top:25px !important" alt="">
                                <p style="font-size: 14px; color: #222; margin: 20px 0;"><strong>Payment Method:</strong> <span style="text-transform: uppercase;">{{$order->payment?$order->payment->payment_method:''}}</span></p>
                                <div class="invoice_form">
                                    <p style="font-size:16px;line-height:1.8;color:#222"><strong>Invoice From:</strong></p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{$generalsetting->name}}</p>
                                    {{--
                                        $contact was never passed to this view, so
                                        the three lines below raised
                                        "Attempt to read property phone on null"
                                        and the whole print popup failed. The
                                        store's own contact details live in
                                        general_settings, so they are read from
                                        there and printed only when set.
                                    --}}
                                    @if($generalsetting->support_phone)
                                        <p style="font-size:16px;line-height:1.8;color:#222">Phone: {{$generalsetting->support_phone}}</p>
                                    @endif
                                    @if($generalsetting->whatsapp_number)
                                        <p style="font-size:16px;line-height:1.8;color:#222">WhatsApp: {{$generalsetting->whatsapp_number}}</p>
                                    @endif
                                </div>
                            </td>
                            <td  style="width:60%;float: left;">
                                <div class="invoice-bar" style=" background: #4DBC60; transform: skew(38deg); width: 100%; margin-left: 65px; padding: 20px 60px; ">
                                    <p style="font-size: 30px; color: #fff; transform: skew(-38deg); text-transform: uppercase; text-align: right; font-weight: bold;">Invoice</p>
                                </div>
                                <div class="invoice-bar" style="background: #fff; transform: skew(36deg); width: 72%; margin-left: 182px; padding: 12px 32px; margin-top: 6px;">
                                    <p style="font-size: 15px; color: #222;font-weight:bold; transform: skew(-36deg); text-align: right; padding-right: 18px">Invoice ID : <strong>#{{$order->invoice_id}}</strong></p>
                                    <p style="font-size: 15px; color: #222;font-weight:bold; transform: skew(-36deg); text-align: right; padding-right: 32px">Invoice Date: <strong>{{$order->created_at->format('d-m-y')}}</strong></span></p>
                                </div>
                                <div class="invoice_to" style="padding-top: 20px;">
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;"><strong>Invoice To:</strong></p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->name:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->phone:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->address:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->area:''}}</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <table class="table" style="margin-top: 30px;margin-bottom: 0;">
                        <thead style="background: #4DBC60; color: #fff;">
                            <tr>
                                <th>SL</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderdetails as $key=>$value)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$value->product_name}}</td>
                                <td>৳{{$value->sale_price}}</td>
                                <td>{{$value->qty}}</td>
                                <td>৳{{$value->sale_price*$value->qty}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="invoice-bottom">
                        
                        <table class="table" style="width: 300px; float: right;    margin-bottom: 30px;">
                            <tbody style="background:#f1f9f8">
                                <tr>
                                    <td><strong>SubTotal</strong></td>
                                    <td><strong>৳{{$order->orderdetails->sum('sale_price')}}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Shipping(+)</strong></td>
                                    <td><strong>৳{{$order->shipping_charge}}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Discount(-)</strong></td>
                                    <td><strong>৳{{$order->discount}}</strong></td>
                                </tr>
                                <tr style="background:#4DBC60;color:#fff">
                                    <td><strong>Final Total</strong></td>
                                    <td><strong>৳{{$order->amount}}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="terms-condition" style="overflow: hidden; width: 100%; text-align: center; padding: 20px 0; border-top: 1px solid #ddd;">
                            <h5 style="font-style: italic;"><a href="{{route('page',['slug'=>'terms-condition'])}}">Terms & Conditions</a></h5>
                            <p style="text-align: center; font-style: italic; font-size: 15px; margin-top: 10px;">* This is a computer generated invoice, does not require any signature.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endforeach
<script>
    function printFunction() {
        window.print();
    }
</script>
</body>
</html>
