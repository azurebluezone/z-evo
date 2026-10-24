<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\GeneralSetting;

class TikTokTrackingService
{
    protected $pixelId;
    protected $accessToken;
    protected $enabled;
    protected $testMode;

    public function __construct()
    {
        $settings = GeneralSetting::first();
        $this->pixelId = $settings->tiktok_pixel_id ?? null;
        $this->accessToken = $settings->tiktok_access_token ?? null;
        $this->enabled = $settings->tiktok_server_side_tracking ?? false;
        $this->testMode = $settings->tiktok_test_mode ?? false;
    }

    /**
     * Send server-side event to TikTok Events API
     * Documentation: https://business-api.tiktok.com/portal/docs?id=1738348800889857
     *
     * `$eventId` must be the SAME id the browser pixel used, so TikTok can
     * deduplicate the pair and the admin dashboard counts the event once.
     */
    public function sendEvent($eventName, $eventData = [], $userData = [], ?string $eventId = null)
    {
        if (!$this->enabled || !$this->pixelId || !$this->accessToken) {
            Log::info('TikTok Server-Side Tracking disabled or not configured');
            return false;
        }

        if (!\App\Support\Consent::marketing()) {
            Log::info('TikTok Events API skipped: marketing consent not granted', ['event' => $eventName]);
            return false;
        }

        $eventId = $this->normaliseEventId($eventId) ?? $this->generateEventId();

        try {
            // Map event name to TikTok standard events
            $tiktokEventName = $this->mapEventName($eventName);

            $payload = [
                'event_batch' => [
                    [
                        'event' => $tiktokEventName,
                        'event_time' => time(),
                        'event_id' => $eventId,
                        'user_data' => $this->prepareUserData($userData),
                        'event_properties' => $this->prepareEventProperties($eventData),
                        'context' => [
                            'ad' => [
                                'adcode' => $this->getAdcode(),
                            ],
                        ],
                    ],
                ],
                'partner_name' => 'GSBD_ECOMMERCE',
                'page_url' => request()->url(),
                'pixel_code' => $this->pixelId,
                'test' => $this->testMode ? true : false,
            ];

            // Add access token to headers
            $headers = [
                'Content-Type' => 'application/json',
                'Access-Token' => $this->accessToken,
            ];

            $endpoint = 'https://business-api.tiktok.com/open_api/v1/pixel/send/';

            $response = Http::withHeaders($headers)->post($endpoint, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('TikTok event sent successfully', [
                    'event' => $eventName,
                    'tiktok_event' => $tiktokEventName,
                    'event_id' => $eventId,
                    'response' => $responseData,
                ]);

                $this->recordLocally($eventName, $eventId, $eventData);

                return true;
            } else {
                Log::error('TikTok event failed', [
                    'event' => $eventName,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('TikTok tracking error', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Mirror the server-side event into event_logs with tracking_method=server so
     * the dashboard's Server vs Browser comparison is complete.
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
                    'ts' => time(),
                    'event_data' => $eventData,
                    'channel' => 'tiktok',
                ]],
                \App\Services\Analytics\EventRecorder::METHOD_SERVER
            );
        } catch (\Throwable $e) {
            // Analytics bookkeeping must never break the actual API call.
            Log::warning('TikTok local event record failed', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reject anything that is not a safe token before it is stored.
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
    public function sendPurchaseEvent($orderData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $orderData['amount'] ?? 0,
            'contents' => $this->prepareContents($orderData['product_ids'] ?? []),
            'content_type' => 'product',
            'order_id' => $orderData['order_id'] ?? null,
            'quantity' => $orderData['num_items'] ?? 1,
        ];

        return $this->sendEvent('Purchase', $eventData, $userData);
    }

    /**
     * Send AddToCart event
     */
    public function sendAddToCartEvent($productData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $productData['price'] ?? 0,
            'contents' => [
                [
                    'item_id' => $productData['product_id'] ?? '',
                    'quantity' => $productData['quantity'] ?? 1,
                    'item_price' => $productData['price'] ?? 0,
                ],
            ],
            'content_type' => 'product',
            'content_name' => $productData['name'] ?? '',
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
            'contents' => [
                [
                    'item_id' => $productData['product_id'] ?? '',
                    'quantity' => 1,
                    'item_price' => $productData['price'] ?? 0,
                ],
            ],
            'content_type' => 'product',
            'content_name' => $productData['name'] ?? '',
            'content_category' => $productData['category'] ?? '',
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
            'contents' => $this->prepareContents($cartData['product_ids'] ?? []),
            'content_type' => 'product',
            'number_of_items' => $cartData['num_items'] ?? 0,
        ];

        return $this->sendEvent('InitiateCheckout', $eventData, $userData);
    }

    /**
     * Send CompletePayment event
     */
    public function sendCompletePaymentEvent($paymentData, $userData = [])
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $paymentData['amount'] ?? 0,
            'payment_info' => [
                'method' => $paymentData['method'] ?? 'unknown',
            ],
        ];

        return $this->sendEvent('CompletePayment', $eventData, $userData);
    }

