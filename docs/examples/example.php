<?php
/* Usage example of Defensio-PHP
 * To run, simply execute 'php example.php'.
 */
 
$api_key = 'KEY_HERE';

require_once('defensio-php/Defensio.php');
$defensio = new Defensio($api_key);
$document = array();

print (array_shift($defensio->getUser()) == 200) ? ">>>>>>>>>> API key is valid.\n" : die(">>>>>>>>>> API key is invalid. Get one at http://defensio.com.\n");

$document = array('type' => 'comment', 'content' => 'I love Defensio!', 'platform' => 'defensio_php_example', 'client' => 'Defensio-PHP Example | 0.1 | Joe Blow | joe@bloe.com', 'async' => 'false');

$post_result = $defensio->postDocument($document);
print(">>>>>>>>>> POSTing a document returns the following:\n" . print_r($post_result[1], true));

$doc1_signature = $post_result[1]->signature;
$get_result  = $defensio->getDocument($doc1_signature);

print(">>>>>>>>>> Performing a GET for the previous document ($doc1_signature) returns the following:\n" . print_r($get_result[1], true));

$put_result = $defensio->putDocument($doc1_signature, array('allow' => 'false'));
print(">>>>>>>>>> Changing the value of 'allow' to 'false' returns:\n" . print_r($put_result[1], true));

$get_result  = $defensio->getDocument($doc1_signature);
print(">>>>>>>>>> Performing another GET after PUT returns:\n" . print_r($get_result[1], true));

$stats_result  = $defensio->getBasicStats();
print(">>>>>>>>>> Performing a GET for basic statistics returns:\n" . print_r($stats_result[1], true));

$extended_stats_result  = $defensio->getExtendedStats(array('from' => '2009-10-01', 'to' => '2009-10-10'));
print(">>>>>>>>>> Performing a GET for extended statistics returns:\n" . print_r($extended_stats_result[1], true));

$dictionary_filter_result = $defensio->postProfanityFilter(array('field1' => 'This content contains some fucking cursing.'));
print(">>>>>>>>>> Performing a dictionary filter POST returns:\n" . print_r($dictionary_filter_result[1], true));

print(">>>>>>>>>> Done!");
?>

