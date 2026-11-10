@extends('backEnd.layouts.app')
@section('title', 'Social Media')

@section('content')
<!-- Tracking Status Cards (Dynamic) -->
<div class="row g-6 mb-6">
  <!-- Facebook -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100" style="border-left: 4px solid #1877f2;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3" style="background-color: #1877f2;">
            <i class="icon-base ri ri-facebook-fill text-white"></i>
          </div>
          <div>
            <h6 class="mb-0">Facebook</h6>
            <small class="text-muted">{{ $fbPixelSet ? 'Pixel Active' : 'Not Configured' }}</small>
          </div>
          @if($fbPixelSet)
            <span class="badge bg-success ms-auto">Active</span>
          @else
            <span class="badge bg-label-secondary ms-auto">Inactive</span>
          @endif
        </div>
        <div class="row text-center">
          <div class="col-4 border-end">
            <h5 class="mb-0">{{ number_format($weekEvents) }}</h5>
            <small class="text-muted">Events</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0">{{ number_format($weekVisitors) }}</h5>
            <small class="text-muted">Visitors</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0">{{ $settings->facebook_server_side_tracking ? 'ON' : 'OFF' }}</h5>
            <small class="text-muted">CAPI</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- TikTok -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100" style="border-left: 4px solid #ff0050;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3" style="background-color: #ff0050;">
            <i class="icon-base ri ri-music-2-fill text-white"></i>
          </div>
          <div>
            <h6 class="mb-0">TikTok</h6>
            <small class="text-muted">{{ $ttPixelSet ? 'Pixel Active' : 'Not Configured' }}</small>
          </div>
          @if($ttPixelSet)
            <span class="badge bg-success ms-auto">Active</span>
          @else
            <span class="badge bg-label-secondary ms-auto">Inactive</span>
          @endif
        </div>
        <div class="row text-center">
          <div class="col-4 border-end">
            <h5 class="mb-0">{{ number_format($weekEvents) }}</h5>
            <small class="text-muted">Events</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0">{{ number_format($todayVisitors) }}</h5>
            <small class="text-muted">Today</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0">{{ $settings->tiktok_server_side_tracking ? 'ON' : 'OFF' }}</h5>
            <small class="text-muted">Server</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Google (GTM) -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100" style="border-left: 4px solid #4285f4;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3" style="background-color: #4285f4;">
            <i class="icon-base ri ri-google-fill text-white"></i>
          </div>
          <div>
            <h6 class="mb-0">Google (GTM)</h6>
            <small class="text-muted">{{ $gtmSet ? 'Container Active' : 'Not Configured' }}</small>
          </div>
          @if($gtmSet)
            <span class="badge bg-success ms-auto">Active</span>
          @else
            <span class="badge bg-label-secondary ms-auto">Inactive</span>
          @endif
        </div>
        <div class="row text-center">
          <div class="col-4 border-end">
            <h5 class="mb-0">{{ number_format($totalEvents) }}</h5>
            <small class="text-muted">Total</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0">{{ number_format($totalVisitors) }}</h5>
            <small class="text-muted">Visitors</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0">{{ $settings->gtm_server_side_tracking ? 'ON' : 'OFF' }}</h5>
            <small class="text-muted">Server</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Overall Stats -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100" style="border-left: 4px solid #20c997;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3" style="background-color: #20c997;">
            <i class="icon-base ri ri-bar-chart-line text-white"></i>
          </div>
          <div>
            <h6 class="mb-0">Tracking Overview</h6>
            <small class="text-muted">Last 7 days</small>
          </div>
        </div>
        <div class="row text-center">
          <div class="col-4 border-end">
            <h5 class="mb-0">{{ number_format($todayEvents) }}</h5>
            <small class="text-muted">Today</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0">{{ number_format($weekEvents) }}</h5>
            <small class="text-muted">Week</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0">{{ number_format($totalEvents) }}</h5>
            <small class="text-muted">Total</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Event Breakdown & Location -->