    /**
     * Map event names to TikTok standard events
     */
    protected function mapEventName($eventName)
    {
        $mapping = [
            'PageView' => 'PageView',
            'ViewContent' => 'ViewContent',
            'AddToCart' => 'AddToCart',
            'AddToWishlist' => 'AddToWishlist',
            'AddPaymentInfo' => 'AddPaymentInfo',
            'InitiateCheckout' => 'InitiateCheckout',
            'BeginCheckout' => 'InitiateCheckout',
            'CompletePayment' => 'CompletePayment',
            'Purchase' => 'PlaceAnOrder',
            'Search' => 'Search',
            'Lead' => 'Lead',
            'CompleteRegistration' => 'CompleteRegistration',
            'Contact' => 'Contact',
            'CustomizeProduct' => 'CustomizeProduct',
            'FakeOrderAttempt' => 'SubmitForm',
        ];

        return $mapping[$eventName] ?? $eventName;
    }

    /**
     * Prepare user data for TikTok
     */
    protected function prepareUserData($userData)
    {
        $prepared = [];

        // Email (SHA256 hashed)
        if (!empty($userData['email'])) {
            $prepared['email'] = hash('sha256', strtolower(trim($userData['email'])));
        }

        // Phone number (SHA256 hashed, digits only)
        if (!empty($userData['phone'])) {
            $prepared['phone_number'] = hash('sha256', preg_replace('/[^0-9]/', '', $userData['phone']));
        }

        // First name (SHA256 hashed)
        if (!empty($userData['first_name'])) {
            $prepared['first_name'] = hash('sha256', strtolower(trim($userData['first_name'])));
        }

        // Last name (SHA256 hashed)
        if (!empty($userData['last_name'])) {
            $prepared['last_name'] = hash('sha256', strtolower(trim($userData['last_name'])));
        }

        // City (SHA256 hashed)
        if (!empty($userData['city'])) {
            $prepared['city'] = hash('sha256', strtolower(trim($userData['city'])));
        }

        // State/Province (SHA256 hashed)
        if (!empty($userData['state'])) {
            $prepared['state'] = hash('sha256', strtolower(trim($userData['state'])));
        }

        // ZIP code (SHA256 hashed)
        if (!empty($userData['zip'])) {
            $prepared['zip'] = hash('sha256', trim($userData['zip']));
        }

        // Country code (2-letter ISO code, not hashed)
        if (!empty($userData['country'])) {
            $prepared['country'] = strtoupper(trim($userData['country']));
        }

        // Client IP address
        $prepared['client_ip_address'] = request()->ip();

        // User agent
        $prepared['client_user_agent'] = request()->userAgent();

        return $prepared;
    }

    /**
     * Prepare event properties
     */
    protected function prepareEventProperties($eventData)
    {
        $properties = [];

        if (isset($eventData['currency'])) {
            $properties['currency'] = $eventData['currency'];
        }

        if (isset($eventData['value'])) {
            $properties['value'] = $eventData['value'];
        }

        if (isset($eventData['content_type'])) {
            $properties['content_type'] = $eventData['content_type'];
        }

        if (isset($eventData['content_name'])) {
            $properties['content_name'] = $eventData['content_name'];
        }

        if (isset($eventData['content_category'])) {
            $properties['content_category'] = $eventData['content_category'];
        }

        if (isset($eventData['quantity'])) {
            $properties['quantity'] = $eventData['quantity'];
        }

        if (isset($eventData['order_id'])) {
            $properties['order_id'] = $eventData['order_id'];
        }

        if (isset($eventData['contents']) && is_array($eventData['contents'])) {
            $properties['contents'] = $eventData['contents'];
        }

        return $properties;
    }

    /**
     * Prepare contents array
     */
    protected function prepareContents($productIds)
    {
        $contents = [];
        
        foreach ($productIds as $productId) {
            $contents[] = [
                'item_id' => $productId,
                'quantity' => 1,
            ];
        }
        
        return $contents;
    }

    /**
     * Generate unique event ID
     */
    protected function generateEventId()
    {
        return 'evt_' . uniqid() . '_' . time();
    }

    /**
     * Get TikTok Pixel TTCLID cookie value
     */
    protected function getAdcode()
    {
        return request()->cookie('ttclid') ?? null;
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
            'enabled' => $this->enabled,
            'test_mode' => $this->testMode,
        ];
    }

    /**
     * Test connection with TikTok API
     */
    public function testConnection()
    {
        if (!$this->pixelId || !$this->accessToken) {
            return ['success' => false, 'message' => 'Pixel ID or Access Token missing'];
        }

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Access-Token' => $this->accessToken,
            ];

            // Try to get pixel info
            $endpoint = "https://business-api.tiktok.com/open_api/v1.3/pixel/get/";
            $payload = [
                'pixel_codes' => [$this->pixelId],
            ];

            $response = Http::withHeaders($headers)->post($endpoint, $payload);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connection successful', 'response' => $response->json()];
            } else {
                return ['success' => false, 'message' => 'Connection failed: ' . $response->body()];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
