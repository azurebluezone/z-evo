#!/usr/bin/env python3
r"""Find Bengali text in the FM (new design) code and replace it with English.

Scope is deliberately narrow - only code written for the new design - so the
classic design stays byte identical:

    resources/views/frontend/fm/**          views, sections, partials, pages
    app/Support/FrontendTheme.php           admin theme labels
    app/Support/ShopPromises.php            shared shop promise strip
    public/frontend/js/fm-app.js            fm runtime (cart, toast, search)
    public/frontend/js/fm-ai.js             fm AI overlays
    public/frontend/css/fm-*.css            fm page sheets
    /home/sahon/uds/hi/verify/*.mjs         browser harnesses

Usage
-----
    python3 tools/bangla_to_english.py --list    # show every Bengali run
    python3 tools/bangla_to_english.py --apply   # rewrite using PHRASE_MAP
    python3 tools/bangla_to_english.py --check   # exit 1 if Bengali remains

PHRASE_MAP keys are exact substrings (longest key wins), so a key may span
Latin words or Blade echoes when the sentence needs it.  The \u09xx escape
entries cover Bengali written as a JS escape (for example the taka sign).
"""

import argparse
import pathlib
import re
import sys

ROOT = pathlib.Path("/home/sahon/Desktop/arafat")
VERIFY = pathlib.Path("/home/sahon/uds/hi/verify")

SCOPE_DIRS = [
    ROOT / "resources/views/frontend/fm",
    VERIFY,
]
SCOPE_FILES = [
    ROOT / "app/Support/FrontendTheme.php",
    ROOT / "app/Support/ShopPromises.php",
    ROOT / "public/frontend/js/fm-app.js",
    ROOT / "public/frontend/js/fm-ai.js",
]
SCOPE_GLOBS = [
    (ROOT / "public/frontend/css", "fm-*.css"),
]

SUFFIXES = {".blade.php", ".php", ".js", ".mjs", ".css"}

# Bengali script block, plus the taka sign that lives inside it.
BANGLA_RE = re.compile(r"[\u0980-\u09FF]")
# Bengali written as a JS unicode escape, e.g. '\u09F3'.
ESCAPE_RE = re.compile(r"\\u09[89a-fA-F][0-9a-fA-F]")

