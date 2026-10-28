<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Models\SmsGateway;
use Toastr;
use File;
use Str;
use Image;
use DB;

class ApiIntegrationController extends Controller
{
    
    public function pay_manage ()
    {
        $bkash = PaymentGateway::firstOrCreate(
            ['type' => 'bkash'],
            [
                'username' => '',
                'app_key' => '',
                'app_secret' => '',
                'base_url' => '',
                'password' => '',
                'status' => 0,
            ]
        );

        $shurjopay = PaymentGateway::firstOrCreate(
            ['type' => 'shurjopay'],
            [
                'username' => '',
                'prefix' => '',
                'success_url' => '',
                'return_url' => '',
                'base_url' => '',
                'password' => '',
                'status' => 0,
            ]
        );
        return view('backEnd.apiintegration.pay_manage',compact('bkash','shurjopay'));
    }
    
    public function pay_update(Request $request)
    {
      
        $update_data = PaymentGateway::find($request->id);
        if (!$update_data) {
            Toastr::error('Error', 'Payment gateway settings not found');
            return redirect()->back();
        }
        $input = $request->all();
        $input['status'] = $request->status?1:0;
        $update_data->update($input);
        
        Toastr::success('Success','Data update successfully');
        return redirect()->back();
    }
    
    public function sms_manage ()
    {
        /*
         * `SmsGateway::first()` is null on a fresh install, and the template
         * reads $sms->id on its very first line - so this page answered 500
         * until a row happened to exist. sms_gateways is empty, so it always
         * did. A fresh, status-ON model renders the same form empty instead,
         * and the first submit creates the row.
         */
        $sms = SmsGateway::first() ?? new SmsGateway(['status' => 1]);

        return view('backEnd.apiintegration.sms_manage', compact('sms'));
    }
    
    public function sms_update(Request $request)
    {
        /*
         * Three separate defects met here:
         *   1. find($request->id) is null on a fresh install and ->update() then
         *      threw, so the FIRST gateway could never be saved - which is why
         *      sms_gateways stayed empty and every OTP / order SMS was skipped.
         *   2. $request->all() was handed straight to update(), carrying
         *      `_token` (and anything else posted) into the SQL.
         *   3. `password_g` was written to a column that did not exist until
         *      migration 2026_10_18_000009.
         * Columns are now listed explicitly and the row is created on first save.
         */
        $request->validate([
            'url'      => 'required',
            'api_key'  => 'required',
            'serderid' => 'required',
        ]);

        $sms = $request->filled('id') ? SmsGateway::find($request->id) : null;

        if (!$sms) {
            $sms = new SmsGateway();
        }

        $sms->url         = $request->input('url');
        $sms->api_key     = $request->input('api_key');
        $sms->serderid    = $request->input('serderid');
        $sms->status      = $request->input('status') ? 1 : 0;
        $sms->order       = $request->input('order') ? 1 : 0;
        $sms->forget_pass = $request->input('forget_pass') ? 1 : 0;
        $sms->password_g  = $request->input('password_g') ? 1 : 0;
        $sms->save();

        Toastr::success('Success','Data update successfully');
        return redirect()->back();
    }
}
