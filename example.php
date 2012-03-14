<?php

	require_once("uClassify.php");
	
	$uclassify = new uClassify();
	
	// Set these values here
	$uclassify->setReadApiKey('');
	$uclassify->setWriteApiKey('');

	echo "<h1> uClassify Examples (See source code for usage) </h1> <hr />";
	
	try {
		$t = array('Many texts are present in this array','What shall I do now?', 'Plz dont do this to me.. Noooooooooo', 'Understanding if a text is positive or negative is easy for humans, but a lot harder for computers. We can “read between the lines”, get jokes and identify irony. Computers aren’t quite there yet but the gap is quickly closing in. Our contribution is a free Sentiment API that can help users to do market research, brand surveys and see trends around their campaigns. The API will not only reveal if a document is positive or negative, it will also indicate how positive or negative it is.');
		
		echo "<h1>Read Calls</h1><hr />";
		echo "<h1>classify</h1>";
		$resp = $uclassify->classify('Understanding if a text is positive or negative is easy for humans, but a lot harder for computers. We can "read between the lines", get jokes and identify irony. Computers aren’t quite there yet but the gap is quickly closing in.', 'Sentiment', 'uClassify');
		echo "<pre>";
		print_r($resp);
		echo "</pre>";


		echo "<h1>classifyMany</h1>";
		$resp = $uclassify->classifyMany($t, 'Sentiment', 'uClassify');
		echo "<pre>";
		print_r($resp);
		echo "</pre>";
		
		echo "<h1>getInformation</h1>";
		// Replace Class1 with your classifier
		$resp = $uclassify->getInformation('Class1');
		echo "<pre>";
		print_r($resp);
		echo "</pre>";
		
		echo "<h1>getInformationMany</h1>";
		// Replace Class1 and Class2 with your classifiers
		$resp = $uclassify->getInformationMany(array('Class1', 'Class2'));
		echo "<pre>";
		print_r($resp);
		echo "</pre>";

		echo "<hr /><h1>Write Calls</h1><hr />";
		
		$title = 'Classify'.time();
		
		echo "<h1>create</h1>";
		$resp = $uclassify->create($title);
		echo "<pre>";
		print_r($resp);
		echo "</pre>";

		echo "<h1>addClass</h1>";
		$resp = $uclassify->addClass('Class1',$title);
		echo "<pre>";
		print_r($resp);
		echo "</pre>";
		
		echo "<h1>train</h1>";
		$resp = $uclassify->train($t, 'Class1',$title);
		echo "<pre>";
		print_r($resp);
		echo "</pre>";
			
		echo "<h1>untrain</h1>";
		$resp = $uclassify->untrain($t, 'Class1',$title);
		echo "<pre>";
		print_r($resp);
		echo "</pre>";
		
		echo "<h1>removeClass</h1>";
		$resp = $uclassify->removeClass('Class1',$title);
		echo "<pre>";
		print_r($resp);
		echo "</pre>";
		
		echo "<h1>remove</h1>";
		$resp = $uclassify->remove($title);
		echo "<pre>";
		print_r($resp);
		echo "</pre>";
		
		
	} catch (uClassifyException $e) {
		die($e->getMessage());
	}
	
