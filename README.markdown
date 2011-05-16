uClassify PHP5 Class
--------------------

This is a PHP5 class for accessing uClassify XML services. 

Currently supports the following operations

1. Classify
2. Classify many texts (batch processing)
3. getInformation
4. getInformationMany
5. CreateClassifier
6. AddClass
7. Train
8. Untrain
9. RemoveClass
10. RemoveClassifier

Usage
-----

Example implementation:

	<?php
	$uclassify = new uClassify();

	$uclassify->setReadApiKey(READ_API_KEY);
	$uclassify->setWriteApiKey(WRITE_API_KEY);

	// Classification from own of public classifiers
	$uclassify->classify('Text that needs to be classified', 'Name of the classifier', (optional)'username_under_which_classifier_exists');
	$uclassify->classifyMany(array('Text that needs to be classified','Text that needs to be classified','Text that needs to be classified'), 'Name of the classifier', (optional)'username_under_which_classifier_exists');

	?>