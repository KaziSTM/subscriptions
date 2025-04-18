<?php

declare(strict_types=1);

use KaziSTM\Subscriptions\Models\Feature;
use KaziSTM\Subscriptions\Models\Limitation;
use KaziSTM\Subscriptions\Models\Plan;
use KaziSTM\Subscriptions\Models\Subscription;
use KaziSTM\Subscriptions\Models\SubscriptionUsage;

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Tables
    |--------------------------------------------------------------------------
    |
    |
    */

    'tables' => [
        'plans' => 'plans',
        'limitations' => 'limitations',
        'features' => 'features',
        'subscriptions' => 'subscriptions',
        'subscription_usage' => 'subscription_usage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Models
    |--------------------------------------------------------------------------
    |
    | Models used to manage subscriptions. You can replace to use your own models,
    | but make sure that you have the same functionalities or that your models
    | extend from each model that you are going to replace.
    |
    */

    'models' => [
        'plan' => Plan::class,
        'limitation' => Limitation::class,
        'feature' => Feature::class,
        'subscription' => Subscription::class,
        'subscription_usage' => SubscriptionUsage::class,
    ],

];
