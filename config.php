<?php

use BDDStaticAnalyser\Rules;

// Parent directory where all feature files are present.
$dir_to_scan = __DIR__ . '/testproject';
$feature_file_extension = 'feature';
$step_definition_file_extension = 'php';

$rules = [
	Rules\NoUrlInSteps::class => null,
	// NoLongScenarios::class => 20,
	// OnlyValidOrderAllowed::class => 'strict',
	// NoSelectorsInSenarios::class => 'strict',
	// OnlyFirstPersonLanguageAllowed::class => 'strict',
];
