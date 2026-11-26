<?php

namespace Database\Seeders;

use App\Models\CreatePage;
use Illuminate\Database\Seeder;

/**
 * The CMS pages the footer and the register form link to.
 *
 * /terms-and-conditions resolves through FrontendController::page(), which
 * 404s until a create_pages row exists. A fresh install therefore had a dead
 * footer link, so the page is seeded here. Re-running updates the same slug.
 */
class StaticPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'name' => 'Terms and Conditions',
                'slug' => 'terms-and-conditions',
                'title' => 'শর্তাবলি ও নিয়মাবলী',
                'description' => implode("\n\n", [
                    'এই ওয়েবসাইট থেকে অর্ডার করার আগে নিচের শর্তাবলি অনুগ্রহ করে পড়ে নিন।',
                    '১. অর্ডার ও কনফার্মেশন: অর্ডার দেওয়ার পর আমাদের প্রতিনিধি ফোনে যোগাযোগ করে অর্ডার নিশ্চিত করেন।',
                    '২. মূল্য ও ডেলিভারি চার্জ: পণ্যের মূল্য ও ডেলিভারি চার্জ অর্ডারের সময় দেখানো হয়; কোনো লুকানো চার্জ নেই।',
                    '৩. ডেলিভারি সময়: ঢাকার মধ্যে সাধারণত ২৪ ঘণ্টা এবং ঢাকার বাইরে ২ থেকে ৩ কর্মদিবস।',
                    '৪. রিটার্ন ও রিপ্লেসমেন্ট: পণ্যে ত্রুটি থাকলে ডেলিভারির ৭ দিনের মধ্যে রিটার্ন বা রিপ্লেসমেন্টের আবেদন করা যাবে।',
                    '৫. পেমেন্ট: ক্যাশ অন ডেলিভারি এবং সমর্থিত অনলাইন পেমেন্ট পদ্ধতি ব্যবহার করা যাবে।',
                    '৬. ব্যক্তিগত তথ্য: আপনার নাম, ফোন নম্বর ও ঠিকানা শুধু অর্ডার প্রক্রিয়াকরণ ও ডেলিভারির জন্য ব্যবহার করা হয়।',
                ]),
                'status' => 1,
            ],
        ];

        foreach ($pages as $page) {
            CreatePage::updateOrCreate(['slug' => $page['slug']], $page);
        }

        $this->command->info('Static pages seeded: ' . count($pages));
    }
}
