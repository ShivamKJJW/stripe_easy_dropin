<?php

require_once('./stripe-php-lib/init.php');
require_once('./constants.php');
require_once('./utils.php');

$verb = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

if ($_SERVER["CONTENT_TYPE"] === 'application/json') {
    $_REQUEST = json_decode(file_get_contents('php://input'), true);
}

$stripe = new \Stripe\StripeClient(STRIPE_KEY);

switch ($verb) {
    case 'GET':
        if ($_REQUEST['res'] === 'invoice') {
            if (!isset($_REQUEST['subId']) || $_REQUEST['subId'] == '') {
                returnJSON('Subscription id is required', false);
            } else if (!isset($_REQUEST['plan']) || $_REQUEST['plan'] == '' || !isset(PRICE_IDS[$_REQUEST['plan']])) {
                returnJSON('New plan is required', false);
            } else {
                //Check invoice proration dummy
                $subscription = $stripe->subscriptions->retrieve($_REQUEST['subId']);

                // See what the next invoice would look like with a price switch
                // and proration set:
                $items = [[
                    'id' => $subscription->items->data[0]->id,
                    'price' => PRICE_IDS[$_REQUEST['plan']], # Switch to new price
                ]];

                $proration_date = time();

                $invoice = $stripe->invoices->upcoming([
                    'customer' => $subscription->customer,
                    'subscription' => $_REQUEST['subId'],
                    'subscription_items' => $items,
                    'subscription_proration_date' => $proration_date,
                ]);
                returnJSON($invoice);
            }
        }
        break;
    case 'POST':
        // Creating the session.
        $email = $_REQUEST['email'];
        $plan = $_REQUEST['plan'];
        if (!isset($email) || $email == '') {
            returnJSON('Email is required', false);
        } else if (!isset($plan) || $plan == '' || !isset(PRICE_IDS[$plan])) {
            returnJSON('Plan is required', false);
        } else {
            $customerId = $_REQUEST['custId'];
            if (!isset($customerId) || $customerId == '') {
                $customer = $stripe->customers->create(['email' => $email]);
                $customerId = $customer->id;
            }
            $sessionParams = [
                'customer' => $customerId,
                'mode' => 'subscription',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => PRICE_IDS[$plan],
                    'quantity' => 1,
                ]],
                'success_url' => SUCCESS_URL,
                'cancel_url' => CANCEL_URL,
            ];
            if (ALLOW_PROMOTION) {
                $sessionParams['allow_promotion_codes'] = true;
            }
            $session = $stripe->checkout->sessions->create($sessionParams);
            returnJSON($session->id);
        }
        break;
    case 'PATCH':
        if ($_REQUEST['res'] === 'sub') {
            if (!isset($_REQUEST['subId']) || $_REQUEST['subId'] == '') {
                returnJSON('Subscription id is required', false);
            } else if (!isset($_REQUEST['plan']) || $_REQUEST['plan'] == '' || !isset(PRICE_IDS[$_REQUEST['plan']])) {
                returnJSON('New plan is required', false);
            } else if (!isset($_REQUEST['proration_date']) || $_REQUEST['proration_date'] == '') {
                returnJSON('Proration date is required', false);
            } else {
                $subscription = $stripe->subscriptions->retrieve($_REQUEST['subId']);
                $stripe->subscriptions->update($_REQUEST['subId'], [
                    'items' => [
                        [
                            'id' => $subscription->items->data[0]->id,
                            'price' => PRICE_IDS[$_REQUEST['plan']],
                        ],
                    ],
                    'proration_date' => isset($_REQUEST['proration_date']) ? $_REQUEST['proration_date'] : time(),
                ]);

                returnJSON($subscription);
            }
        }
        break;
    case 'DELETE':
        //Cancel the subscription
        $subscriptionId = $_REQUEST['subId'];
        if (!isset($subscriptionId) || $subscriptionId == '') {
            returnJSON('SubscriptionId is required', false);
        } else {
            $stripe->subscriptions->update($subscriptionId, ['cancel_at_period_end' => CANCEL_SUBSCRIPTION_AT_PERIOD_END]);
            returnJSON(CANCEL_SUBSCRIPTION_AT_PERIOD_END ? 'set to cancel at period end.' : 'set to cancel immediately');
        }
        break;

    default:
        returnJSON('not supported', false);
        break;
}
