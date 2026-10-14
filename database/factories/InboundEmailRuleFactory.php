<?php

namespace Database\Factories;

use App\Enums\InboundEmailRuleAction;
use App\Enums\MaintenanceLogType;
use App\Models\InboundEmailRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboundEmailRule>
 */
class InboundEmailRuleFactory extends Factory
{
    protected $model = InboundEmailRule::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => 'Fault emails',
            'is_active' => true,
            'priority' => 100,
            'match_conditions' => ['subject_contains' => 'fault'],
            'action' => InboundEmailRuleAction::CreateLog,
            'log_type' => MaintenanceLogType::Fault,
            'field_mapping' => [],
            'fault_status' => null,
            'require_review' => true,
        ];
    }
}
