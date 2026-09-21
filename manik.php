<?php
/**
 * Printify Primer Payment Script & API (PHP Port of manik.py)
 * Fully optimized for cPanel (Apache / LiteSpeed / Nginx / PHP-FPM) and CLI.
 * 
 * cPanel Web Usage:
 *   https://yourdomain.com/manik.php?cc=5154620036376364|02|2031|666
 *   https://yourdomain.com/manik.php?num=5154620036376364&mm=02&yy=2031&cvv=666
 */

// ==============================================================================
// CPANEL & SERVER OPTIMIZATIONS (DISABLE ERROR DISPLAY TO PREVENT JSON CORRUPTION)
// ==============================================================================
error_reporting(0);
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('log_errors', '1');
@ini_set('memory_limit', '256M');
@set_time_limit(120);

// ==============================================================================
// DEFAULT TOKENS & SESSION DATA (EMBEDDED FALLBACKS FOR CPANEL WITHOUT main.har)
// ==============================================================================
const DEFAULT_CLIENT_TOKEN = 
    "eyJhbGciOiJIUzI1NiIsImtpZCI6ImNsaWVudC10b2tlbi1zaWduaW5nLWtleSIsInR5cCI6IkpXVCJ9." .
    "eyJleHAiOjE3OTAwNzAwNTAsImFjY2Vzc1Rva2VuIjoiZXlKaGJHY2lPaUpJVXpJMU5pSXNJbXRwWkNJ" .
    "NkltTnNhV1Z1ZEMxMGIydGxiaTF6YVdkdWFXNW5MV3RsZVNJc0luUjVjQ0k2SWtwWFZDSjkuZXlKcFlY" .
    "UWlPakUzT0RrNU9ETTJOVEFzSW5OMVlpSTZJak5rWWpsbE56TmhMVEUwTURVdE5EVTRaQzA1TkdNMkxU" .
    "QTBOV0UxTldNNU1XUmpPU0lzSW1wMGFTSTZJamc0WkRVMFpHWXhMV1ExWWprdE5HSXhOaTA0TUROa0xU" .
    "Z3hNVFV6WWpjMU5tUTFaU0lzSW1WNGNDSTZNVGM1TURBM01EQTFNSDAuU3J0d3F5MDRUSzlBeUllQkZl" .
    "UjFINGR5RVRWODhwdG45VzdIMGw0Y3RPWSIsImFuYWx5dGljc1VybCI6Imh0dHBzOi8vYW5hbHl0aWNz" .
    "LmFwaS5wcm9kdWN0aW9uLmNvcmUucHJpbWVyLmlvL21peHBhbmVsIiwiYW5hbHl0aWNzVXJsVjIiOiJo" .
    "dHRwczovL2FuYWx5dGljcy5wcm9kdWN0aW9uLmRhdGEucHJpbWVyLmlvL2NoZWNrb3V0L3RyYWNrIiwi" .
    "aW50ZW50IjoiQ0hFQ0tPVVQiLCJjb25maWd1cmF0aW9uVXJsIjoiaHR0cHM6Ly9hcGkucHJpbWVyLmlv" .
    "L2NsaWVudC1zZGsvY29uZmlndXJhdGlvbiIsImNvcmVVcmwiOiJodHRwczovL2FwaS5wcmltZXIuaW8i" .
    "LCJwY2lVcmwiOiJodHRwczovL3Nkay5hcGkucHJpbWVyLmlvIiwiZW52IjoiUFJPRFVDVElPTiIsInBh" .
    "eW1lbnRGbG93IjoiREVGQVVMVCJ9.74kkgk3CF90cDR2zCLx6Geh1DyVXYoxYtIyr6BTC_gk";

const DEFAULT_PRIMER_CLIENT_TOKEN = 
    "eyJhbGciOiJIUzI1NiIsImtpZCI6ImNsaWVudC10b2tlbi1zaWduaW5nLWtleSIsInR5cCI6IkpXVCJ9." .
    "eyJpYXQiOjE3ODk5ODM2NTAsInN1YiI6IjNkYjllNzNhLTE0MDUtNDU4ZC05NGM2LTA0NWE1NWM5MWRj" .
    "OSIsImp0aSI6Ijg4ZDU0ZGYxLWQ1YjktNGIxNi04MDNkLTgxMTUzYjc1NmQ1ZSIsImV4cCI6MTc5MDA3" .
    "MDA1MH0.Srtwqy04TK9AyIeBFeR1H4dyETV88ptn9W7H0l4ctOY";

const DEFAULT_CHECKOUT_SESSION_ID = "b901f53d-ff69-4f6c-8e23-e3a849000a20";
const DEFAULT_EXTERNAL_ID = "28510217";
const DEFAULT_AB_TEST_TOKEN = "2c3e8713-7e2d-4c26-ac52-69f60bf01f54";
const DEFAULT_ANTIFRAUD_SESSION_ID = "2c3e8713-7e2d-4c26-ac52-69f60bf01f54";
const DEFAULT_AUTHORIZATION = "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE3OTI1NzU1MTEsInN1YiI6IjVmZjRkYmY3LTdhODQtNDcyZC1iNjI2LTYwNjhhN2I1ZmE0MiIsInNpZCI6IlFnckJqdlgwaDAxY1hXWjNTeVVVbHUwNDNHRHM0eTF1RU96SnF6MlktMWciLCJkYXRhIjp7InVzZXJfaWQiOjI4NTEwMjE3LCJwbGF0Zm9ybV9icmFuZCI6InByaW50aWZ5In19.TW1OQnO2dYP6GiUhhF1gBp8xG7Q5l7Sp8IFpg3WYeKM";

