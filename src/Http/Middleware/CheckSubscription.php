<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model; // Import Eloquent Model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Still needed for fallback
use Illuminate\Support\Facades\Log; // Optional: for debugging which entity was found
use KaziSTM\Subscriptions\Models\Subscription;
use KaziSTM\Subscriptions\Traits\HasPlanSubscriptions; // Import the trait
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     * Aborts if the relevant subscribable entity cannot be found or does not have an active subscription.
     *
     * @param  string  $subscriptionName  The name identifier of the subscription (default: 'main').
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $subscriptionName = 'main'): Response
    {
        $subscribable = $this->findSubscribable($request);

        if (
            ! $subscribable ||
            ! in_array(HasPlanSubscriptions::class, class_uses_recursive($subscribable), true)
        ) {
            Log::warning('CheckSubscription Middleware: Could not find a valid subscribable entity using HasPlanSubscriptions trait.');
            abort(403, 'No valid subscribable entity found for subscription check.');
        }

        /** @var Subscription|null $subscription */
        $subscription = $subscribable->planSubscription($subscriptionName);

        if (! $subscription || ! $subscription->active()) {
            abort(403, 'Active subscription required.');
        }

        return $next($request);
    }

    /**
     * Attempt to find the relevant subscribable model instance from the request context.
     * Checks Route Parameters, Filament Tenant, and falls back to Auth User.
     */
    protected function findSubscribable(Request $request): ?Model
    {
        if ($request->route()) {
            foreach ($request->route()->parameters() as $parameter) {
                if (
                    $parameter instanceof Model &&
                    in_array(HasPlanSubscriptions::class, class_uses_recursive($parameter), true)
                ) {
                    Log::debug('CheckSubscription: Found subscribable via route parameter.', ['class' => get_class($parameter), 'id' => $parameter->getKey()]);

                    return $parameter;
                }
            }
        }

        // 2. Check Filament Tenant (if Filament is installed/available and configured for tenancy)
        if (class_exists(Filament::class) && method_exists(Filament::class, 'getTenant')) {
            try {
                $tenant = Filament::getTenant();
                if (
                    $tenant instanceof Model &&
                    in_array(HasPlanSubscriptions::class, class_uses_recursive($tenant), true)
                ) {
                    Log::debug('CheckSubscription: Found subscribable via Filament tenant.', ['class' => get_class($tenant), 'id' => $tenant->getKey()]);

                    return $tenant;
                }
            } catch (Throwable $e) {
                Log::debug('CheckSubscription: Error checking Filament tenant.', ['error' => $e->getMessage()]);
            }
        }

        $user = Auth::user();
        if (
            $user instanceof Model &&
            in_array(HasPlanSubscriptions::class, class_uses_recursive($user), true)
        ) {
            Log::debug('CheckSubscription: Found subscribable via Auth::user().', ['class' => get_class($user), 'id' => $user->getKey()]);

            return $user;
        }

        Log::warning('CheckSubscription Middleware: Could not automatically determine subscribable entity.');

        return null;
    }
}
