<?php

require_once ROOT . 'lib/vendor/stripe-php/init.php';
require_once ROOT . 'lib/vendor/paypal-php/autoload.php';
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

class Pay
{
    private $db;
    private $f3;

    public function __construct()
    {
        global $f3;
        global $db;
        $this->f3 = $f3;
        $this->db = $db;
    }

    public function paypal(): void
    {
        $response = new Response;
        if ($this->f3->get('SITE.paypal_status') !== 1) {
            $response->json('error', 'Paypal not enabled.');
            return;
        }

        // Replace these values by entering your own ClientId and Secret by visiting https://developer.paypal.com/webapps/developer/applications/myapps
        $clientId = $this->f3->get('SITE.paypal_pk');
        $clientSecret = $this->f3->get('SITE.paypal_sk');

        header('Content-Type: application/json');

        $body = json_decode($this->f3->get('BODY'), true);
        error_log('Payment request: ' . json_encode($body));

        if (!$body) {
            $response->json('error', 'Invalid request body');
            return;
        }

    }

    public function stripe(): void
    {
        $response = new Response;

        if ($this->f3->get('SITE.stripe_status') !== 1) {
            $response->json('error', 'Stripe not enabled.');
            return;
        }

        $stripe_sk = $this->f3->get('SITE.stripe_sk');

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