const DEFAULT_ADDRESS = [
    "firstName" => "dsfg sdfg",
    "lastName" => "",
    "addressLine1" => "216 Baltimore Way",
    "addressLine2" => "",
    "city" => "Daly City",
    "state" => "AL",
    "postalCode" => "94014",
    "countryCode" => "US"
];

const DEFAULT_CARD = [
    "number" => "5154620036376364",
    "cvv" => "666",
    "expirationMonth" => "02",
    "expirationYear" => "2031",
    "cardholderName" => "dsfg sdfg"
];

const DEFAULT_CARD_ALIAS = "dfgdfsg sdfg";

// Terminal Colors (for CLI mode)
const GREEN  = "\033[92m";
const RED    = "\033[91m";
const YELLOW = "\033[93m";
const CYAN   = "\033[96m";
const BLUE   = "\033[94m";
const BOLD   = "\033[1m";
const RESET  = "\033[0m";

/**
 * Generate UUID v4
 */
function generateUuidV4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Decode JWT payload without verifying signature.
 */
function decodeJwtPayload(string $token): array {
    try {
        $parts = explode('.', $token);
        if (count($parts) >= 2) {
            $remainder = strlen($parts[1]) % 4;
            $padded = $parts[1];
            if ($remainder) {
                $padded .= str_repeat('=', 4 - $remainder);
            }
            $base64 = strtr($padded, '-_', '+/');
            $decoded = base64_decode($base64);
            if ($decoded !== false) {
                $json = json_decode($decoded, true);
                if (is_array($json)) {
                    return $json;
                }
            }
        }
    } catch (\Throwable $e) {
        // ignore
    }
    return [];
}

/**
 * Load configuration with default tokens, optionally updating from HAR file.
 */
