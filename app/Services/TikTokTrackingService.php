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
    protected $testEventCode;

    public function __construct()
    {
        $settings = GeneralSetting::first();
        $this->pixelId = $settings->tiktok_pixel_id ?? null;
        $this->accessToken = $settings->tiktok_access_token ?? null;
        $this->enabled = $settings->tiktok_server_side_tracking ?? false;
        $this->testMode = $settings->tiktok_test_mode ?? false;
        $this->testEventCode = $settings->tiktok_test_event_code ?? null;
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

        // Test mode without a code would silently send a REAL event: the
        // Events API only routes to the Test Events tab when test_event_code
        // is present. Refuse instead of polluting production data.
        if ($this->testMode && blank($this->testEventCode)) {
            Log::warning('TikTok Events API test mode is on but test_event_code is empty', [
                'event' => $eventName,
            ]);

            return false;
        }

        try {
            // Map event name to TikTok standard events
            $tiktokEventName = $this->mapEventName($eventName);

            $payload = [
                'event_source' => 'web',
                'event_source_id' => (string) $this->pixelId,
                'data' => [
                    [
                        'event' => $tiktokEventName,
                        'event_time' => time(),
                        'event_id' => $eventId,
                        'user' => $this->prepareUserData($userData),
                        'properties' => $this->prepareEventProperties($eventData),
                        'page' => [
                            'url' => request()->headers->get('referer') ?: request()->fullUrl(),
                        ],
                    ],
                ],
            ];

            if ($this->testMode) {
                $payload['test_event_code'] = (string) $this->testEventCode;
            }

            // Add access token to headers
            $headers = [
                'Content-Type' => 'application/json',
                'Access-Token' => $this->accessToken,
            ];

            // Events API 2.0. The old /open_api/v1/pixel/send/ endpoint and its
            // event_batch/partner_name envelope are not the current contract.
            $endpoint = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';

            $response = Http::withHeaders($headers)->post($endpoint, $payload);

            $responseData = $response->json();
            $apiCode = is_array($responseData) ? ($responseData['code'] ?? null) : null;

            // Events API 2.0 answers HTTP 200 for parameter/auth errors too;
            // the business result is `code`. Treating 200 as success would log
            // a delivery that TikTok never accepted.
            if ($response->successful() && ($apiCode === null || (int) $apiCode === 0)) {
                Log::info('TikTok event sent successfully', [
                    'event' => $eventName,
                    'tiktok_event' => $tiktokEventName,
                    'event_id' => $eventId,
                    'response' => $responseData,
                ]);

                $this->recordLocally($eventName, $eventId, $eventData);

                return true;
            }

            Log::error('TikTok event failed', [
                'event' => $eventName,
                'status' => $response->status(),
                'code' => $apiCode,
                'response' => $response->body(),
            ]);

            return false;
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
     *
     * The order id is the deterministic id the confirmation page uses, so the
     * browser pixel and the Events API call deduplicate instead of counting the
     * sale twice.
     */
    public function sendPurchaseEvent($orderData, $userData = [], ?string $eventId = null)
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $orderData['amount'] ?? 0,
            'contents' => $this->prepareContents($orderData['product_ids'] ?? []),
            'content_type' => 'product',
            'order_id' => $orderData['order_id'] ?? null,
            'quantity' => $orderData['num_items'] ?? 1,
        ];

        return $this->sendEvent('Purchase', $eventData, $userData, $eventId);
    }

    /**
     * Send AddToCart event
     */
    public function sendAddToCartEvent($productData, $userData = [], ?string $eventId = null)
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $productData['price'] ?? 0,
            'contents' => [
                [
                    'content_id' => (string) ($productData['product_id'] ?? ''),
                    'quantity' => $productData['quantity'] ?? 1,
                    'price' => $productData['price'] ?? 0,
                ],
            ],
            'content_type' => 'product',
            'content_name' => $productData['name'] ?? '',
        ];

        return $this->sendEvent('AddToCart', $eventData, $userData, $eventId);
    }

    /**
     * Send ViewContent event
     */
    public function sendViewContentEvent($productData, $userData = [], ?string $eventId = null)
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $productData['price'] ?? 0,
            'contents' => [
                [
                    'content_id' => (string) ($productData['product_id'] ?? ''),
                    'quantity' => 1,
                    'price' => $productData['price'] ?? 0,
                ],
            ],
            'content_type' => 'product',
            'content_name' => $productData['name'] ?? '',
            'content_category' => $productData['category'] ?? '',
        ];

        return $this->sendEvent('ViewContent', $eventData, $userData, $eventId);
    }

    /**
     * Send InitiateCheckout event
     */
    public function sendInitiateCheckoutEvent($cartData, $userData = [], ?string $eventId = null)
    {
        $eventData = [
            'currency' => 'BDT',
            'value' => $cartData['total'] ?? 0,
            'contents' => $this->prepareContents($cartData['product_ids'] ?? []),
            'content_type' => 'product',
            'number_of_items' => $cartData['num_items'] ?? 0,
        ];

        return $this->sendEvent('InitiateCheckout', $eventData, $userData, $eventId);
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
     * Prepare user data for TikTok Events API 2.0.
     *
     * Only the documented `user` keys are sent. PII is SHA-256 hashed after
     * normalisation (lowercase email, E.164 phone); click/browser ids and the
     * network fields are forwarded raw, which is what TikTok expects.
     */
    protected function prepareUserData($userData)
    {
        $prepared = [];

        if (!empty($userData['email'])) {
            $prepared['email'] = hash('sha256', strtolower(trim((string) $userData['email'])));
        }

        $phone = \App\Support\PhoneNormalizer::e164($userData['phone'] ?? null);

        if ($phone !== '') {
            $prepared['phone'] = hash('sha256', $phone);
        }

        if (!empty($userData['external_id'])) {
            $prepared['external_id'] = hash('sha256', trim((string) $userData['external_id']));
        }

        // ttclid arrives in the landing URL, _ttp is written by the pixel. Both
        // are JS cookies, so read the raw jar: Laravel would try to decrypt them.
        $ttclid = $userData['ttclid'] ?? $this->rawCookie('ttclid');

        if (is_string($ttclid) && $ttclid !== '') {
            $prepared['ttclid'] = preg_replace('/[^A-Za-z0-9._\-]/', '', $ttclid);
        }

        $ttp = $userData['ttp'] ?? $this->rawCookie('_ttp');

        if (is_string($ttp) && $ttp !== '') {
            $prepared['ttp'] = preg_replace('/[^A-Za-z0-9._\-]/', '', $ttp);
        }

        $ip = $userData['ip'] ?? request()->ip();

        if (is_string($ip) && $ip !== '') {
            $prepared['ip'] = $ip;
        }

        $userAgent = $userData['user_agent'] ?? request()->userAgent();

        if (is_string($userAgent) && $userAgent !== '') {
            $prepared['user_agent'] = $userAgent;
        }

        if (!empty($userData['locale'])) {
            $prepared['locale'] = str_replace('_', '-', trim((string) $userData['locale']));
        }

        return array_filter($prepared, fn ($value) => $value !== '');
    }

    private function rawCookie(string $name): ?string
    {
        $value = $_COOKIE[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Prepare event properties in TikTok's `contents[]` shape.
     *
     * The service's older convenience methods called the line item fields
     * `item_id`/`item_price` (Meta's naming). TikTok expects `content_id` and
     * `price`; both spellings are accepted here so an existing caller cannot
     * silently produce a payload TikTok ignores.
     */
    protected function prepareEventProperties($eventData)
    {
        $properties = [];

        foreach ([
            'currency', 'value', 'content_type', 'content_name',
            'content_category', 'content_id', 'quantity', 'order_id',
            'search_string', 'description', 'query', 'results_count',
        ] as $key) {
            if (isset($eventData[$key])) {
                $properties[$key] = $eventData[$key];
            }
        }

        if (isset($eventData['contents']) && is_array($eventData['contents'])) {
            $properties['contents'] = $this->normaliseContents($eventData['contents']);
        }

        return $properties;
    }

    /**
     * Prepare contents array from a plain list of product ids.
     */
    protected function prepareContents($productIds)
    {
        if (!is_array($productIds)) {
            return [];
        }

        return $this->normaliseContents($productIds);
    }

    private function normaliseContents(array $contents): array
    {
        $normalised = [];

        foreach ($contents as $item) {
            if (is_array($item)) {
                $contentId = $item['content_id'] ?? $item['item_id'] ?? null;

                if ($contentId === null || $contentId === '') {
                    continue;
                }

                $line = [
                    'content_id' => (string) $contentId,
                    'quantity' => (int) ($item['quantity'] ?? 1),
                ];

                $price = $item['price'] ?? $item['item_price'] ?? null;

                if ($price !== null && $price !== '') {
                    $line['price'] = $price;
                }

                if (!empty($item['content_name'])) {
                    $line['content_name'] = (string) $item['content_name'];
                }

                $normalised[] = $line;
                continue;
            }

            if ($item === null || $item === '') {
                continue;
            }

            $normalised[] = [
                'content_id' => (string) $item,
                'quantity' => 1,
            ];
        }

        return $normalised;
    }

    /**
     * Generate unique event ID
     */
    protected function generateEventId()
    {
        return 'evt_' . uniqid() . '_' . time();
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
