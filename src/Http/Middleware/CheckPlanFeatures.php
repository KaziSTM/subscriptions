<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use KaziSTM\Subscriptions\Models\Subscription;
use KaziSTM\Subscriptions\Traits\HasPlanSubscriptions;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckPlanFeatures
{
    /**
     * Handle an incoming request.
     * Aborts if the relevant subscribable entity's active plan doesn't include ALL specified features.
     * Features are checked by their Limitation slugs.
     *
     * @param  string  ...$limitationSlugs  Slugs of the required Limitations.
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$limitationSlugs): Response
    {
        $subscribable = $this->findSubscribable($request);

        if (
            ! $subscribable ||
            ! in_array(HasPlanSubscriptions::class, class_uses_recursive($subscribable), true)
        ) {
            Log::warning('CheckPlanFeatures Middleware: Could not find a valid subscribable entity.');
            abort(403, 'No valid subscribable entity found for feature check.');
        }

        $subscriptionName = 'main';
        /** @var Subscription|null $subscription */
        $subscription = $subscribable->planSubscription($subscriptionName);

        if (! $subscription || ! $subscription->active()) {
            abort(403, 'Active subscription required to check features.');
        }

        if (empty($limitationSlugs)) {
            return $next($request);
        }

        $subscription->loadMissing('plan.limitations');
        $plan = $subscription->plan;

        if (! $plan) {
            Log::error("CheckPlanFeatures Middleware: Subscription ID {$subscription->id} has no associated Plan.");
            abort(403, 'Subscription plan not found.');
        }

        $planLimitationSlugs = $plan->relationLoaded('limitations')
            ? $plan->limitations->pluck('slug')->unique()->toArray()
            : [];

        $missingSlugs = array_diff($limitationSlugs, $planLimitationSlugs);

        if (! empty($missingSlugs)) {
            abort(403, 'Your current plan does not include required feature(s): ' . implode(', ', $missingSlugs));
        }

        return $next($request);
    }

    /**
     * Attempt to find the relevant subscribable model instance from the request context.
     * Checks Route Parameters, Filament Tenant, and falls back to Auth User.
     * (Identical helper method as in CheckSubscription)
     */
    protected function findSubscribable(Request $request): ?Model
    {
        // 1. Check Route Parameters
        if ($request->route()) {
            foreach ($request->route()->parameters() as $parameter) {
                if (
                    $parameter instanceof Model &&
                    in_array(HasPlanSubscriptions::class, class_uses_recursive($parameter), true)
                ) {
                    return $parameter;
                }
            }
        }

        // 2. Check Filament Tenant
        if (class_exists(Filament::class) && method_exists(Filament::class, 'getTenant')) {
            try {
                $tenant = Filament::getTenant();
                if (
                    $tenant instanceof Model &&
                    in_array(HasPlanSubscriptions::class, class_uses_recursive($tenant), true)
                ) {
                    return $tenant;
                }
            } catch (Throwable $e) {
            }
        }

        // 3. Check Authenticated User
        $user = Auth::user();
        if (
            $user instanceof Model &&
            in_array(HasPlanSubscriptions::class, class_uses_recursive($user), true)
        ) {
            return $user;
        }

        Log::warning('CheckPlanFeatures Middleware: Could not automatically determine subscribable entity.');

        return null;
    }
}
