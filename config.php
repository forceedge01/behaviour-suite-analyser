<?php

use Forceedge01\BDDStaticAnalyser\Rules;

return [
	// Parent directory where all feature files are present.
	'feature_file_extension' => 'feature',
	'step_definition_file_extension' => 'php',

	// Configure which class will process the outcomes and display the summary.
	'display_processor' => Forceedge01\BDDStaticAnalyser\Processor\DisplayProcessor::class,

	// Class => ?array
	'rules' => [
		Rules\NoUrlInSteps::class => null,
		Rules\NoLongScenarios::class => [10],
		Rules\NoCommentedOutSteps::class => null,
		// OnlyValidOrderAllowed::class => 'strict',
		// NoSelectorsInSenarios::class => 'strict',
		// OnlyFirstPersonLanguageAllowed::class => 'strict',
	]
];
