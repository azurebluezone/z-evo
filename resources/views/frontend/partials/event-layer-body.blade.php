@php
    $gtmContainerId = null;
    if (isset($gtm_code) && count($gtm_code) > 0) {
        foreach ($gtm_code as $gtmRow) {
            if (!empty($gtmRow->code) && preg_match('/GTM-[A-Z0-9]+/i', $gtmRow->code, $m)) {
                $gtmContainerId = strtoupper($m[0]);
                break;
            }
        }
    }
@endphp

@if($gtmContainerId)
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmContainerId }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif
