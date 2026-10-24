<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\GeneralSetting;

class FacebookTrackingService
{
    protected $pixelId;
    protected $accessToken;
    protected $apiVersion;
    protected $enabled;

    public function __construct()
    {
        $settings = GeneralSetting::first();
        $this->pixelId = $settings->facebook_pixel_id ?? null;
        $this->accessToken = $settings->facebook_conversion_api_token ?? null;
        $this->apiVersion = $settings->facebook_conversion_api_version ?? 'v17.0';
        $this->enabled = $settings->facebook_server_side_tracking ?? false;
    }

    /**
     * Generate a unique event ID for deduplication
     */
    protected function generateEventId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Send server-side event to Facebook Conversion API
     *
     * `$eventId` must be the SAME id the browser pixel used for this event.
     * Meta deduplicates on event_id, so sharing it is what stops one conversion
     * being counted twice on their side — and it is what lets the admin
     * dashboard's Server vs Browser panel report one number instead of two.
     */
    public function sendEvent($eventName, $eventData = [], $userData = [], ?string $eventId = null)
    {
        if (!$this->enabled || !$this->pixelId || !$this->accessToken) {
            Log::info('Facebook Server-Side Tracking disabled or not configured');
            return false;
        }

        $eventId = $this->normaliseEventId($eventId) ?? $this->generateEventId();

        try {
            $payload = [
                'data' => [
                    [
                        'event_name' => $eventName,
                        'event_time' => time(),
                        'event_id' => $eventId,
                        'action_source' => 'website',
                        'event_source_url' => request()->headers->get('referer') ?: request()->url(),
                        'user_data' => $this->prepareUserData($userData),
                        'custom_data' => $eventData
                    ]
                ],
                'access_token' => $this->accessToken
            ];

            // Bounded: an unreachable Graph API must not hold a checkout request
            // open. 8s total, 4s to connect.
            $response = Http::connectTimeout(4)
                ->timeout(8)
                ->post("https://graph.facebook.com/{$this->apiVersion}/{$this->pixelId}/events", $payload);

            if ($response->successful()) {
                Log::info('Facebook event sent successfully', [
                    'event' => $eventName,
                    'event_id' => $eventId,
                    'response' => $response->json()
                ]);

                $this->recordLocally($eventName, $eventId, $eventData);

                return true;
            } else {
                Log::error('Facebook event failed', [
                    'event' => $eventName,
                    'event_id' => $eventId,
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Facebook tracking error', [
                'event' => $eventName,
                'event_id' => $eventId ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mirror the server-side event into event_logs with tracking_method=server.
     *
     * EventRecorder applies the same dedup rules as the browser path, so a
     * matching browser row is marked "primary" instead of creating a duplicate.
     */
    private function recordLocally(string $eventName, string $eventId, array $eventData): void
    {
        try {
            $visitorId = request()->cookie('visitor_id') ?: request()->input('visitor_id');

            if (!is_string($visitorId) || $visitorId === '') {
                return;
            }

            app(\App\Services\Analytics\EventRecorder::class)->recordBatch(
                $visitorId,
                [[
                    'name' => $eventName,
                    'event_id' => $eventId,
                    'url' => request()->fullUrl(),
                    'page_path' => '/' . ltrim(request()->path(), '/'),
                    'session_id' => request()->cookie('session_id'),
                    'ts' => time(),
                    'event_data' => $eventData,
                    'channel' => 'facebook',
                ]],
                \App\Services\Analytics\EventRecorder::METHOD_SERVER
            );
        } catch (\Throwable $e) {
            // Analytics bookkeeping must never break the actual CAPI call.
            Log::warning('Facebook local event record failed', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reject anything that is not a safe hex/uuid-ish token before it is stored.
     */
    private function normaliseEventId(?string $eventId): ?string
    {
        if (!is_string($eventId)) {
            return null;
        }

        $eventId = trim($eventId);

        if ($eventId === '' || mb_strlen($eventId) > 64) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9_\-]+$/', $eventId) === 1 ? $eventId : null;
    }

    /**
     * Send Purchase event
     */
    public function sendPurchaseEvent($orderData, $userData = [], ?string $eventId = null)
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $orderData['amount'] ?? 0,
            'content_ids' => $orderData['product_ids'] ?? [],
            'content_type' => 'product',
            'order_id' => $orderData['order_id'] ?? null,
            'num_items' => $orderData['num_items'] ?? 1
        ];

        return $this->sendEvent('Purchase', $eventData, $userData, $eventId);
    }

    /**
     * Send AddToCart event
     */
    public function sendAddToCartEvent($productData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $productData['price'] ?? 0,
            'content_ids' => [$productData['product_id'] ?? ''],
            'content_type' => 'product',
            'content_name' => $productData['name'] ?? '',
            'quantity' => $productData['quantity'] ?? 1
        ];

        return $this->sendEvent('AddToCart', $eventData, $userData);
    }

    /**
     * Send ViewContent event
     */
    public function sendViewContentEvent($productData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $productData['price'] ?? 0,
            'content_ids' => [$productData['product_id'] ?? ''],
            'content_type' => 'product',
            'content_name' => $productData['name'] ?? '',
            'content_category' => $productData['category'] ?? ''
        ];

        return $this->sendEvent('ViewContent', $eventData, $userData);
    }

    /**
     * Send InitiateCheckout event
     */
    public function sendInitiateCheckoutEvent($cartData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $cartData['total'] ?? 0,
            'content_ids' => $cartData['product_ids'] ?? [],
            'content_type' => 'product',
            'num_items' => $cartData['num_items'] ?? 0
        ];

        return $this->sendEvent('InitiateCheckout', $eventData, $userData);
    }

    /**
     * Send AddToWishlist event
     */
    public function sendAddToWishlistEvent($productData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $productData['price'] ?? 0,
            'content_ids' => [$productData['product_id'] ?? ''],
            'content_type' => 'product',
            'content_name' => $productData['name'] ?? '',
            'quantity' => $productData['quantity'] ?? 1
        ];

        return $this->sendEvent('AddToWishlist', $eventData, $userData);
    }

    /**
     * Send AddPaymentInfo event
     */
    public function sendAddPaymentInfoEvent($paymentData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $paymentData['value'] ?? 0,
            'content_ids' => $paymentData['content_ids'] ?? [],
            'content_type' => 'product',
        ];

        return $this->sendEvent('AddPaymentInfo', $eventData, $userData);
    }

    /**
     * Send Search event
     */
    public function sendSearchEvent($searchData, $userData = [])
    {
        $eventData = [
            'search_string' => $searchData['query'] ?? '',
            'content_ids' => $searchData['content_ids'] ?? [],
            'content_type' => 'product',
            'currency' => 'BDT',
            'value' => $searchData['value'] ?? 0
        ];

        return $this->sendEvent('Search', $eventData, $userData);
    }

    /**
     * Send Lead event
     */
    public function sendLeadEvent($leadData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $leadData['value'] ?? 0,
        ];

        return $this->sendEvent('Lead', $eventData, $userData);
    }

    /**
     * Send CompleteRegistration event
     */
    public function sendCompleteRegistrationEvent($registrationData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $registrationData['value'] ?? 0,
            'status' => $registrationData['status'] ?? true,
        ];

        return $this->sendEvent('CompleteRegistration', $eventData, $userData);
    }

    /**
     * Send Contact event
     */
    public function sendContactEvent($contactData, $userData = [])
    {
        $eventData = [
            'content_name' => $contactData['subject'] ?? 'Contact Form',
        ];

        return $this->sendEvent('Contact', $eventData, $userData);
    }

    /**
     * Send CustomizeProduct event
     */
    public function sendCustomizeProductEvent($customizeData, $userData = [])
    {
        $eventData = [
            'content_ids' => [$customizeData['product_id'] ?? ''],
            'content_type' => 'product',
            'content_name' => $customizeData['name'] ?? '',
            'currency' => 'BDT',
            'value' => $customizeData['price'] ?? 0,
            'customization' => $customizeData['options'] ?? []
        ];

        return $this->sendEvent('CustomizeProduct', $eventData, $userData);
    }

    /**
     * Prepare user data for Facebook
     */
    protected function prepareUserData($userData)
    {
        $prepared = [];

        if (!empty($userData['email'])) {
            $prepared['em'] = hash('sha256', strtolower(trim($userData['email'])));
        }

        if (!empty($userData['phone'])) {
            $prepared['ph'] = hash('sha256', preg_replace('/[^0-9]/', '', $userData['phone']));
        }

        if (!empty($userData['first_name'])) {
            $prepared['fn'] = hash('sha256', strtolower(trim($userData['first_name'])));
        }

        if (!empty($userData['last_name'])) {
            $prepared['ln'] = hash('sha256', strtolower(trim($userData['last_name'])));
        }

        if (!empty($userData['city'])) {
            $prepared['ct'] = hash('sha256', strtolower(trim($userData['city'])));
        }

        if (!empty($userData['state'])) {
            $prepared['st'] = hash('sha256', strtolower(trim($userData['state'])));
        }

        if (!empty($userData['zip'])) {
            $prepared['zp'] = hash('sha256', trim($userData['zip']));
        }

        if (!empty($userData['country'])) {
            $prepared['country'] = hash('sha256', strtolower(trim($userData['country'])));
        }

        // Client IP address
        $prepared['client_ip_address'] = request()->ip();

        // User agent
        $prepared['client_user_agent'] = request()->userAgent();

        // Click / browser ids are forwarded raw (Meta hashes only PII). They are
        // read from the raw cookie jar on purpose: Laravel would try to decrypt
        // these JS-written cookies and hand back null.
        $fbp = isset($_COOKIE['_fbp']) ? preg_replace('/[^A-Za-z0-9._\-]/', '', (string) $_COOKIE['_fbp']) : '';
        if ($fbp !== '') {
            $prepared['fbp'] = $fbp;
        }

        $fbc = isset($_COOKIE['_fbc']) ? preg_replace('/[^A-Za-z0-9._\-]/', '', (string) $_COOKIE['_fbc']) : '';
        if ($fbc !== '') {
            $prepared['fbc'] = $fbc;
        }

        return $prepared;
    }

    /**
     * Check if tracking is enabled
     */
    public function isEnabled()
    {
        return $this->enabled && $this->pixelId && $this->accessToken;
    }

    /**
     * Get tracking configuration
     */
    public function getConfig()
    {
        return [
            'pixel_id' => $this->pixelId,
            'api_version' => $this->apiVersion,
            'enabled' => $this->enabled
        ];
    }
}
