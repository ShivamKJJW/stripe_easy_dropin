<?php

/**
 * Note: 1. In stripe set the webhook to the stripe_wh.php file.
 *       2. Before checkout call the index.php file with POST method for checkout session id.
 *       3. To cancel the subscription just call the index.php file with DELETE method with subId.
 */

// Stripe secret key
const STRIPE_KEY = '<YOUR_STRIPE_PRIVATE_KEY>';

// Create one webhook with permissions [customer.created, customer.subscription.created and customer.subscription.updated]
const WEBHOOK_KEY = '<WEBHOOK_SECRET_KEY>';

// If promocode is required during checkout then set it to true.
const ALLOW_PROMOTION = true;

// Cancel the subscription at period end.
const CANCEL_SUBSCRIPTION_AT_PERIOD_END = true;

// Checkout callback url for frontend to handle the user experience.
const SUCCESS_URL = '<SUCCESS_CALLBACK_URL>';
const CANCEL_URL = '<CANCEL_CALLBACK_URL>';

// To inform actual server about user handling regarding stripe info.
const CALLBACK_CUSTOMER_CREATION_URL = '<API_URL_TO_HANDLE_CUSTOMER_CREATION>';
const CALLBACK_SUBSCRIPTION_URL = '<API_URL_TO_HANDLE_SUBSCRIPTION_CHANGES>';
const EXTRA_HEADERS = ["<ANY_EXTRA_HEADER_TO_BE_USED_FOR_API_CALLS>"];

// Add all plan prices here. Keys are being used to refer the frontend and values are actual price key from the Stripe.
const PRICE_IDS = ['monthly' => '<MONTHLY_PLAN_ID>', 'annualy' => '<YEARLY_PLAN_ID>'];