# Exact substring -> English replacement.  Longest key is applied first,
# so full-sentence keys always win over the short fallback keys at the
# bottom of this table (this is what keeps substrings such as "category"
# inside "all categories" from corrupting a longer phrase).
PHRASE_MAP = {
    # ------------------------------------------------------------------
    # JS unicode escapes (taka sign)
    # ------------------------------------------------------------------
    "\\u09F3": "Tk ",

    # ------------------------------------------------------------------
    # app/Support/FrontendTheme.php  (admin theme picker)
    # ------------------------------------------------------------------
    "'ক্লাসিক (বর্তমান ডিজাইন)'": "'Classic (current design)'",
    "'আগের ডিজাইন, হুবহু আগের মতোই কাজ করছে। ঝুঁকি নেই।'": "'The previous design, exactly as it always worked. No risk.'",
    "'মডার্ন (নতুন ডিজাইন)'": "'Modern (new design)'",
    "'Bootstrap 5 ভিত্তিক নতুন ডিজাইন। রেসপন্সিভ, দ্রুত, আধুনিক।'": "'New design built on Bootstrap 5. Responsive, fast and modern.'",

    # ------------------------------------------------------------------
    # app/Support/ShopPromises.php  (shared promise strip)
    # ------------------------------------------------------------------
    "'ঢাকার ভিতরে ২৪ ঘণ্টায়, সারা দেশে ২–৩ দিনে'": "'Inside Dhaka in 24 hours, nationwide in 2-3 days'",
    "'ক্যাশ অন ডেলিভারি, কার্ড ও মোবাইল ব্যাংকিং'": "'Cash on delivery, cards and mobile banking'",
    "'৭ দিনের মধ্যে পণ্য ফেরত দেওয়ার সুযোগ'": "'Return any product within 7 days'",
    "'প্রতিদিন সকাল ৯টা থেকে রাত ৯টা পর্যন্ত'": "'Every day from 9 am to 9 pm'",
    "দ্রুত ডেলিভারি": "Fast delivery",
    "নিরাপদ পেমেন্ট": "Secure payment",
    "সহজ রিটার্ন": "Easy returns",
    "সাপোর্ট": "Support",

    # ------------------------------------------------------------------
    # public/frontend/js/fm-app.js
    # ------------------------------------------------------------------
    "'কিছু একটা সমস্যা হয়েছে'": "'Something went wrong'",
    "কার্টটি এখন খালি": "Your cart is empty",
    '” এর জন্য কিছু পাওয়া যায়নি': '” produced no results',

    # ------------------------------------------------------------------
    # layouts.blade.php  -  top bar
    # ------------------------------------------------------------------
    "অনলাইনে পণ্য কিনুন — দ্রুত ডেলিভারি, নিরাপদ পেমেন্ট, সহজ রিটার্ন": "Shop online - fast delivery, secure payment, easy returns",
    "ক্যাশ অন ডেলিভারি সুবিধা পাচ্ছেন": "Enjoy cash on delivery",
    "হেলপলাইন:": "Helpline:",
    "হেলপলাইন": "Helpline",
    "এখানে নম্বর দিন": "Add your number",
    "প্রতিদিন সকাল ৯টা – রাত ৯টা": "Open daily 9 am - 9 pm",
    "হোয়াটসঅ্যাপে লিখুন": "Chat on WhatsApp",
    "ডেলিভারি ঠিকানা:": "Delivery address:",
    "সারা বাংলাদেশ": "All over Bangladesh",

    # ------------------------------------------------------------------
    # layouts.blade.php  -  search bar + account dropdown
    # ------------------------------------------------------------------
    "আপনি কী খুঁজছেন? পণ্যের নাম বা কোড লিখুন": "What are you looking for? Type a product name or code",
    "সব ক্যাটাগরি দেখুন →": "View all categories →",
    "সব ক্যাটাগরি": "All categories",
    "স্বাগতম,": "Welcome,",
    "নতুন অ্যাকাউন্ট": "New account",
    "অতিথি ব্যবহারকারী": "Guest user",
    "অ্যাকাউন্ট": "Account",
    "আমার অর্ডার": "My orders",
    "অর্ডার ট্র্যাক": "Track order",
    "পাসওয়ার্ড ভুলে গেছেন": "Forgot password",
    "লগআউট": "Log out",
    "লগইন": "Log in",
    "উইশলিস্টে সংরক্ষিত হয়েছে": "Saved to wishlist",
    "উইশলিস্ট থেকে সরানো হয়েছে": "Removed from wishlist",
    "উইশলিস্টে যোগ করুন": "Add to wishlist",
    "উইশলিস্ট": "Wishlist",

    # ------------------------------------------------------------------
    # layouts.blade.php  -  main nav + footer
    # ------------------------------------------------------------------
    "স্পেশাল অফার": "Special offers",
    "হট ডিল দেখুন": "See hot deals",
    "হট ডিল": "Hot deals",
    "কেনাকাটার ক্যাটাগরি": "Product categories",
    "কেনাকাটা চালিয়ে যান": "Continue shopping",
    "আমাদের সম্পর্কে": "About us",
    "ক্রেতা সহায়তা": "Customer support",
    "প্রাইভেসি পলিসি": "Privacy policy",
    "রিটার্ন পলিসি": "Return policy",
    "পেমেন্ট মেথড:": "Payment methods:",
    "ক্যাশ অন ডেলিভারি": "Cash on delivery",
    "মোবাইল ব্যাংকিং": "Mobile banking",
    "নিরাপদ লেনদেন": "Secure transaction",
    "হোয়াটসঅ্যাপে অর্ডার করুন": "Order on WhatsApp",
    "হোয়াটসঅ্যাপ": "WhatsApp",
    "আমরা বাংলাদেশের বিশ্বস্ত অনলাইন শপিং প্রতিষ্ঠান। মানসম্পন্ন পণ্য, সাশ্রয়ী মূল্য আর দ্রুত ডেলিভারি।": "A trusted online shopping store of Bangladesh. Quality products, affordable prices and fast delivery.",
    "সর্বস্বত্ব সংরক্ষিত।": "All rights reserved.",
    "শর্তাবলি": "Terms & conditions",
    "কোম্পানি": "Company",
    "সব দেখুন": "View all",
    "সব পণ্য দেখুন": "See all products",
    "সব পণ্য ও গ্রোসারি": "All products & groceries",
    "সব পণ্য": "All products",
    "ক্যাটাগরি ঘুরে দেখুন": "Browse categories",
    "জনপ্রিয় ক্যাটাগরি": "Popular categories",
    "ক্যাটাগরি": "Categories",
    "হোম": "Home",
    "যোগাযোগ পাতা দেখুন": "See contact page",
    "যোগাযোগ": "Contact",
    "কার্ড": "Card",

    # ------------------------------------------------------------------
    # layouts.blade.php  -  cart drawer + CFG block
    # ------------------------------------------------------------------
    "আমার শপিং বাস্কেট": "My shopping basket",
    "কার্ট থেকে সরানো হয়েছে": "Removed from cart",
    "কার্ট থেকে সরান": "Remove from cart",
    "কার্টে যোগ হয়েছে": "Added to cart",
    "কার্টে যোগ করুন": "Add to cart",
    "ডেলিভারি চার্জ": "Delivery charge",
    "চেকআউটে জানা যাবে": "Calculated at checkout",
    "চেকআউটে যান →": "Proceed to checkout →",
    "সর্বমোট": "Total",
    "উপরে যান": "Back to top",
    "এই পণ্যের পর্যাপ্ত স্টক নেই": "Not enough stock for this product",

    # ------------------------------------------------------------------
    # pages/home.blade.php
    # ------------------------------------------------------------------
    "'নতুন কালেকশন'": "'New arrivals'",
    "'আপনার জন্য সাজানো'": "'Picked for you'",
    "'নতুন এসেছে আপনার পছন্দের মতো পণ্যগুলো।'": "'Fresh arrivals picked for you.'",
    "'বিশেষ কালেকশন'": "'Special collections'",
    "'সব কালেকশন'": "'All collections'",
    "'কালেকশন'": "'Collections'",
    "'একসাথে পছন্দের জিনিসগুলো দেখে নিন।'": "'Browse your favorite picks together.'",
    "বাংলাদেশের বিশ্বস্ত অনলাইন শপিং প্ল্যাটফর্ম। হাজারো পণ্য এক জায়গায়, দ্রুত ডেলিভারি আর সহজ পেমেন্ট।": "Trusted online shopping platform of Bangladesh. Thousands of products in one place, fast delivery and easy payment.",

    # ------------------------------------------------------------------
    # pages/product-details.blade.php  -  PHP side
    # ------------------------------------------------------------------
    "'ছাড় ' . $fmSavePct": "'Save ' . $fmSavePct",
    "'স্টকে আছে (' . $fmStockQty . ' টি বাকি)'": "'In stock (' . $fmStockQty . ' left)'",
    "'স্টকে আছে'": "'In stock'",
    "'স্টকনেই'": "'Out of stock'",
    "'আমি এই পণ্যটি সম্পর্কে জানতে চাই: '": "'I would like to know more about this product: '",
    "$product_categories[0]['name'] . ' সব দেখুন'": "'View all ' . $product_categories[0]['name']",
    "সম্পর্কিত পণ্য": "Related products",
    "একসাথে পছন্দ হতে পারে": "You may also like",

    # ------------------------------------------------------------------
    # pages/product-details.blade.php  -  page JS (CFG + runtime)
    # ------------------------------------------------------------------
    "pickAll: 'সব অপশন একসাথে বাছুন'": "pickAll: 'Select all options'",
    "unavailable: 'এই নির্বাচনে পণ্য পাওয়া যায়নি'": "unavailable: 'This selection is out of stock'",
    "offline: 'স্টক তথ্য আনা যায়নি'": "offline: 'Stock info could not be loaded'",
    "reviewRatting: 'তারকা নির্বাচন করুন'": "reviewRatting: 'Choose a star rating'",
    "reviewFallback: 'আবার চেষ্টা করুন'": "reviewFallback: 'Try again'",
    'reviewText: \'রিভিউ লিখুন\'': "reviewText: 'Write a review'",
    '"N টি রিভিউ"': '"N reviews"',
    "'স্টকনেই' : 'স্টকে আছে (' + qty + ' টি বাকি)'": "'Out of stock' : 'In stock (' + qty + ' left)'",
    "'পছন্দমতো বাছুন'": "'Select options'",
    " (নির্বাচিত)": " (selected)",

    # ------------------------------------------------------------------
    # pages/products.blade.php
    # ------------------------------------------------------------------
    "'ফিচার্ড পণ্য'": "'Featured products'",
    "'বেস্ট সেলিং পণ্য'": "'Best selling products'",
    "'ছাড়ের পণ্য'": "'Discounted products'",
    "'সুপারিশকৃত পণ্য'": "'Recommended products'",
    "ফিল্টার ও সর্টিং দিয়ে দ্রুত পছন্দের পণ্য খুঁজে নিন — ": "Find your favorite products with filters and sorting - ",
    "'এই ফিল্টারে কোনো পণ্য পাওয়া যায়নি। ফিল্টার একটু কমিয়ে দেখুন।'": "'No products match these filters. Try removing a filter.'",

    # ------------------------------------------------------------------
    # partials/cart-items.blade.php
    # ------------------------------------------------------------------
    "পরিমাণ কমান": "Decrease quantity",
    "পরিমাণ বাড়ান": "Increase quantity",
    "aria-label=\"পরিমাণ\"": "aria-label=\"Quantity\"",
    "title=\"কমান\"": "title=\"Decrease\"",
    "title=\"বাড়ান\"": "title=\"Increase\"",
    "কেনাকাটা শুরু করুন": "Start shopping",

    # ------------------------------------------------------------------
    # partials/pagination.blade.php
    # ------------------------------------------------------------------
    "পণ্য তালিকার পাতা": "Product list pages",
    "আগের পাতা": "Previous page",
    "পরের পাতা": "Next page",

    # ------------------------------------------------------------------
    # sections/categories.blade.php
    # ------------------------------------------------------------------
    "'আপনার পছন্দের যেকোনো ক্যাটাগরি বেছে নিন।'": "'Pick any category you like.'",
    "'আসল ব্র্যান্ডের অরিজিনাল পণ্য, সরাসরি উৎস থেকে।'": "'Original products from genuine brands, straight from the source.'",
    "'ব্র্যান্ড অনুযায়ী কিনুন'": "'Shop by brand'",
    "'বিশ্বস্ত ব্র্যান্ড'": "'Trusted brands'",
    "ব্র্যান্ড": "Brand",

    # ------------------------------------------------------------------
    # sections/detail-gallery.blade.php
    # ------------------------------------------------------------------
    "থাম্বনেইল ক্লিক করে দেখুন": "Click a thumbnail to view",
    "ছবি ": "Image ",

    # ------------------------------------------------------------------
    # sections/detail-summary.blade.php
    # ------------------------------------------------------------------
    "{{ $fmRatingCount }} টি রিভিউ ভিত্তিতে": "based on {{ $fmRatingCount }} reviews",
    "{{ $fmGroup['name'] }} নির্বাচন করুন:": "Select {{ $fmGroup['name'] }}:",
    "ছাড় ৳ ": "Save Tk ",
    "পণ্যটি স্টকে নেই": "product is out of stock right now",
    "এই মুহূর্তে পণ্যটি স্টকে নেই": "This product is out of stock right now",
    "ফেসবুকে শেয়ার করুন": "Share on Facebook",
    "পিনট্রেস্টে শেয়ার করুন": "Share on Pinterest",
    "লিংকডইনে শেয়ার করুন": "Share on LinkedIn",
    "এক্সে শেয়ার করুন": "Share on X",
    "শেয়ার:": "Share:",

    # ------------------------------------------------------------------
    # sections/detail-tabs.blade.php
    # ------------------------------------------------------------------
    "এই পণ্যের বিস্তারিত বিবরণ এখনো যোগ করা হয়নি। কোনো প্রশ্ন থাকলে হেলপলাইনে যোগাযোগ করুন।": "A detailed description has not been added yet. Contact the helpline if you have any questions.",
    "বিস্তারিত তথ্যের জন্য ডকুমেন্টটি খুলে দেখুন।": "Open the document for detailed information.",
    "এখনো কোনো রিভিউ আসেনি। প্রথম রিভিউটি আপনিই লিখুন!": "No reviews yet. Be the first to write one!",
    "ধন্যবাদ! আপনার রিভিউ জমা হয়েছে — অনুমোদনের পর এখানে দেখা যাবে।": "Thank you! Your review has been submitted - it will appear here after approval.",
    "রিভিউটি অনুমোদনের পর প্রকাশিত হবে।": "Your review will appear after approval.",
    "লগইন করে আপনার অভিজ্ঞতা অন্যদের সাথে শেয়ার করুন।": "Log in and share your experience with others.",
    "রিভিউ দিতে লগইন করুন": "Log in to write a review",
    "পণ্যটি কেমন লেগেছে লিখে জানান...": "Tell us how you liked this product...",
    "পণ্যের বিবরণ": "Description",
    "পণ্যের ডকুমেন্ট": "Product document",
    "ডকুমেন্টটি খুলে দেখুন": "open the document",
    "ডকুমেন্ট খুলুন": "Open document",
    "ডকুমেন্ট": "Document",
    "পণ্যের ভিডিও": "Product video",
    "ভিডিও": "Video",
    "সাপোর্ট ও ডেলিভারি": "Support & delivery",
    "রিভিউ লিখুন": "Write a review",
    "রিভিউ জমা দিন": "Submit review",
    "রিভিউ (": "Reviews (",
    "আপনার রেটিং:": "Your rating:",
    "পণ্যগুলো দেখায়": "products shown",
    "ভেরিফায়েড ক্রেতা": "Verified buyer",
    "'ক্রেতা'": "'Customer'",
    "'কে'": "'C'",
    "কোনো রিভিউ আসেনি": "No reviews yet",
    "রিভিউ": "review",
    "তারকা নির্বাচন করুন": "Choose a star rating",
    "তারকা ও বেশি": "stars & up",
    "তারকা": "stars",
    "তারা": "stars",
    "ও বেশি": " & up",

    # ------------------------------------------------------------------
    # sections/hero.blade.php
    # ------------------------------------------------------------------
    "১০০% অরিজিনাল পণ্যের নিশ্চয়তা": "100% original product guarantee",
    "পছন্দের পণ্য": "Your favorite products",
    "সেরা দামে": "at the best price",
    "আপনার দরজায়": "to your doorstep",
    "এখনই কিনুন": "Buy now",
    "আজকের অফার": "Today's offer",

    # ------------------------------------------------------------------
    # sections/newsletter.blade.php
    # ------------------------------------------------------------------
    "নতুন অফার সবার আগে জানুন": "Know about new offers first",
    "চেকআউটে ছাড়": "Discounts at checkout",
    "ইমেইল দিন, নতুন নতুন ছাড় ও নতুন পণ্যের খবর সরাসরি পাবেন।": "Share your email and get new deals and product news first.",
    "আপনার ইমেইল ঠিকানা": "Your email address",
    "ইমেইল ঠিকানা": "Email address",
    "সাবস্ক্রাইব": "Subscribe",

    # ------------------------------------------------------------------
    # sections/partials
    # ------------------------------------------------------------------
    "এই মুহূর্তে কোনো পণ্য নেই।": "No products right now.",

    # ------------------------------------------------------------------
    # sections/product-feeds.blade.php
    # ------------------------------------------------------------------
    "'বাছাই করা পণ্য'": "'Handpicked products'",
    "'আমাদের বাছাই করা সেরা পণ্যগুলো'": "'Our best handpicked products'",
    "'সবচেয়ে বেশি বিক্রি'": "'Top sellers'",
    "'যে পণ্যগুলো ক্রেতারা সবচেয়ে বেশি কিনছেন'": "'Products customers buy the most'",
    "'ডিসকাউন্ট পণ্য'": "'Discounted products'",
    "'নির্দিষ্ট সময়ের বিশেষ ছাড়ে পাওয়া যাচ্ছে'": "'Available at a special limited-time discount'",
    "'ফিচার্ড'": "'Featured'",
    "'বেস্ট সেলিং'": "'Best selling'",
    "'ছাড়'": "'Discount'",

    # ------------------------------------------------------------------
    # sections/promo-banner.blade.php
    # ------------------------------------------------------------------
    "সর্বোচ্চ <strong>{{ $fmPromoOff }}% ছাড়</strong> পর্যন্ত পাচ্ছেন সীমিত কিছু পণ্যে।": "Get up to <strong>{{ $fmPromoOff }}% off</strong> on selected items.",
    "সময় শেষ হওয়ার আগেই অর্ডার করে ফেলুন।": "Order before the offer ends.",
    "বাছাই করা পণ্যে বাড়তি ছাড় পাচ্ছেন। সময় ফুরিয়ে আসার আগে অর্ডার করুন।": "Extra savings on selected items. Order before time runs out.",
    "সীমিত সময়ের অফার": "Limited time offer",
    "সব অফার": "All offers",
    "'অফার'": "'Offer'",

    # ------------------------------------------------------------------
    # sections/shop-header.blade.php
    # ------------------------------------------------------------------
    "'ন্যূনতম ৳'": "'Min Tk '",
    "'সর্বোচ্চ ৳'": "'Max Tk '",
    "শুধু স্টকে আছে": "In stock only",
    "খামার থেকে সরাসরি দরজায়": "From the farm straight to your door",
    "ফিল্টার ও সর্টিং দিয়ে দ্রুত পছন্দের পণ্য খুঁজে নিন। প্রতিটি ফিল্টার ঠিক সেই পণ্যগুলো দেখায় যা আপনার পছন্দ মেলে।": "Find your favorite products fast with filters and sorting. Each filter shows exactly the products you prefer.",
    "সক্রিয় ফিল্টার:": "Active filters:",
    "এই ফিল্টার সরান": "Remove this filter",
    "সব মুছুন": "Clear all",

    # ------------------------------------------------------------------
    # sections/shop-sidebar.blade.php
    # ------------------------------------------------------------------
    "পুরনো দামের চেয়ে কম দামে পছন্দের পণ্যগুলো এখনই নিয়ে যান।": "Get your favorite products now at lower prices than before.",
    "সর্বোচ্চ ছাড় এক ক্লিকে": "Biggest discounts in one click",
    "ছাড়ের পণ্য দেখুন": "See discounted products",
    "ছাড়ের পণ্যসমূহ": "Discounted products",
    "ফিল্টার প্রয়োগ করুন": "Apply filters",
    "সর্বোচ্চ দাম": "Max price",
    "সর্বোচ্চ": "Maximum",
    "ন্যূনতম": "Minimum",
    "সব রিসেট": "Reset all",
    "সব বিভাগ": "All categories",
    "বিভাগ": "Categories",
    "রেটিং": "Rating",
    "ফিল্টার": "Filters",
    "৳১০০ – ৩০০": "Tk 100 - 300",
    "৳৩০০ – ৫০০": "Tk 300 - 500",
    "৳১০০ পর্যন্ত": "Up to Tk 100",
    "৳৫০০ বেশি": "Over Tk 500",

    # ------------------------------------------------------------------
    # sections/shop-toolbar.blade.php
    # ------------------------------------------------------------------
    "'ডিফল্ট (সর্বশেষ)'": "'Default (newest first)'",
    "'দাম: কম থেকে বেশি'": "'Price: low to high'",
    "'দাম: বেশি থেকে কম'": "'Price: high to low'",
    "'নতুন আগে'": "'Newest first'",
    "'পুরনো আগে'": "'Oldest first'",
    "তালিকা দেখার ধরন": "View type",
    "গ্রিড ভিউ": "Grid view",
    "তালিকা ভিউ": "List view",
    " টি পণ্য পাওয়া গেছে": " products found",
    " টি পণ্য": " products",
    "মোট ": "Total ",
    "সর্ট করুন:": "Sort by:",
    "প্রতি পাতায়:": "Per page:",

    # ------------------------------------------------------------------
    # sections/testimonials.blade.php
    # ------------------------------------------------------------------
    "'ক্রেতাদের মতামত'": "'Customer reviews'",
    "'যারা কিনেছেন তারা যা বলছেন'": "'What our buyers are saying'",
    "'আমাদের ক্রেতাদের লেখা রিভিউ থেকে।'": "'From reviews written by our customers.'",
    "'ক্রেতা'": "'Customer'",
    "'ক'": "'C'",

    # ------------------------------------------------------------------
    # pages/product-details.blade.php  -  tab labels shared with detail-tabs
    # ------------------------------------------------------------------
    "পছন্দমতো বাছুন": "Select options",

    # ------------------------------------------------------------------
    # stock wording shared by blade + JS + harness
    # ------------------------------------------------------------------
    " টি বাকি": " left",
    "স্টকে আছে": "In stock",
    "স্টকনেই": "Out of stock",
    "স্টক নেই": "Out of stock",
    " টি রিভিউ": " reviews",

    # ------------------------------------------------------------------
    # currency + digits (Bengali block) - keep last in reading order,
    # the script sorts by length so short keys naturally run last
    # ------------------------------------------------------------------
    "৳ ": "Tk ",
    "৳": "Tk ",
    "০": "0",
    "১": "1",
    "২": "2",
    "৩": "3",
    "৪": "4",
    "৫": "5",
    "৬": "6",
    "৭": "7",
    "৮": "8",
    "৯": "9",
    "।": ".",
}


