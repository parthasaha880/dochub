<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('ORG-###')),
            'name' => fake()->company(),
            'legal_name' => fake()->company().' Ltd',
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('+1##########'),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'is_active' => true,
        ];
    }
}
