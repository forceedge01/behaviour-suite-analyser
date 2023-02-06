<?php

declare(strict_types = 1);

namespace Forceedge01\BDDStaticAnalyser\Processor;

use Forceedge01\BDDStaticAnalyserRules\Rules;
use Forceedge01\BDDStaticAnalyserRules\Entities;

class RulesProcessor
{
    private array $rules = [];
    private array $ruleObjects = [];
    private array $outcome = [];

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * All rules are applied everytime on each file.
     */
    public function applyRules(
        Entities\FeatureFileContents $contentObject,
        Entities\OutcomeCollection $collection
    ): Entities\OutcomeCollection {
        $collection->summary['files']++;
        $collection->summary['activeRules'] = $this->rules;

        foreach ($this->rules as $ruleClass => $params) {
            try {
                $rule = $this->getRule($ruleClass, $params);
                $rule->beforeApply($contentObject->filePath, $collection);
                $this->outcome[] = $this->applyRule($contentObject, $rule, $collection);
            } catch (\Exception $e) {
                throw new \Exception(sprintf(
                    'Unable to apply rule "%s" on file "%s". Error: %s',
                    $ruleClass,
                    $contentObject->filePath,
                    $e->getMessage()
                ));
                continue;
            }
        }

        return $collection;
    }

    public function getRule(string $rule, array $params = null): Rules\RuleInterface
    {
        if (isset($this->ruleObjects[$rule])) {
            return $this->ruleObjects[$rule]->reset();
        }

        $this->ruleObjects[$rule] = new $rule($params);

        return $this->ruleObjects[$rule];
    }

    public function applyRule(
        Entities\FeatureFileContents $contentObject,
        Rules\RuleInterface $rule,
        Entities\OutcomeCollection $collection
    ): Entities\OutcomeCollection {
        $rule->setFeatureFileContents($contentObject);
        $rule->applyOnFeature($contentObject, $collection);

        if ($contentObject->background) {
            $collection->addSummary('backgrounds', $contentObject->filePath . $contentObject->background->lineNumber);
            $rule->applyOnBackground($contentObject->background, $collection);
        }

        foreach ($contentObject->scenarios as $scenario) {
            // Scenarios
            $collection->addSummary('scenarios', $contentObject->filePath . $scenario->lineNumber);
            $rule->setScenario($scenario);
            $rule->beforeApplyOnScenario($scenario, $collection);
            $rule->applyOnScenario($scenario, $collection);

            // Steps
            $steps = $scenario->getSteps();

            foreach ($steps as $index => $step) {
                $collection->addSummary('activeSteps', $step->getStepDefinition());
                $rule->beforeApplyOnStep($step, $collection);
                $rule->applyOnStep($step, $collection);
                $rule->afterApplyOnStep($step, $collection);
            }

            $rule->afterApplyOnScenario($scenario, $collection);
        }

        $rule->applyAfterFeature($contentObject, $collection);

        return $collection;
    }
}
