<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\GeneralSetting;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SocialMedia;
use App\Models\Contact;
use App\Models\CreatePage;
use App\Models\OrderStatus;
use App\Models\EcomPixel;
use App\Models\GoogleTagManager;
use App\Models\Order;
use App\Models\PaymentGateway;
use Config;
use Session;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Front-end template switching: hand view lookups to the themed finder
        // so App\Support\FrontendTheme can decide which template set answers.
        // Only the two constructor arguments Laravel itself passes are given -
        // FileViewFinder treats a third one as "replace the file extensions",
        // which would stop every blade file from resolving.
        // The original finder may already carry package namespaces (a few
        // register them while their provider boots), so they are copied over
        // before the themed finder replaces it.
        $this->app->extend('view.finder', function ($finder, $app) {
            $themed = new \App\Support\ThemeViewFinder($app['files'], $finder->getPaths());

            foreach ($finder->getHints() as $namespace => $paths) {
                $themed->addNamespace($namespace, $paths);
            }

            return $themed;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Use Bootstrap for pagination
        Paginator::useBootstrap();

        // The template set in resources/views/frontend/fm ships its own Blade
        // components, so it keeps its own namespace instead of competing with
        // the global resources/views/components folder.
        $this->callAfterResolving('blade.compiler', function ($blade) {
            $blade->anonymousComponentNamespace('frontend.fm.components', 'fm');

            // The admin's own components. Registered next to the fm namespace so
            // backEnd/components/button.blade.php answers to <x-admin.button>
            // instead of forcing every call site to spell out the view path.
            //
            // component(), not componentNamespace(): the latter expects a PHP
            // class namespace (App\View\Components) and silently finds nothing
            // for a view folder, which shows up as "Unable to locate a class or
            // view for component [admin.button]" on the first render.
            $blade->component('backEnd.components.button', 'admin.button');
        });

        // Don't load data from database during console commands or when database isn't ready
        if ($this->app->runningInConsole()) {
            return;
        }

        // Check if database connection is available
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            return; // Database not ready, skip loading data
        }

       $shurjopay = PaymentGateway::where(['status' => 1, 'type' => 'shurjopay'])->first();
        if ($shurjopay) {
            
            Config::set(['shurjopay.apiCredentials.username' => $shurjopay->username]);
            Config::set(['shurjopay.apiCredentials.password' => $shurjopay->password]);
            Config::set(['shurjopay.apiCredentials.prefix' => $shurjopay->prefix]);
            Config::set(['shurjopay.apiCredentials.return_url' => $shurjopay->success_url]);
            Config::set(['shurjopay.apiCredentials.cancel_url' => $shurjopay->return_url]);
            Config::set(['shurjopay.apiCredentials.base_url' => $shurjopay->base_url]);
        }
        $generalsetting = GeneralSetting::where('status',1)->limit(1)->first();
        view()->share('generalsetting',$generalsetting); 

        $sidecategories = Category::where('parent_id','=','0')->where('status',1)->select('id','name','slug','status','image')->get();
        view()->share('sidecategories',$sidecategories); 
        
        $menucategories = Category::where('status',1)
            ->select('id','name','slug','status','image')
            ->with(['subcategories' => function ($q) {
                $q->select('id', 'slug', 'subcategoryName', 'category_id')->where('status', 1)
                    ->with(['childcategories' => function ($q2) {
                        $q2->select('id', 'slug', 'subcategory_id', 'childcategoryName')->where('status', 1);
                    }]);
            }])
            ->get();
        view()->share('menucategories',$menucategories); 

        $contact = Contact::where('status',1)->first();
        view()->share('contact',$contact); 

        $socialicons = SocialMedia::where('status',1)->get();
        view()->share('socialicons',$socialicons);

        $pages = CreatePage::where('status',1)->limit(3)->get();
        view()->share('pages',$pages);

        $pagesright = CreatePage::where('status',1)->skip(3)->limit(10)->get();
        view()->share('pagesright',$pagesright);

        $cmnmenu = CreatePage::where('status',1)->get();
        view()->share('cmnmenu',$cmnmenu);

        $brands = Brand::where('status',1)->get();
        view()->share('brands',$brands);
        
        $neworder = Order::where('order_status','1')->count();
        view()->share('neworder',$neworder); 
        
        $pendingorder = Order::where('order_status','1')->latest()->limit(9)->get();
        view()->share('pendingorder',$pendingorder); 
        
        $orderstatus = OrderStatus::get();
        view()->share('orderstatus',$orderstatus);
        
        $pixels = EcomPixel::where('status',1)->get();
        view()->share('pixels',$pixels);
        
        $gtm_code = GoogleTagManager::where('status',1)->get();
        view()->share('gtm_code',$gtm_code);
    }
}
