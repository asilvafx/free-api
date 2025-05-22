<?php

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

    public function stripe(): void
    {
        if ($this->f3->get('SITE.stripe_status') !== 1) {
            echo json_encode(['error' => 'Stripe not enabled.']);
            return;
        }

        $stripe_sk = $this->f3->get('SITE.stripe_sk');
        require_once ROOT . 'lib/vendor/stripe-php/init.php';

        header('Content-Type: application/json');

        $body = json_decode($this->f3->get('BODY'), true);
        error_log('Payment request: ' . json_encode($body));

        if (!$body) {
            echo json_encode(['error' => 'Invalid request body']);
            return;
        }

        $amount = isset($body['amount']) ? intval($body['amount']) : 0;
        $currency = $body['currency'] ?? 'usd';
        $email = $body['email'] ?? '';
        $useAutomaticMethods = $body['automatic_payment_methods'] ?? false;

        if ($amount <= 0) {
            echo json_encode(['error' => 'Invalid amount']);
            return;
        }

        if (empty($email)) {
            echo json_encode(['error' => 'Email is required']);
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

        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API Error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            error_log('Server Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
