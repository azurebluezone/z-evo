<!-- Footer -->
<footer class="content-footer footer bg-footer-theme">
    <div class="container-fluid">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="mb-2 mb-md-0">
{!! $generalsetting->copyright ?? ('© ' . date('Y') . ' <a href="' . route('home') . '">' . e($generalsetting->name ?? 'ZEvo') . '</a> All Rights Reserved.') !!}
            </div>
        </div>
    </div>
</footer>
<!-- / Footer -->
