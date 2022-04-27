<?php

require_once('./stripe-php-lib/init.php');
require_once('./constants.php');
require_once('./utils.php');

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
  $event = \Stripe\Webhook::constructEvent(
    $payload,
    $sig_header,
    WEBHOOK_KEY
  );
} catch (\UnexpectedValueException $e) {
  // Invalid payload
  http_response_code(400);
  exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
  // Invalid signature
  http_response_code(400);
  exit();
}

// Handle the event
switch ($event->type) {
  case 'customer.created':
    $customer = $event->data->object;
    handleCustomerCreation($customer);
    break;
  case 'customer.subscription.created':
  case 'customer.subscription.updated':
    $subscription = $event->data->object;
    handleSubscriptionInfo($subscription);
    break;
  default:
    echo 'Received unknown event type ' . $event->type;
}

http_response_code(200);
exit();

function handleCustomerCreation(\Stripe\Customer $customer)
{
  if ($customer) {
    callToServer(CALLBACK_CUSTOMER_CREATION_URL, ['custId' => $customer->id, 'email' => $customer->email]);
  }
}

function handleSubscriptionInfo(\Stripe\Subscription $subscription)
{
  if ($subscription) {
    $subItem = \Stripe\SubscriptionItem::constructFrom($subscription->items->data[0]);
    $params = [
      'subId' => $subscription->id,
      'custId' => $subscription->customer,
      'status' => $subscription->status,
      'is_trial' => $subscription->status === 'trailing',
      'plan' => array_search($subItem->price->id, PRICE_IDS),
      'auto_renew' => $subscription->cancel_at_period_end ? 'off' : 'on',
      'started_on' => date('Y-m-d H:i:s', $subscription->start_date),
      'expiring_on' => date('Y-m-d H:i:s', $subscription->current_period_end),
    ];
    callToServer(CALLBACK_SUBSCRIPTION_URL, $params);
  }
}

/**
 * Makes final call to the server with specified arguments.
 */
function callToServer($url, $params, $method = 'POST')
{
  global $extraHeaders;
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_POSTFIELDS => json_encode($params),
    CURLOPT_HTTPHEADER => array_merge(
      [
        "cache-control: no-cache",
        "content-type: application/json"
      ],
      EXTRA_HEADERS
    ),
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);

  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
    return $response;
  }
  return NULL;
}
