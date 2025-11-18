<?php

namespace App\Services;

use App\Contracts\SubacquirerInterface;
use App\Models\User;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class SubacquirerManager
{
    protected array $map;

    public function __construct(private readonly Container $app)
    {
        $this->map = config('subacquirers.map', []);
    }

    public function resolve(string $slug): SubacquirerInterface
    {
        if (! isset($this->map[$slug])) {
            throw new RuntimeException("Adapter for [$slug] not configured");
        }

        return $this->app->make($this->map[$slug]);
    }

    public function forUser(User $user): SubacquirerInterface
    {
        $subacquirer = $user->subacquirer;

        if (! $subacquirer) {
            throw new RuntimeException('User has no subacquirer assigned');
        }

        return $this->resolve($subacquirer->slug);
    }
}
