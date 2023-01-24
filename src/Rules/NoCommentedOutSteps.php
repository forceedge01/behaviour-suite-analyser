<?php

namespace Forceedge01\BDDStaticAnalyser\Rules;

use Forceedge01\BDDStaticAnalyser\Entities;

class NoCommentedOutSteps extends BaseRule {
    const VIOLATION_MESSAGE = 'In support of clean code, do not leave behind commented out code.';

    public function applyOnStep(Entities\Step $step, Entities\OutcomeCollection $collection) {
        if (!$step->isActive()) {
            $collection->addOutcome($this->getOutcomeObject(
                $step->lineNumber,
                self::VIOLATION_MESSAGE,
                Entities\Outcome::MEDIUM,
                $step->getStepDefinition(),
                $step->trimmedTitle
            ));
        }
    }
}