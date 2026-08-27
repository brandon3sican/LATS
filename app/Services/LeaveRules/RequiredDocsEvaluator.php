<?php

namespace App\Services\LeaveRules;

use App\Models\LeaveType;

class RequiredDocsEvaluator
{
    /**
     * @return array<int, array{name:string,key:?string}>
     */
    public function requiredDocsFor(LeaveType $leaveType, array $payload): array
    {
        $docs = $leaveType->requiredDocuments()->get();

        $required = [];
        foreach ($docs as $doc) {
            $rule = $doc->rule_json;

            $isRequiredNow = $doc->is_required && ($rule === null || $this->matchRule($rule, $payload));

            if ($isRequiredNow) {
                $required[] = ['name' => $doc->name, 'key' => $doc->key];
            }
        }

        return $required;
    }

    private function matchRule(array $rule, array $payload): bool
    {
        // days comparisons
        if (isset($rule['days_gt'])) {
            $days = (float)($payload['working_days_requested'] ?? 0);
            return $days > (float)$rule['days_gt'];
        }
        if (isset($rule['days_gte'])) {
            $days = (float)($payload['working_days_requested'] ?? 0);
            return $days >= (float)$rule['days_gte'];
        }

        // field equals (supports dot paths)
        if (isset($rule['field']) && array_key_exists('equals', $rule)) {
            $value = $this->getDot($payload, (string)$rule['field']);
            return $value === $rule['equals'];
        }

        // field in [..]
        if (isset($rule['field']) && isset($rule['in']) && is_array($rule['in'])) {
            $value = $this->getDot($payload, (string)$rule['field']);
            return in_array($value, $rule['in'], true);
        }

        // boolean truthy
        if (isset($rule['field']) && isset($rule['truthy'])) {
            $value = $this->getDot($payload, (string)$rule['field']);
            return (bool)$value === (bool)$rule['truthy'];
        }

        // any/all
        if (isset($rule['any']) && is_array($rule['any'])) {
            foreach ($rule['any'] as $sub) {
                if (is_array($sub) && $this->matchRule($sub, $payload)) return true;
            }
            return false;
        }
        if (isset($rule['all']) && is_array($rule['all'])) {
            foreach ($rule['all'] as $sub) {
                if (!is_array($sub) || !$this->matchRule($sub, $payload)) return false;
            }
            return true;
        }

        return false;
    }

    private function getDot(array $payload, string $path)
    {
        $parts = explode('.', $path);
        $cur = $payload;
        foreach ($parts as $p) {
            if (!is_array($cur) || !array_key_exists($p, $cur)) return null;
            $cur = $cur[$p];
        }
        return $cur;
    }
}
