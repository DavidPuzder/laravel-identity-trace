<?php

namespace DavidPuzder\LaravelIdentityTrace;

use DavidPuzder\LaravelIdentityTrace\Services\IdentityTraceAgentService;
use DavidPuzder\LaravelIdentityTrace\Services\IdentityTraceDeviceService;
use DavidPuzder\LaravelIdentityTrace\Services\IdentityTraceLoginService;
use DavidPuzder\LaravelIdentityTrace\Subscribers\IdentityTraceSubscriber;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\AgentServiceProvider;

class IdentityTraceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            if (false === class_exists('CreateIdentityTraceLoginsTable') && false === class_exists('CreateIdentityTraceDevicesTable')) {
                $this->publishes([
                    __DIR__.'/../migrations/create_identity_trace_devices_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_identity_trace_devices_table.php'),
                    __DIR__.'/../migrations/create_identity_trace_logins_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_identity_trace_logins_table.php')
                ], 'identity-trace');
            }
        }

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->app['events'];
        $dispatcher->subscribe(IdentityTraceSubscriber::class);
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(AgentServiceProvider::class);
        $this->app->alias(IdentityTraceLoginService::class, 'identity-trace-login-service');
        $this->app->alias(IdentityTraceDeviceService::class, 'identity-trace-device-service');
        $this->app->alias(IdentityTraceAgentService::class, 'identity-trace-agent-service');
    }
}