def scope_files():
    files = []
    for d in SCOPE_DIRS:
        if d.is_dir():
            files.extend(p for p in sorted(d.rglob("*")) if p.suffixes and SUFFIXES.intersection(p.suffixes) or p.name.endswith(".blade.php"))
    files.extend(f for f in SCOPE_FILES if f.exists())
    for d, pattern in SCOPE_GLOBS:
        files.extend(sorted(d.glob(pattern)))
    # stable, de-duplicated
    return sorted(set(f for f in files if f.is_file()))


def find_bangla(text):
    """Yield (start, end) spans of Bengali characters."""
    return [(m.start(), m.end()) for m in BANGLA_RE.finditer(text)]


def list_mode():
    total_lines = 0
    for path in scope_files():
        text = path.read_text(encoding="utf-8")
        hits = []
        for i, line in enumerate(text.splitlines(), 1):
            if BANGLA_RE.search(line):
                hits.append((i, line.strip()))
        if hits:
            print(f"\n### {path}")
            for i, line in hits:
                print(f"  {i}: {line}")
            total_lines += len(hits)
    print(f"\n{total_lines} Bengali line(s) in {len(scope_files())} scoped file(s).")


def apply_mode():
    changed = 0
    for path in scope_files():
        text = original = path.read_text(encoding="utf-8")
        for key in sorted(PHRASE_MAP, key=len, reverse=True):
            if key in text:
                text = text.replace(key, PHRASE_MAP[key])
        if text != original:
            path.write_text(text, encoding="utf-8")
            changed += 1
            print(f"rewritten: {path}")
    print(f"{changed} file(s) rewritten.")


def check_mode():
    bad = 0
    for path in scope_files():
        text = path.read_text(encoding="utf-8")
        for i, line in enumerate(text.splitlines(), 1):
            if BANGLA_RE.search(line) or ESCAPE_RE.search(line):
                print(f"REMAINS  {path}:{i}: {line.strip()}")
                bad += 1
    if bad:
        print(f"\n{bad} line(s) still contain Bengali.")
        sys.exit(1)
    print("OK - no Bengali in scope.")


def main():
    ap = argparse.ArgumentParser(description=__doc__)
    mode = ap.add_mutually_exclusive_group(required=True)
    mode.add_argument("--list", action="store_true", help="show every Bengali line")
    mode.add_argument("--apply", action="store_true", help="rewrite using PHRASE_MAP")
    mode.add_argument("--check", action="store_true", help="fail if Bengali remains")
    args = ap.parse_args()
    if args.list:
        list_mode()
    elif args.apply:
        apply_mode()
    else:
        check_mode()


if __name__ == "__main__":
    main()
