<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeneralSetting;

class UpdateLogoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-logo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update logo paths in general settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $setting = GeneralSetting::first();
        
        if (!$setting) {
            $this->error('No general settings found!');
            return 1;
        }
        
        $this->info('Current white_logo: ' . $setting->white_logo);
        $this->info('Current dark_logo: ' . $setting->dark_logo);
        
        $newLogoPath = 'public/uploads/settings/1725554682-logo-01.webp';
        
        $setting->white_logo = $newLogoPath;
        $setting->dark_logo = $newLogoPath;
        $setting->save();
        
        $this->info('Updated white_logo: ' . $setting->white_logo);
        $this->info('Updated dark_logo: ' . $setting->dark_logo);
        $this->info('Logo paths updated successfully!');
        
        return 0;
    }
}