function loadSessionConfig(?string $harPath = null, bool $silent = false): array {
    $config = [
        'client_token' => DEFAULT_CLIENT_TOKEN,
        'primer_client_token' => DEFAULT_PRIMER_CLIENT_TOKEN,
        'checkout_session_id' => DEFAULT_CHECKOUT_SESSION_ID,
        'external_id' => DEFAULT_EXTERNAL_ID,
        'ab_test_token' => DEFAULT_AB_TEST_TOKEN,
        'antifraud_session_id' => DEFAULT_ANTIFRAUD_SESSION_ID,
        'authorization' => DEFAULT_AUTHORIZATION,
        'address' => DEFAULT_ADDRESS,
        'card_data' => DEFAULT_CARD,
        'card_alias' => DEFAULT_CARD_ALIAS
    ];

    if ($harPath && file_exists($harPath) && is_readable($harPath)) {
        try {
            $harContent = @file_get_contents($harPath);
            if ($harContent !== false) {
                $harData = @json_decode($harContent, true);

                $entries = $harData['log']['entries'] ?? [];
                foreach ($entries as $entry) {
                    $req = $entry['request'] ?? [];
                    $url = $req['url'] ?? '';
                    $method = $req['method'] ?? '';
                    $postText = $req['postData']['text'] ?? '';

                    if (str_contains($url, 'client-session') && $method === 'PATCH' && $postText) {
                        try {
                            $body = json_decode($postText, true);
                            if (!empty($body['clientToken'])) {
                                $config['client_token'] = $body['clientToken'];
                                $jwtPayload = decodeJwtPayload($body['clientToken']);
                                if (!empty($jwtPayload['accessToken'])) {
                                    $config['primer_client_token'] = $jwtPayload['accessToken'];
                                }
                            }
                            if (!empty($body['externalId'])) {
                                $config['external_id'] = $body['externalId'];
                            }
                            if (!empty($body['address'])) {
                                $config['address'] = $body['address'];
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }

                    foreach ($req['headers'] ?? [] as $header) {
                        $name = strtolower($header['name'] ?? '');
                        $val = $header['value'] ?? '';
                        if ($name === 'primer-client-token' && $val) {
                            $config['primer_client_token'] = $val;
                        } elseif ($name === 'primer-sdk-checkout-session-id' && $val) {
                            $config['checkout_session_id'] = $val;
                        } elseif ($name === 'x-ab-test-token' && $val) {
                            $config['ab_test_token'] = $val;
                        } elseif ($name === 'x-antifraud-session-id' && $val) {
                            $config['antifraud_session_id'] = $val;
                        } elseif ($name === 'authorization' && $val) {
                            $config['authorization'] = $val;
                        }
                    }

                    if (str_contains($url, 'payment-instruments') && $method === 'POST' && $postText) {
                        try {
                            $cBody = json_decode($postText, true);
                            if (!empty($cBody['paymentInstrument'])) {
                                $config['card_data'] = $cBody['paymentInstrument'];
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }

                    if (str_contains($url, '/cards') && $method === 'POST' && $postText) {
                        try {
                            $cardsBody = json_decode($postText, true);
                            if (!empty($cardsBody['cardAlias'])) {
                                $config['card_alias'] = $cardsBody['cardAlias'];
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            if (!$silent) {
                echo YELLOW . "[!] Notice: Could not parse HAR (" . $e->getMessage() . "). Using built-in defaults." . RESET . "\n";
            }
        }
    }

    return $config;
}

class PrintifyPrimerRequester {
    private string $clientToken;
    private string $primerToken;
    private string $checkoutSessionId;
    private string $externalId;
    private string $abTestToken;
    private string $antifraudSessionId;
    private ?string $authorization;
    private array $address;
    private array $cardData;
    private string $cardAlias;
    private string $userAgent;
    private ?string $cookieFile = null;
    private bool $silent;
    private ?string $proxy = null;
    private array $logs = [];
    private ?string $lastError = null;
    private ?array $lastTokenData = null;

    public function __construct(array $sessionData, ?string $authToken = null, bool $silent = false, ?string $proxy = null) {
        $this->clientToken = $sessionData['client_token'] ?? DEFAULT_CLIENT_TOKEN;
        $this->primerToken = $sessionData['primer_client_token'] ?? DEFAULT_PRIMER_CLIENT_TOKEN;
        $this->checkoutSessionId = $sessionData['checkout_session_id'] ?? DEFAULT_CHECKOUT_SESSION_ID;
        $this->externalId = (string)($sessionData['external_id'] ?? DEFAULT_EXTERNAL_ID);
        $this->abTestToken = $sessionData['ab_test_token'] ?? DEFAULT_AB_TEST_TOKEN;
        $this->antifraudSessionId = $sessionData['antifraud_session_id'] ?? DEFAULT_ANTIFRAUD_SESSION_ID;
        $this->authorization = $authToken ?? ($sessionData['authorization'] ?? null);
        $this->address = $sessionData['address'] ?? DEFAULT_ADDRESS;
        $this->cardData = $sessionData['card_data'] ?? DEFAULT_CARD;
        $this->cardAlias = $sessionData['card_alias'] ?? DEFAULT_CARD_ALIAS;
        $this->userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36";
        $this->silent = $silent;
        $this->proxy = $proxy;

        // Safe cookie handling for Linux cPanel and Windows
        $tempDir = sys_get_temp_dir();
        if (!is_dir($tempDir) || !is_writable($tempDir)) {
            $tempDir = __DIR__;
        }
        $created = @tempnam($tempDir, 'prm_ck_');
        $this->cookieFile = $created !== false ? $created : null;
    }

    public function __destruct() {
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    /**
     * Helper to log messages
     */
    private function logOutput(string $msg, string $type = 'info'): void {
        $clean = trim(preg_replace('/\033\[[0-9;]*m/', '', $msg));
        if ($clean !== '') {
            $this->logs[] = [
                'time' => date('H:i:s'),
                'type' => $type,
                'message' => $clean
            ];
        }
        if (!$this->silent) {
            echo $msg;
        }
    }

    /**
     * Helper to perform HTTP request via cURL (optimized for shared hosting & cPanel)
     */
    private function executeRequest(string $method, string $url, array $headers, $payload = null, int $timeout = 20): array {
        $ch = curl_init();

        $formattedHeaders = [];
        foreach ($headers as $k => $v) {
            $formattedHeaders[] = "{$k}: {$v}";
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        if ($this->cookieFile) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        }

        if (!empty($this->proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }

        if ($payload !== null) {
            $body = is_string($payload) ? $payload : json_encode($payload);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception("cURL Error: " . $curlError);
        }

        return [
            'status' => $statusCode,
            'body' => $response
        ];
    }

    /**
     * Step 1: PATCH https://payments.printify.com/api/payments/client-session
     */
    public function step1PatchClientSession(): bool {
        $this->logOutput("\n" . BOLD . BLUE . "------------------------------------------------------------" . RESET . "\n");
        $this->logOutput(BOLD . BLUE . "[Step 1] Initializing Printify Payment Session" . RESET . "\n");
        $this->logOutput(BOLD . BLUE . "------------------------------------------------------------" . RESET . "\n");

        $url = "https://payments.printify.com/api/payments/client-session";
        $headers = [
            "accept" => "application/json, text/plain, */*",
            "content-type" => "application/json",
            "origin" => "https://printify.com",
            "referer" => "https://printify.com/",
            "user-agent" => $this->userAgent,
            "x-ab-test-token" => $this->abTestToken
        ];
        if ($this->authorization) {
            $headers["authorization"] = $this->authorization;
        }

        $payload = [
            "clientToken" => $this->clientToken,
            "externalId" => (string)$this->externalId,
            "sessionType" => "first_payment",
            "address" => $this->address
        ];

        $this->logOutput("  " . CYAN . "URL:" . RESET . " {$url}\n");
        $this->logOutput("  " . CYAN . "External ID:" . RESET . " {$this->externalId}\n");

        try {
            $res = $this->executeRequest("PATCH", $url, $headers, $payload, 15);
            $this->logOutput("  " . CYAN . "Status:" . RESET . " {$res['status']}\n");
            $this->logOutput("  " . CYAN . "Response:" . RESET . " {$res['body']}\n");

            if (in_array($res['status'], [200, 204], true)) {
                $this->logOutput("  " . GREEN . "[+] Session Validated." . RESET . "\n");
                return true;
            } else {
                $this->logOutput("  " . YELLOW . "[!] Status {$res['status']}. Proceeding directly with Primer tokenization..." . RESET . "\n");
                return true;
            }
        } catch (\Throwable $e) {
            $this->lastError = "Step 1 Error: " . $e->getMessage();
            $this->logOutput("  " . RED . "[-] Step 1 Error: " . $e->getMessage() . RESET . "\n", 'error');
            return false;
        }
    }

    /**
     * Step 2: POST https://sdk.api.primer.io/payment-instruments (Tokenize Card)
     */
    public function step2TokenizeCard(?array $cardInfo = null): ?string {
        $this->logOutput("\n" . BOLD . BLUE . "------------------------------------------------------------" . RESET . "\n");
        $this->logOutput(BOLD . BLUE . "[Step 2] Tokenizing Card via Primer SDK" . RESET . "\n");
        $this->logOutput(BOLD . BLUE . "------------------------------------------------------------" . RESET . "\n");

        $targetCard = $cardInfo ?? $this->cardData;
        if (!$targetCard) {
            $this->lastError = "No card details provided for tokenization.";
            $this->logOutput("  " . RED . "[-] No card details provided for tokenization." . RESET . "\n", 'error');
            return null;
        }

        $url = "https://sdk.api.primer.io/payment-instruments";
        $requestId = generateUuidV4();

        $headers = [
            "accept" => "*/*",
            "content-type" => "application/json",
            "origin" => "https://sdk.primer.io",
            "referer" => "https://sdk.primer.io/",
            "user-agent" => $this->userAgent,
            "primer-client-token" => $this->primerToken,
            "primer-sdk-checkout-session-id" => $this->checkoutSessionId,
            "primer-sdk-client" => "WEB",
            "primer-sdk-version" => "v2.63.0",
            "x-api-version" => "2.4",
            "x-request-id" => $requestId
        ];

        $cardNumber = str_replace(" ", "", (string)($targetCard["number"] ?? ""));
        $cardCvv = (string)($targetCard["cvv"] ?? "");
        $cardMonth = str_pad((string)($targetCard["expirationMonth"] ?? ""), 2, '0', STR_PAD_LEFT);
        $cardYear = (string)($targetCard["expirationYear"] ?? "");
        $cardholderName = (string)($targetCard["cardholderName"] ?? "dsfg sdfg");

        $payload = [
            "paymentInstrument" => [
                "number" => $cardNumber,
                "cvv" => $cardCvv,
                "expirationMonth" => $cardMonth,
                "expirationYear" => $cardYear,
                "cardholderName" => $cardholderName
            ]
        ];

        $last4 = substr($cardNumber, -4);
        $this->logOutput("  " . CYAN . "URL:" . RESET . " {$url}\n");
        $this->logOutput("  " . CYAN . "Card Number:" . RESET . " **** **** **** {$last4}\n");
        $this->logOutput("  " . CYAN . "Exp:" . RESET . " {$cardMonth}/{$cardYear} | CVV: {$cardCvv}\n");

        try {
            $res = $this->executeRequest("POST", $url, $headers, $payload, 15);
            $this->logOutput("  " . CYAN . "Status:" . RESET . " {$res['status']}\n");
            $this->logOutput("  " . CYAN . "Response:" . RESET . " {$res['body']}\n");

            $resData = json_decode($res['body'], true);
            $this->lastTokenData = is_array($resData) ? $resData : null;

            if (in_array($res['status'], [200, 201], true)) {
                $paymentToken = $resData["token"] ?? null;
                $this->logOutput("  " . GREEN . "[+] Tokenized Successfully! Token: {$paymentToken}" . RESET . "\n");
                return $paymentToken;
            } else {
                $errMsg = $resData['description'] ?? $resData['message'] ?? "Card tokenization failed with status {$res['status']}";
                $this->lastError = $errMsg;
                $this->logOutput("  " . RED . "[-] Card tokenization failed with status: {$res['status']} ({$errMsg})" . RESET . "\n", 'error');
                return null;
            }
        } catch (\Throwable $e) {
            $this->lastError = "Step 2 Error: " . $e->getMessage();
            $this->logOutput("  " . RED . "[-] Step 2 Error: " . $e->getMessage() . RESET . "\n", 'error');
            return null;
        }
    }

    /**
     * Step 3: POST https://sdk.api.primer.io/payments (Execute payment)
     */
    public function step3ExecutePayment(string $paymentMethodToken): ?array {
        $this->logOutput("\n" . BOLD . BLUE . "------------------------------------------------------------" . RESET . "\n");
        $this->logOutput(BOLD . BLUE . "[Step 3] Executing Primer Payment Transaction" . RESET . "\n");
        $this->logOutput(BOLD . BLUE . "------------------------------------------------------------" . RESET . "\n");

        $url = "https://sdk.api.primer.io/payments";
        $requestId = generateUuidV4();

        $headers = [
            "accept" => "*/*",
            "content-type" => "application/json",
            "origin" => "https://sdk.primer.io",
            "referer" => "https://sdk.primer.io/",
            "user-agent" => $this->userAgent,
            "primer-client-token" => $this->primerToken,
            "primer-sdk-checkout-session-id" => $this->checkoutSessionId,
            "primer-sdk-client" => "WEB",
            "primer-sdk-version" => "v2.63.0",
            "x-api-version" => "2.4",
            "x-request-id" => $requestId
        ];

        $payload = [
            "paymentMethodToken" => $paymentMethodToken
        ];

        $this->logOutput("  " . CYAN . "URL:" . RESET . " {$url}\n");
        $this->logOutput("  " . CYAN . "Token:" . RESET . " {$paymentMethodToken}\n");

        try {
            $res = $this->executeRequest("POST", $url, $headers, $payload, 20);
            $this->logOutput("  " . CYAN . "Status:" . RESET . " {$res['status']}\n");
            $this->logOutput("  " . CYAN . "Response:" . RESET . " {$res['body']}\n");

            $resData = json_decode($res['body'], true) ?? [];

            if (in_array($res['status'], [200, 201], true)) {
                $paymentId = $resData["id"] ?? null;
                $status = $resData["status"] ?? null;
                $customerId = $resData["customerId"] ?? null;
                $outcome = $resData["checkoutOutcome"] ?? null;

                $this->logOutput("  " . GREEN . "[+] Payment Process Completed!" . RESET . "\n");
                $this->logOutput("  " . CYAN . "Payment ID (resourceId):" . RESET . " {$paymentId}\n");
                $this->logOutput("  " . CYAN . "Customer ID:" . RESET . " {$customerId}\n");
                $this->logOutput("  " . CYAN . "Outcome:" . RESET . " {$status} ({$outcome})\n");
                return $resData;
            } else {
                $errDesc = $resData['description'] ?? $resData['message'] ?? "Payment failed with status {$res['status']}";
                $this->lastError = $errDesc;
                $this->logOutput("  " . RED . "[-] Payment execution failed with status: {$res['status']} ({$errDesc})" . RESET . "\n", 'error');
                return !empty($resData) ? $resData : null;
            }
        } catch (\Throwable $e) {
            $this->lastError = "Step 3 Error: " . $e->getMessage();
            $this->logOutput("  " . RED . "[-] Step 3 Error: " . $e->getMessage() . RESET . "\n", 'error');
            return null;
        }
    }

    /**
     * Step 4: POST https://payments.printify.com/api/customers/{external_id}/cards
     */
    public function step4RegisterCustomerCard(string $paymentId, string $partnerCustomerId): ?array {
        $this->logOutput("\n" . BOLD . BLUE . "------------------------------------------------------------" . RESET . "\n");
        $this->logOutput(BOLD . BLUE . "[Step 4] Registering Card on Printify API" . RESET . "\n");
        $this->logOutput(BOLD . BLUE . "------------------------------------------------------------" . RESET . "\n");

        $url = "https://payments.printify.com/api/customers/{$this->externalId}/cards";

        $headers = [
            "accept" => "application/json, text/plain, */*",
            "content-type" => "application/json",
            "origin" => "https://printify.com",
            "referer" => "https://printify.com/",
            "user-agent" => $this->userAgent,
            "x-ab-test-token" => $this->abTestToken,
            "x-antifraud-session-id" => $this->antifraudSessionId
        ];
        if ($this->authorization) {
            $headers["authorization"] = $this->authorization;
        }

        $payload = [
            "partner" => "Primer",
            "cardAlias" => $this->cardAlias,
            "isDefault" => false,
            "resourceId" => $paymentId,
            "partnerCustomerId" => $partnerCustomerId
        ];

        $this->logOutput("  " . CYAN . "URL:" . RESET . " {$url}\n");
        $this->logOutput("  " . CYAN . "ResourceId:" . RESET . " {$paymentId}\n");
        $this->logOutput("  " . CYAN . "PartnerCustomerId:" . RESET . " {$partnerCustomerId}\n");

        try {
            $res = $this->executeRequest("POST", $url, $headers, $payload, 15);
            $this->logOutput("  " . CYAN . "Status:" . RESET . " {$res['status']}\n");
            $this->logOutput("  " . CYAN . "Response:" . RESET . " {$res['body']}\n");

            $resData = json_decode($res['body'], true);
            if (!is_array($resData)) {
                $resData = ["raw" => $res['body']];
            }

            if (in_array($res['status'], [200, 201], true)) {
                $this->logOutput("  " . GREEN . "[+] Card Registered Successfully!" . RESET . "\n");
            } elseif ($res['status'] === 400) {
                $errCode = $resData['error_code'] ?? '';
                $errDesc = $resData['error_description'] ?? '';
                $this->lastError = "Card registration rejected: {$errCode} - {$errDesc}";
                $this->logOutput("  " . YELLOW . "[!] Response Error: {$errCode} - {$errDesc}" . RESET . "\n", 'warning');
            } else {
                $this->lastError = "Card registration failed with status {$res['status']}";
                $this->logOutput("  " . RED . "[!] Status: {$res['status']}" . RESET . "\n", 'error');
            }

            return $resData;
        } catch (\Throwable $e) {
            $this->lastError = "Step 4 Error: " . $e->getMessage();
            $this->logOutput("  " . RED . "[-] Step 4 Error: " . $e->getMessage() . RESET . "\n", 'error');
            return null;
        }
    }

    /**
     * Run the full request pipeline and return structured result data.
     */
    public function runFlow(?array $card = null): array {
        $startTime = microtime(true);
        $targetCard = $card ?? $this->cardData;
        $cardNumber = str_replace(" ", "", (string)($targetCard["number"] ?? ""));
        $maskedCard = strlen($cardNumber) >= 10
            ? substr($cardNumber, 0, 6) . str_repeat("*", max(0, strlen($cardNumber) - 10)) . substr($cardNumber, -4)
            : $cardNumber;

        $response = [
            'success' => false,
            'status' => 'PENDING',
            'message' => '',
            'card' => [
                'number' => $maskedCard,
                'full_number' => $cardNumber,
                'expirationMonth' => $targetCard['expirationMonth'] ?? '',
                'expirationYear' => $targetCard['expirationYear'] ?? '',
                'cvv' => $targetCard['cvv'] ?? '',
                'cardholderName' => $targetCard['cardholderName'] ?? ''
            ],
            'tokens' => [
                'payment_method_token' => null,
                'payment_id' => null,
                'customer_id' => null
            ],
            'steps' => [
                'session_patched' => false,
                'tokenized' => false,
                'payment_executed' => false,
                'card_registered' => false
            ],
            'details' => [
                'token_data' => null,
                'payment' => null,
                'registration' => null
            ],
            'logs' => [],
            'time_taken' => '0s'
        ];

        $this->logOutput("\n" . BOLD . GREEN . "============================================================" . RESET . "\n");
        $this->logOutput(BOLD . GREEN . "              SENDING REQUESTS WITH ALL TOKENS              " . RESET . "\n");
        $this->logOutput(BOLD . GREEN . "============================================================" . RESET . "\n");

        // Step 1: Session patch
        $sessionOk = $this->step1PatchClientSession();
        $response['steps']['session_patched'] = $sessionOk;

        // Step 2: Tokenize card
        $token = $this->step2TokenizeCard($targetCard);
        $response['details']['token_data'] = $this->lastTokenData;

        if (!$token) {
            $this->logOutput("\n" . RED . "[-] Card Tokenization Failed. Ending Flow." . RESET . "\n", 'error');
            $response['status'] = 'TOKENIZATION_FAILED';
            $response['message'] = $this->lastError ?? 'Card Tokenization Failed';
            $response['time_taken'] = round(microtime(true) - $startTime, 2) . 's';
            $response['logs'] = $this->logs;
            return $response;
        }

        $response['steps']['tokenized'] = true;
        $response['tokens']['payment_method_token'] = $token;

        // Step 3: Execute payment
        $paymentResult = $this->step3ExecutePayment($token);
        if (!$paymentResult) {
            $this->logOutput("\n" . RED . "[-] Payment Execution Failed. Ending Flow." . RESET . "\n", 'error');
            $response['status'] = 'PAYMENT_FAILED';
            $response['message'] = $this->lastError ?? 'Payment Execution Failed';
            $response['time_taken'] = round(microtime(true) - $startTime, 2) . 's';
            $response['logs'] = $this->logs;
            return $response;
        }

        $response['steps']['payment_executed'] = true;
        $response['details']['payment'] = $paymentResult;

        $paymentId = $paymentResult["id"] ?? null;
        $customerId = $paymentResult["customerId"] ?? null;
        $paymentStatus = $paymentResult["status"] ?? null;
        $outcome = $paymentResult["checkoutOutcome"] ?? null;

        $response['tokens']['payment_id'] = $paymentId;
        $response['tokens']['customer_id'] = $customerId;

        // Step 4: Register card
        $regResult = null;
        if ($paymentId && $customerId) {
            $regResult = $this->step4RegisterCustomerCard($paymentId, $customerId);
            $response['details']['registration'] = $regResult;
            if ($regResult !== null) {
                $response['steps']['card_registered'] = true;
            }
        }

        $isSuccess = ($paymentStatus === 'AUTHORIZED' || $paymentStatus === 'SETTLING' || $paymentStatus === 'SETTLED' || $outcome === 'SUCCESS' || $paymentStatus === 'SUCCESS');

        if (!$isSuccess && $paymentStatus) {
            $response['status'] = $paymentStatus;
            $response['message'] = "Payment {$paymentStatus}" . ($outcome ? " ({$outcome})" : "");
        } else {
            $response['success'] = true;
            $response['status'] = $paymentStatus ?? 'COMPLETED';
            $response['message'] = "Payment Process Completed! Outcome: " . ($outcome ?? 'SUCCESS');
        }

        $response['time_taken'] = round(microtime(true) - $startTime, 2) . 's';
        $response['logs'] = $this->logs;

        $this->logOutput("\n" . BOLD . GREEN . "============================================================" . RESET . "\n");
        $this->logOutput(BOLD . GREEN . "                   REQUEST FLOW FINISHED                    " . RESET . "\n");
        $this->logOutput(BOLD . GREEN . "============================================================" . RESET . "\n\n");

        return $response;
    }
}

/**
 * Robust Card Input Parser
 * Supports piped strings (?cc=NUM|MM|YY|CVV, ?, :, ;, /) or separate GET/POST query parameters
 */
function parseCardInput(array $params): ?array {
    $raw = $params['cc'] ?? $params['card'] ?? $params['lista'] ?? $params['data'] ?? $params['c'] ?? null;
    
    if (empty($raw) && !empty($params['b64'])) {
        $raw = base64_decode($params['b64']);
    }

    $name = $params['name'] ?? $params['cardholderName'] ?? 'dsfg sdfg';

    if (!empty($raw) && is_string($raw)) {
        $raw = trim($raw);
        $delimiter = null;
        if (str_contains($raw, '|')) {
            $delimiter = '|';
        } elseif (str_contains($raw, ':')) {
            $delimiter = ':';
        } elseif (str_contains($raw, ';')) {
            $delimiter = ';';
        } elseif (str_contains($raw, '/')) {
            $delimiter = '/';
        }

        if ($delimiter !== null) {
            $parts = explode($delimiter, $raw);
        } else {
            $parts = preg_split('/\s+/', $raw);
        }

        if (count($parts) >= 4) {
            $num = preg_replace('/\D/', '', $parts[0]);
            $mm = str_pad(preg_replace('/\D/', '', $parts[1]), 2, '0', STR_PAD_LEFT);
            $yy = preg_replace('/\D/', '', $parts[2]);
            if (strlen($yy) === 2) {
                $yy = '20' . $yy;
            }
            $cvv = preg_replace('/\D/', '', $parts[3]);

            if (strlen($num) >= 12 && strlen($mm) === 2 && strlen($yy) === 4 && strlen($cvv) >= 3) {
                return [
                    'number' => $num,
                    'expirationMonth' => $mm,
                    'expirationYear' => $yy,
                    'cvv' => $cvv,
                    'cardholderName' => $name
                ];
            }
        }
    }

    // Check individual parameters
    $num = $params['number'] ?? $params['num'] ?? $params['card_number'] ?? null;
    $mm = $params['month'] ?? $params['mm'] ?? $params['exp_month'] ?? null;
    $yy = $params['year'] ?? $params['yy'] ?? $params['exp_year'] ?? null;
    $cvv = $params['cvv'] ?? $params['cvc'] ?? $params['security_code'] ?? null;

    if ($num && $mm && $yy && $cvv) {
        $num = preg_replace('/\D/', '', (string)$num);
        $mm = str_pad(preg_replace('/\D/', '', (string)$mm), 2, '0', STR_PAD_LEFT);
        $yy = preg_replace('/\D/', '', (string)$yy);
        if (strlen($yy) === 2) {
            $yy = '20' . $yy;
        }
        $cvv = preg_replace('/\D/', '', (string)$cvv);

        if (strlen($num) >= 12 && strlen($mm) === 2 && strlen($yy) === 4 && strlen($cvv) >= 3) {
            return [
                'number' => $num,
                'expirationMonth' => $mm,
                'expirationYear' => $yy,
                'cvv' => $cvv,
                'cardholderName' => $name
            ];
        }
    }

    return null;
}

/**
 * Output response in requested format (JSON or Text) and flush buffer
 */
function outputApiResponse(array $data, string $format = 'json'): void {
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'text' || $format === 'raw') {
        header('Content-Type: text/plain; charset=utf-8');
        $status = $data['status'] ?? ($data['success'] ? 'APPROVED' : 'DECLINED');
        $msg = $data['message'] ?? '';
        $cardNum = $data['card']['number'] ?? '';
        echo "{$status} | {$cardNum} | {$msg}\n";
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/**
 * Handle Web Browser / HTTP API Request (cPanel / Apache / LiteSpeed / Nginx)
 */
function handleApiRequest(): void {
    // Enable CORS for external access / frontend fetch
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    // Handle preflight OPTIONS request
    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // Merge GET, POST and php://input JSON body
    $params = $_GET ?? [];
    if (!empty($_POST)) {
        $params = array_merge($params, $_POST);
    }
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonInput = json_decode($rawInput, true);
        if (is_array($jsonInput)) {
            $params = array_merge($params, $jsonInput);
        }
    }

    $format = strtolower($params['format'] ?? 'json');
    $proxy = !empty($params['proxy']) ? trim($params['proxy']) : null;

    // Check if HAR file exists (cPanel may only have manik.php without main.har)
    $harPath = null;
    if (!empty($params['har'])) {
        $candidate = __DIR__ . DIRECTORY_SEPARATOR . $params['har'];
        if (file_exists($candidate)) {
            $harPath = $candidate;
        }
    } else {
        $defaultHar = __DIR__ . DIRECTORY_SEPARATOR . 'main.har';
        if (file_exists($defaultHar)) {
            $harPath = $defaultHar;
        }
    }

    $sessionData = loadSessionConfig($harPath, true);

    if (!empty($params['token'])) {
        $sessionData['primer_client_token'] = $params['token'];
    }
    if (!empty($params['auth'])) {
        $sessionData['authorization'] = $params['auth'];
    }
    if (!empty($params['external_id'])) {
        $sessionData['external_id'] = $params['external_id'];
    }

    // Parse card parameters
    $hasCardParam = isset($params['cc']) || isset($params['card']) || isset($params['lista']) || isset($params['data']) || isset($params['c']) || isset($params['num']) || isset($params['number']);
    
    $card = parseCardInput($params);

    if ($hasCardParam && $card === null) {
        // User attempted to pass card data, but format was invalid
        $errorResponse = [
            'success' => false,
            'status' => 'INVALID_CARD_FORMAT',
            'message' => 'Invalid card format provided. Expected format: ?cc=NUMBER|MM|YYYY|CVV or ?num=...&mm=...&yy=...&cvv=...',
            'example' => 'manik.php?cc=5154620036376364|02|2031|666'
        ];
        outputApiResponse($errorResponse, $format);
        return;
    }

    $usingDefault = false;
    if ($card === null) {
        // Default card fallback when opened directly in browser without parameters
        $usingDefault = true;
        $card = $sessionData['card_data'] ?? DEFAULT_CARD;
    }

    $requester = new PrintifyPrimerRequester($sessionData, $params['auth'] ?? null, true, $proxy);
    $result = $requester->runFlow($card);

    if ($usingDefault) {
        $result['api_info'] = 'Default test card executed. To check a specific card, pass it via URL parameters.';
        $result['usage_examples'] = [
            'single_pipe_param' => 'manik.php?cc=5154620036376364|02|2031|666',
            'separate_params'   => 'manik.php?num=5154620036376364&mm=02&yy=2031&cvv=666&name=Test+User',
            'text_format'       => 'manik.php?cc=5154620036376364|02|2031|666&format=text'
        ];
    }

    outputApiResponse($result, $format);
}

/**
 * Parse CLI arguments
 */
function parseArguments(array $argv): array {
    $args = [
        'har' => 'main.har',
        'card' => null,
        'name' => 'dsfg sdfg',
        'auth' => null,
        'token' => null,
        'proxy' => null,
        'api' => false,
        'help' => false
    ];

    $count = count($argv);
    for ($i = 1; $i < $count; $i++) {
        $arg = $argv[$i];
        if ($arg === '--help' || $arg === '-h') {
            $args['help'] = true;
        } elseif ($arg === '--api' || $arg === '-a') {
            $args['api'] = true;
        } elseif ($arg === '--har' && isset($argv[$i + 1])) {
            $args['har'] = $argv[++$i];
        } elseif ($arg === '--proxy' && isset($argv[$i + 1])) {
            $args['proxy'] = $argv[++$i];
        } elseif ($arg === '--name' && isset($argv[$i + 1])) {
            $args['name'] = $argv[++$i];
        } elseif ($arg === '--auth' && isset($argv[$i + 1])) {
            $args['auth'] = $argv[++$i];
        } elseif ($arg === '--token' && isset($argv[$i + 1])) {
            $args['token'] = $argv[++$i];
        } elseif ($arg === '--card') {
            $cardParts = [];
            while (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '--') && count($cardParts) < 4) {
                $cardParts[] = $argv[++$i];
            }
            if (count($cardParts) === 4) {
                $args['card'] = $cardParts;
            }
        }
    }

    return $args;
}

/**
 * Main CLI Execution
 */
function main(array $argv): void {
    $args = parseArguments($argv);

    if ($args['help']) {
        echo "Usage: php manik.php [options]\n\n";
        echo "Options:\n";
        echo "  --har <path>                    Path to HAR file (default: main.har)\n";
        echo "  --card <NUM> <MM> <YYYY> <CVV>  Custom card details\n";
        echo "  --name <name>                   Cardholder name (default: dsfg sdfg)\n";
        echo "  --auth <token>                  Printify Authorization token (optional)\n";
        echo "  --token <token>                 Override Primer Client Token (optional)\n";
        echo "  --proxy <ip:port>               Use HTTP/SOCKS proxy\n";
        echo "  --api                           Output pure JSON response\n";
        echo "  --help, -h                      Show this help message\n";
        return;
    }

    $harPath = __DIR__ . DIRECTORY_SEPARATOR . $args['har'];
    $sessionData = loadSessionConfig(file_exists($harPath) ? $harPath : null, $args['api']);

    if ($args['token']) {
        $sessionData['primer_client_token'] = $args['token'];
    }

    $card = null;
    if ($args['card']) {
        $card = [
            "number" => $args['card'][0],
            "expirationMonth" => $args['card'][1],
            "expirationYear" => $args['card'][2],
            "cvv" => $args['card'][3],
            "cardholderName" => $args['name']
        ];
    }

    if ($args['api']) {
        $requester = new PrintifyPrimerRequester($sessionData, $args['auth'], true, $args['proxy']);
        $result = $requester->runFlow($card);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        return;
    }

    $primerToken = $sessionData['primer_client_token'] ?? '';
    $auth = $sessionData['authorization'] ?? '';

    echo GREEN . "[+] Loaded Default Tokens & Parameters:" . RESET . "\n";
    echo "    - Primer Token:     " . substr($primerToken, 0, 40) . "... (len " . strlen($primerToken) . ")\n";
    echo "    - Checkout Session: " . ($sessionData['checkout_session_id'] ?? '') . "\n";
    echo "    - External ID:      " . ($sessionData['external_id'] ?? '') . "\n";
    echo "    - AB Test Token:    " . ($sessionData['ab_test_token'] ?? '') . "\n";
    echo "    - Antifraud Token:  " . ($sessionData['antifraud_session_id'] ?? '') . "\n";
    echo "    - Authorization:    " . substr($auth, 0, 40) . "... (len " . strlen($auth) . ")\n";

    $requester = new PrintifyPrimerRequester($sessionData, $args['auth'], false, $args['proxy']);
    $requester->runFlow($card);
}

// Entry Point Router: Works on cPanel (Apache, LiteSpeed, Nginx, PHP-FPM) and CLI
if (php_sapi_name() === 'cli') {
    if (isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
        main($argv);
    }
} else {
    handleApiRequest();
}
