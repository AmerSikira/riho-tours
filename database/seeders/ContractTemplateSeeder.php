<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    /**
     * Seed default versioned contract templates for regular and subagent arrangements.
     */
    public function run(): void
    {
        $actorId = User::query()->where('email', 'user1@user.com')->value('id');

        $templates = [
            [
                'template_key' => 'standard-travel-contract',
                'version' => 1,
                'name' => 'Standard Travel Contract',
                'description' => 'Baseline contract for regular travel reservations.',
                'subagentski_ugovor' => false,
                'file' => resource_path('contracts/templates/standard-travel-contract-v1.html'),
            ],
            [
                'template_key' => 'subagent-travel-contract',
                'version' => 1,
                'name' => 'Subagent Travel Contract',
                'description' => 'Contract format for subagent travel reservations.',
                'subagentski_ugovor' => true,
                'file' => resource_path('contracts/templates/premium-travel-contract-v1.html'),
            ],
        ];

        foreach ($templates as $template) {
            if (! file_exists($template['file'])) {
                continue;
            }

            $existing = ContractTemplate::query()->where([
                'template_key' => $template['template_key'],
                'version' => $template['version'],
            ])->first();

            ContractTemplate::query()->updateOrCreate(
                [
                    'template_key' => $template['template_key'],
                    'version' => $template['version'],
                ],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'html_template' => file_get_contents($template['file']) ?: '',
                    'placeholder_hints_json' => null,
                    'is_active' => true,
                    'subagentski_ugovor' => (bool) $template['subagentski_ugovor'],
                    'previous_version_id' => null,
                    'created_by' => $existing?->created_by ?? $actorId,
                    'updated_by' => $actorId,
                ]
            );
        }
    }
}
