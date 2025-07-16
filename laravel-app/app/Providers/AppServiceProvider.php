<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use App\Repositories\CustomerRepository;
use App\Services\CustomerImportService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Customer\CustomerRepositoryInterface::class,
            \App\Repositories\Customer\CustomerRepository::class
        );

        $this->app->bind(
            \App\Repositories\CustomerAddress\CustomerAddressRepositoryInterface::class,
            \App\Repositories\CustomerAddress\CustomerAddressRepository::class
        );

        $this->app->bind(
            \App\Repositories\CustomerFailure\CustomerFailureRepositoryInterface::class,
            \App\Repositories\CustomerFailure\CustomerFailureRepository::class
        );

        $this->app->bind(
            \App\Repositories\CustomerImportLog\CustomerImportLogRepositoryInterface::class,
            \App\Repositories\CustomerImportLog\CustomerImportLogRepository::class
        );

        $this->app->bind(
            \App\Repositories\CustomerSegment\CustomerSegmentRepositoryInterface::class,
            \App\Repositories\CustomerSegment\CustomerSegmentRepository::class
        );

        $this->app->bind(
            \App\Repositories\CustomerType\CustomerTypeRepositoryInterface::class,
            \App\Repositories\CustomerType\CustomerTypeRepository::class
        );

        $this->app->bind(
            \App\Repositories\Gender\GenderRepositoryInterface::class,
            \App\Repositories\Gender\GenderRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\TempCustomer\TempCustomerRepositoryInterface::class,
            \App\Repositories\TempCustomer\TempCustomerRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\TempCustomerAddress\TempCustomerAddressRepositoryInterface::class,
            \App\Repositories\TempCustomerAddress\TempCustomerAddressRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