<div class="row g-6 mb-6">
  <!-- Event Breakdown -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Event Breakdown (All Time)</h5>
        <span class="badge bg-label-primary">{{ number_format($totalEvents) }} total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Event</th>
                <th class="text-end">Count</th>
              </tr>
            </thead>
            <tbody>
              @forelse($eventBreakdown as $ev)
              <tr>
                <td>
                  @if($ev->event_name === 'PageView')
                    <i class="icon-base ri ri-eye-line me-2 text-primary"></i>
                  @elseif($ev->event_name === 'AddToCart')
                    <i class="icon-base ri ri-shopping-cart-line me-2 text-success"></i>
                  @elseif($ev->event_name === 'InitiateCheckout')
                    <i class="icon-base ri ri-briefcase-line me-2 text-warning"></i>
                  @else
                    <i class="icon-base ri ri-flashlight-line me-2 text-info"></i>
                  @endif
                  {{ $ev->event_name }}
                </td>
                <td class="text-end"><span class="badge bg-label-primary">{{ number_format($ev->count) }}</span></td>
              </tr>
              @empty
              <tr>
                <td colspan="2" class="text-center text-muted py-4">No events recorded yet</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Countries -->
  <div class="col-lg-3">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Top Countries</h5>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          @forelse($topCountries as $country)
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="icon-base ri ri-map-pin-line me-2 text-primary"></i>{{ $country->country ?: 'Unknown' }}</span>
            <span class="badge bg-label-primary rounded-pill">{{ number_format($country->count) }}</span>
          </div>
          @empty
          <div class="list-group-item text-center text-muted">No country data</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <!-- Top Cities -->
  <div class="col-lg-3">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Top Cities</h5>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          @forelse($topCities as $city)
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="icon-base ri ri-building-line me-2 text-success"></i>{{ $city->city ?: 'Unknown' }}</span>
            <span class="badge bg-label-success rounded-pill">{{ number_format($city->count) }}</span>
          </div>
          @empty
          <div class="list-group-item text-center text-muted">No city data</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Events Timeline -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Events</h5>
        <a href="{{ route('admin.social-events.dashboard') }}" class="btn btn-sm btn-outline-primary">View Dashboard</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Event</th>
                <th>Visitor</th>
                <th>IP</th>
                <th>Country</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentEvents as $event)
              <tr>
                <td>
                  @if($event->event_name === 'PageView')
                    <span class="badge bg-label-primary"><i class="icon-base ri ri-eye-line me-1"></i>PageView</span>
                  @elseif($event->event_name === 'AddToCart')
                    <span class="badge bg-label-success"><i class="icon-base ri ri-shopping-cart-line me-1"></i>AddToCart</span>
                  @elseif($event->event_name === 'InitiateCheckout')
                    <span class="badge bg-label-warning"><i class="icon-base ri ri-briefcase-line me-1"></i>InitiateCheckout</span>
                  @else
                    <span class="badge bg-label-info"><i class="icon-base ri ri-flashlight-line me-1"></i>{{ $event->event_name }}</span>
                  @endif
                </td>
                <td><small class="text-truncate" style="max-width:100px;display:block;" title="{{ $event->visitor_id }}">{{ Str::limit($event->visitor_id, 12) }}</small></td>
                <td>{{ $event->ip ?? 'N/A' }}</td>
                <td>{{ $event->country ?? 'N/A' }}</td>
                <td><small>{{ $event->created_at ? $event->created_at->diffForHumans() : 'N/A' }}</small></td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">No events recorded yet</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tracking Settings -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
          <h5 class="mb-0"><i class="icon-base ri ri-settings-3-line me-2"></i>Tracking Settings</h5>
          <small class="text-muted">Configure Facebook Pixel, TikTok, Google Tag Manager & Contact Settings</small>
        </div>
        <div class="d-flex gap-2">
          <span class="badge bg-label-success"><i class="icon-base ri ri-check-line me-1"></i>FB: {{ !empty($settings->facebook_pixel_id) ? 'Configured' : 'Not Set' }}</span>
          <span class="badge bg-label-{{ !empty($settings->tiktok_pixel_id) ? 'success' : 'secondary' }}"><i class="icon-base ri ri-{{ !empty($settings->tiktok_pixel_id) ? 'check-line' : 'close-line' }} me-1"></i>TT: {{ !empty($settings->tiktok_pixel_id) ? 'Configured' : 'Not Set' }}</span>
          <span class="badge bg-label-{{ !empty($settings->gtm_server_container_id) ? 'success' : 'secondary' }}"><i class="icon-base ri ri-{{ !empty($settings->gtm_server_container_id) ? 'check-line' : 'close-line' }} me-1"></i>GTM: {{ !empty($settings->gtm_server_container_id) ? 'Configured' : 'Not Set' }}</span>
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.social-events.update') }}">
          @csrf
          @method('POST')

          <!-- Nav tabs -->
          <ul class="nav nav-tabs nav-fill flex-nowrap overflow-auto mb-4" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active d-flex align-items-center gap-1" id="facebook-tab" data-bs-toggle="tab" data-bs-target="#facebook-settings" type="button" role="tab" aria-controls="facebook-settings" aria-selected="true">
                <i class="icon-base ri ri-facebook-fill" style="color: #1877f2;"></i>Facebook
                @if(!empty($settings->facebook_pixel_id))
                  <span class="badge bg-success rounded-pill ms-1" style="width: 8px; height: 8px; padding: 0;"></span>
                @endif
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link d-flex align-items-center gap-1" id="tiktok-tab" data-bs-toggle="tab" data-bs-target="#tiktok-settings" type="button" role="tab" aria-controls="tiktok-settings" aria-selected="false">
                <i class="icon-base ri ri-music-2-fill" style="color: #ff0050;"></i>TikTok
                @if(!empty($settings->tiktok_pixel_id))
                  <span class="badge bg-success rounded-pill ms-1" style="width: 8px; height: 8px; padding: 0;"></span>
                @endif
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link d-flex align-items-center gap-1" id="google-tab" data-bs-toggle="tab" data-bs-target="#google-settings" type="button" role="tab" aria-controls="google-settings" aria-selected="false">
                <i class="icon-base ri ri-google-fill" style="color: #4285f4;"></i>Google (GTM)
                @if(!empty($settings->gtm_server_container_id))
                  <span class="badge bg-success rounded-pill ms-1" style="width: 8px; height: 8px; padding: 0;"></span>
                @endif
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link d-flex align-items-center gap-1" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact-settings" type="button" role="tab" aria-controls="contact-settings" aria-selected="false">
                <i class="icon-base ri ri-contacts-line"></i>Contact
              </button>
            </li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content">
            {{-- Facebook Settings --}}
            <div class="tab-pane fade show active" id="facebook-settings" role="tabpanel" aria-labelledby="facebook-tab">
              <div class="row g-4">
                <div class="col-md-6">
                  <x-admin.input name="facebook_pixel_id" label="Facebook Pixel ID"
                                 :value="$settings->facebook_pixel_id ?? ''"
                                 autocomplete="off" placeholder="e.g. 3602672243223193" />
                </div>
                <div class="col-md-6">
                  <x-admin.input name="facebook_conversion_api_token" label="Conversion API Token"
                                 :value="$settings->facebook_conversion_api_token ?? ''" placeholder="e.g. EAAB..." />
                </div>
                <div class="col-md-4">
                  <x-admin.select name="facebook_conversion_api_version" label="API Version"
                                  :options="[
                                      'v18.0' => 'v18.0 (Latest)',
                                      'v17.0' => 'v17.0',
                                      'v16.0' => 'v16.0',
                                      'v15.0' => 'v15.0',
                                  ]"
                                  :selected="$settings->facebook_conversion_api_version ?? 'v18.0'" />
                </div>
                <div class="col-md-8">
                  <x-admin.textarea name="facebook_custom_events" label="Custom Events (JSON)"
                                    rows="2"
                                    placeholder='[{"name":"CustomEvent","data":{"key":"value"}}]'
                                    :value="$settings->facebook_custom_events ?? ''" />
                </div>
                <div class="col-12">
                  <div class="d-flex gap-4 flex-wrap bg-light p-3 rounded">
                    <x-admin.switch name="facebook_server_side_tracking" label="Server-Side Tracking (Conversions API)"
                                    :checked="$settings->facebook_server_side_tracking ?? false" />
                    <x-admin.switch name="facebook_enhanced_ecommerce" label="Enhanced Ecommerce"
                                    :checked="$settings->facebook_enhanced_ecommerce ?? false" />
                  </div>
                </div>
              </div>
            </div>

            {{-- TikTok Settings --}}
            <div class="tab-pane fade" id="tiktok-settings" role="tabpanel" aria-labelledby="tiktok-tab">
              <div class="row g-4">
                <div class="col-md-6">
                  <x-admin.input name="tiktok_pixel_id" label="TikTok Pixel ID"
                                 :value="$settings->tiktok_pixel_id ?? ''" placeholder="e.g. C4BSC3JCGK4S7A74QGHG" />
                </div>
                <div class="col-md-6">
                  <x-admin.input name="tiktok_access_token" label="Access Token"
                                 :value="$settings->tiktok_access_token ?? ''" placeholder="e.g. eaa9b..." />
                </div>
                <div class="col-12">
                  <div class="d-flex gap-4 flex-wrap bg-light p-3 rounded">
                    <x-admin.switch name="tiktok_server_side_tracking" label="Server-Side Tracking (Events API)"
                                    :checked="$settings->tiktok_server_side_tracking ?? false" />
                    <x-admin.switch name="tiktok_browser_tracking" label="Browser-Side Tracking (Pixel)"
                                    :checked="$settings->tiktok_browser_tracking ?? false" />
                    <x-admin.switch name="tiktok_test_mode" label="Test Mode"
                                    :checked="$settings->tiktok_test_mode ?? false" />
                  </div>
                  <div class="mt-3">
                    <x-admin.input name="tiktok_test_event_code" id="ttTestEventCode" label="Test event code"
                                   :value="$settings->tiktok_test_event_code ?? ''" placeholder="e.g. TEST12345" />
                  </div>
                </div>
              </div>
            </div>

            {{-- Google Tag Manager Settings --}}
            <div class="tab-pane fade" id="google-settings" role="tabpanel" aria-labelledby="google-tab">
              <div class="row g-4">
                <div class="col-md-6">
                  <x-admin.input name="gtm_server_container_id" label="GTM Server Container ID"
                                 :value="$settings->gtm_server_container_id ?? ''" placeholder="e.g. GTM-XXXXXXX" />
                </div>
                <div class="col-md-6">
                  <x-admin.input name="gtm_server_url" label="GTM Server URL"
                                 :value="$settings->gtm_server_url ?? ''" placeholder="e.g. https://gtm.example.com" />
                </div>
                <div class="col-12">
                  <div class="bg-light p-3 rounded">
                    <x-admin.switch name="gtm_server_side_tracking" label="Server-Side Tracking (GTM Server Container)"
                                    :checked="$settings->gtm_server_side_tracking ?? false" />
                  </div>
                </div>
              </div>
            </div>

            {{-- Contact Settings --}}
            <div class="tab-pane fade" id="contact-settings" role="tabpanel" aria-labelledby="contact-tab">
              <div class="row g-4">
                <div class="col-md-4">
                  <x-admin.input name="whatsapp_number" label="WhatsApp Number"
                                 :value="$settings->whatsapp_number ?? ''" placeholder="e.g. +8801700000000" />
                </div>
                <div class="col-md-4">
                  <x-admin.input name="messenger_link" label="Messenger Link"
                                 :value="$settings->messenger_link ?? ''" placeholder="e.g. https://m.me/username" />
                </div>
                <div class="col-md-4">
                  <x-admin.input name="support_phone" label="Support Phone"
                                 :value="$settings->support_phone ?? ''" placeholder="e.g. +8801700000000" />
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
            <x-admin.button variant="primary" class="px-4" type="submit">
              <i class="icon-base ri ri-save-line me-1"></i>Save Settings
            </x-admin.button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
