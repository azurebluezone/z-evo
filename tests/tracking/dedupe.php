<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\VisitorMaster;
use App\Services\Analytics\EventRecorder;
use App\Support\EventId;
use Illuminate\Support\Str;

$order = Order::orderByDesc('id')->first();
if (!$order) { echo "NO_ORDER\n"; exit(1); }

$id1 = EventId::purchase($order);
$id2 = EventId::purchase(Order::find($order->id));
echo "order={$order->id} invoice={$order->invoice_id} id={$id1} deterministic=" . ($id1 === $id2 ? 'yes' : 'NO') . " len=" . strlen($id1) . "\n";

$vid = (string) Str::uuid();
VisitorMaster::create(['visitor_id'=>$vid,'ip'=>'127.0.0.1','status'=>1,'last_seen_at'=>now(),'device_type'=>'desktop']);

$rec = app(EventRecorder::class);
$r1 = $rec->recordBatch($vid, [[
    'name'=>'Purchase','event_id'=>$id1,'url'=>'http://127.0.0.1:8000/checkout',
    'page_path'=>'/checkout','ts'=>now()->toIso8601String(),
]], EventRecorder::METHOD_SERVER);
$r2 = $rec->recordBatch($vid, [[
    'name'=>'Purchase','event_id'=>$id1,'url'=>'http://127.0.0.1:8000/customer/order-success/'.$order->id,
    'page_path'=>'/customer/order-success/'.$order->id,'ts'=>now()->toIso8601String(),
]], EventRecorder::METHOD_BROWSER);

$row = DB::table('event_logs')->where('event_id',$id1)->first();
echo "server_batch=".json_encode($r1)."\n";
echo "browser_batch=".json_encode($r2)."\n";
echo "rows=".DB::table('event_logs')->where('event_id',$id1)->count()
   ." status=".($row->dedup_status ?? 'none')
   ." method=".($row->tracking_method ?? 'none')
   ." matched=".($row->dedup_matched_with ?? 'none')
   ." page_path=".($row->page_path ?? 'null')."\n";

DB::table('event_logs')->where('event_id',$id1)->delete();
DB::table('visitors_master')->where('visitor_id',$vid)->delete();
echo "cleanup rows=".DB::table('event_logs')->where('event_id',$id1)->count()
   ." visitor=".DB::table('visitors_master')->where('visitor_id',$vid)->count()."\n";
