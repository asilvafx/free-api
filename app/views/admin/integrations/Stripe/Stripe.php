<?php
require_once INTEGRATIONS . 'Stripe/lib/stripe-php/init.php';

class Stripe
{
    private $db;
    private $f3;
    private $client;
    private $secret;

    public function __construct()
    {
        global $f3;
        global $db;
        $this->f3 = $f3;
        $this->db = $db;
        $this->client = "Ov23liYhob2PacpCKe6C";
        $this->secret = "fd51dd005f972850200893a59ef063eee8c8301f";
        $this->DbCheck();
    }

    function DbCheck(): void
    {
        $query = new Query;
        // Check and create 'stripe' table
        if (!$query->load('stripe')) {
            $created = $query->addCollection($this->db, 'stripe', '
              id INTEGER PRIMARY KEY AUTOINCREMENT,
            stripe_status INTEGER DEFAULT 0,
            stripe_sk TEXT DEFAULT "",
            stripe_pk TEXT DEFAULT ""
        ');

        // Insert default values after table creation
        if ($created) {
            $this->db->exec("INSERT INTO stripe (stripe_status, stripe_sk, stripe_pk) VALUES (0, '', '')");
        }
        }

        // Also ensure 'payments' table exists
        if (!$query->load('payments')) {
            $query->addCollection($this->db, 'payments', '
            amount INTEGER,
            currency TEXT,
            customer TEXT,
            intent TEXT,
            created_at INTEGER
        ');
        }
    }

    function Init(): void
    {
        $response = new Response;
        $query = new Query;
        $stripe_load = $query->load('stripe');
        $stripe = $stripe_load[0];

        if ($stripe['stripe_status'] !== 1) {
            $response->json('error', 'Stripe not enabled.');
            return;
        }

        $stripe_sk = $stripe['stripe_sk'];

        header('Content-Type: application/json');

        $body = json_decode($this->f3->get('BODY'), true);
        error_log('Payment request: ' . json_encode($body));

        if (!$body) {
            $response->json('error', 'Invalid request body');
            return;
        }

        $amount = isset($body['amount']) ? intval($body['amount']) : 0;
        $currency = $body['currency'] ?? 'usd';
        $email = $body['email'] ?? '';
        $useAutomaticMethods = $body['automatic_payment_methods'] ?? false;

        if ($amount <= 0) {
            $response->json('error', 'Invalid amount');
            return;
        }

        if (empty($email)) {
            $response->json('error', 'Email is required');
            return;
        }

        try {
            $stripe = new \Stripe\StripeClient($stripe_sk);

            $customer = $stripe->customers->create([
                'email' => $email,
                'description' => 'Customer for ' . $email,
            ]);

            $paymentIntentParams = [
                'amount' => $amount,
                'currency' => $currency,
                'customer' => $customer->id,
                'metadata' => [
                    'customer_email' => $email
                ],
            ];

            if ($useAutomaticMethods) {
                $paymentIntentParams['automatic_payment_methods'] = ['enabled' => true];
            } else {
                $paymentIntentParams['payment_method_types'] = ['card'];
            }

            $paymentIntent = $stripe->paymentIntents->create($paymentIntentParams);
            error_log('Created PaymentIntent: ' . $paymentIntent->id);

            echo json_encode([
                'client_secret' => $paymentIntent->client_secret,
                'customer_id' => $customer->id,
                'payment_intent_id' => $paymentIntent->id
            ]);

            $data = new DB\SQL\Mapper($this->db, 'payments');
            $data->amount = $amount;
            $data->currency = $currency;
            $data->customer = $customer->id;
            $data->intent = $paymentIntent->id;
            $data->created_at = time();
            $data->save();

        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API Error: ' . $e->getMessage());
            http_response_code(400);
            $response->json('error', $e->getMessage());
        } catch (\Exception $e) {
            error_log('Server Error: ' . $e->getMessage());
            http_response_code(500);
            $response->json('error', $e->getMessage());
        }
    }

}

