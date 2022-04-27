# stripe_easy_dropin
An easy guide to use Stripe subscription checkout, upgrade/downgrade, cancel etc.

This lib has been created based on stripe-php-lib-7.119.0.

#### Setup:

1. Download the zip and extract or directly clone it to your working path.
2. Put the files on your frontend server to avoid exposing the backend server urls to frontend users. (If you need so)
3. Change the content of constants.php to have the KEYS, URLs as per your enviornments.
4. In stripe create all of your products and reference them with different keys in the PRICE_IDS property of constants.php file. This key should be used for all communication with api instead of the actual ids.
5. Create a webhook in stripe with 3 events named `customer.created`, `subscription.created`, `subscription.updated` for easy access and assign the webhook url to the absolute url of /stripe_wh.php file in your environment.
6. Webhook will call your backend apis for above events with relevant data. You need to provide the backend urls to be called in the constants.php file for properties `CALLBACK_CUSTOMER_CREATION_URL` and `CALLBACK_SUBSCRIPTION_URL` with extra headers `EXTRA_HEADERS` if any needed.
7. All the checkout session will redirect the user to `SUCCESS_URL` or `CANCEL_URL` in your frontend. So make sure to give proper url over there.
8. Set whether promotion `ALLOW_PROMOTION` should be allowed during checkout or not. By Default it is set to true.
9. Set whether subscriptions should be cancelled immediately or at the end of current period with property `CANCEL_SUBSCRIPTION_AT_PERIOD_END`. By default it is set to true.
10. Great, your web app is ready to handle easy stripe operations like adding a customer, creating / updating subscriptions, upgrade/downgrade, cancel subscription etc.
11. All the responses from the api calls are json encoded and in the format as:
 `{status: true/false, code: 200/400, data: <info>}`


## API Reference

### Create checkout session

```http
  POST /
```

| Parameter | Type     | Description                |
| :-------- | :------- | :------------------------- |
| `email`   | `string` | **Required**.email address |
| `plan`    | `string` | **Required**.any of the subscription plan key from `PRICE_IDS` |
| `custId`  | `string` | **Optional**.Previous stripe customer id to avoid duplicates |

#### Response

    {status: true, code: 200, data: '<session-id to be consumed in frontend for `redirectToCheckout`>'}

### Cancel subscription

```http
  DELETE /
```
We are not terminating the subscription immediately here. Instead we are setting it to cancel_ar_period_end as default.

| Parameter | Type     | Description                |
| :-------- | :------- | :------------------------- |
| `subId`   | `string` | **Required**.subscription id to cancel the subscription |

#### Response

    {status: true, code: 200, data: 'set to cancel at period end / immediately'}


### Check invoice with proration for upgrade/downgrade.

```http
  GET /?res=invoice
```

| Parameter | Type     | Description                |
| :-------- | :------- | :------------------------- |
| `subId`   | `string` | **Required**.Existing subscription id |
| `plan`    | `string` | **Required**.New plan to upgrade/downgrade. It should be any of the subscription plan key from `PRICE_IDS` |

#### Response

    {status: true, code: 200, data: <Stripe Invoice Data>}


### Upgrade/Downgrade subscription.

```http
  PATCH /?res=sub
```

| Parameter | Type     | Description                |
| :-------- | :------- | :------------------------- |
| `subId`   | `string` | **Required**.Existing subscription id |
| `plan`    | `string` | **Required**.New plan to upgrade/downgrade. It should be any of the subscription plan key from `PRICE_IDS` |
| `proration_date`  | `string` | **Optional**.Proration Date for the effect to take place. It should be `subscription_proration_date` from the invoice api response. |

#### Response

    {status: true, code: 200, data: <Complete Subscription Data after the Change>}

## Webhook Reference

Webhoook calls 2 different urls named with properties `CALLBACK_CUSTOMER_CREATION_URL` and `CALLBACK_SUBSCRIPTION_URL`.

### CALLBACK_CUSTOMER_CREATION_URL params

    {custId: 'customer id', email: 'email provided'}

### CALLBACK_SUBSCRIPTION_URL params

    {
      custId: 'customer id', 
      subId: 'subscription id', 
      status: 'subscription status from stripe', 
      is_trial: true/false, 
      plan: 'plan key from the PRICE_IDS', 
      auto_renew: 'on/off', 
      started_on: 'Y-m-d H:i:s', 
      expiring_on: 'Y-m-d H:i:s'
    }

## Authors

- [@shivamkjjw](https://www.github.com/shivamkjjw)
