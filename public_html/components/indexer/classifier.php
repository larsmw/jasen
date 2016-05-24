<?php

interface StorageAdapter
{
    /**
     * Get distinct names of labels
     *
     * @return array
     */
    public function getDistinctLabels();
    /**
     * Get how many times we have seen this word before
     *
     * @param $word
     *
     * @return int
     */
    public function getWordCount($word);
    /**
     * Get the probability that this word shows up in a LABEL document
     *
     * @param $word
     * @param $label
     *
     * @return float
     */
    public function getWordProbabilityWithLabel($word, $label);
    /**
     * Get the probability that this word shows up in a any other LABEL
     *
     * @param $word
     * @param $label
     *
     * @return float
     */
    public function getInverseWordProbabilityWithLabel($word, $label);
    public function insertLabel($label);
    public function insertWord($word, $label);
}

class PersistentMemory implements StorageAdapter
{
  var $words = array();
  var $labels = array();
  var $d;

  public function __construct() {
    $this->d = new Database();
    // we should have a word index elsewhere!!
    if (!$this->d->tableExists("classifier_words")) {
      $sql = "CREATE TABLE classifier_words ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
	"word VARCHAR(2048));";
      $this->d->exec($sql);
    }
    if (!$this->d->tableExists("classifier_labels")) {
      $sql = "CREATE TABLE classifier_labels ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, " .
	"label VARCHAR(2048));";
      $this->d->exec($sql);
    }
    $sql = "SELECT * FROM classifier_words;";
    $r = $this->d->q($sql);
    foreach($r as $row) {
      $this->words[] = $row['word'];
    }
    $sql = "SELECT * FROM classifier_labels;";
    $r = $this->d->q($sql);
    foreach($r as $row) {
      $this->words[] = $row['label'];
    }
  }

  public function __destruct() {
    foreach ($this->words as $word) {
      $sql = "SELECT * FROM classifier_words WHERE word like '".serialize($word)."';";
      $r = $this->d->q($sql);
      if(count($r)===0) {
	$sql = "INSERT INTO classifier_words (word) VALUES ('".serialize($word)."');";
	$id = $this->d->insert($sql);
      }
    }
    foreach ($this->labels as $label) {
      $sql = "SELECT * FROM classifier_labels WHERE label like '$label';";
      $r = $this->d->q($sql);
      if(count($r)===0) {
	$sql = "INSERT INTO classifier_labels (label) VALUES ('".$label."');";
	$id = $this->d->insert($sql);
      }
    }
  }

  /**
   * Get total documents the system has been trained,
   * by counting the number of labels (not distinct)
   *
   * @return int
   */
  public function getTotalDocs()
  {
    return count($this->labels);
  }

  /**
   * Get how many documents we have seen so far for each label
   *
   * @return array
   */
  public function getTotalDocsGroupByLabel()
  {
    $results = array();
    foreach ($this->labels as $l)
      if (isset($results[ $l ]))
	$results[ $l ] += 1;
      else
	$results[ $l ] = 1;
    return $results;
  }

  /**
   * @see StorageAdapter::getDistinctLabels()
   */
  public function getDistinctLabels()
  {
    $results = array();
    foreach ($this->labels as $l)
      if (!in_array($l, $results))
	$results[ ] = $l;
    return $results;
  }

  /**
   * Get how many documents there are that does not contain a label
   * grouped by label
   *
   * @return array
   */
  public function getInverseTotalDocsGroupByLabel()
  {
    $docCounts = $this->getTotalDocsGroupByLabel();
    $totalDocs = $this->getTotalDocs();
    $docInverseCounts = array();
    foreach ($docCounts as $key => $item) {
      $docInverseCounts[ $key ] = $totalDocs - $item;
    }
    return $docInverseCounts;
  }
  /**
   * @see StorageAdapter::getWordCount()
   */
  public function getWordCount($word)
  {
    $total = 0;
    foreach ($this->words as $w)
      if ($w[ 'word' ] == $word)
	$total++;
    return $total;
  }
  /**
   * @see StorageAdapter::getWordProbabilityWithLabel()
   */
  public function getWordProbabilityWithLabel($word, $label)
  {
    $total = 0;
    foreach ($this->words as $k => $w)
      if ($w[ 'word' ] == $word && $w[ 'label' ] == $label)
	$total++;
    return $total;
  }
  /**
   * @see StorageAdapter::getInverseWordProbabilityWithLabel()
   */
  public function getInverseWordProbabilityWithLabel($word, $label)
  {
    $total = 0;
    foreach ($this->words as $k => $w)
      if ($w[ 'word' ] == $word && $w[ 'label' ] != $label)
	$total++;
    return $total;
  }
  /**
   * @see StorageAdapter::insertLabel()
   */
  public function insertLabel($label)
  {
    $this->labels[ ] = $label;
  }
  /**
   * @see StorageAdapter::insertWord()
   */
  public function insertWord($word, $label)
  {
    $this->words[ ] = [
		       'word'  => $word,
		       'label' => $label
		       ];
  }
}

class Classifier
{
  /**
   * @var Adapter Storage Adapter
   */

  var $storage;

  function __construct($storage)
  {
    mb_internal_encoding("UTF-8");
    if ($storage instanceof StorageAdapter)
      $this->storage = $storage;
    else
      throw new \Exception('Storage must implement Documer\Storage\Adapter interface.');
  }

  /**
   * This will return if a word is starting with an Uppercase letter, with UTF-8 Support
   *
   * @see http://stackoverflow.com/questions/2814880/how-to-check-if-letter-is-upper-or-lower-in-php
   *
   * @param $str String The string to examine
   */
  private function startsWithUppercase($str)
  {
    $chr = mb_substr($str, 0, 1, "UTF-8");
    return mb_strtolower($chr, "UTF-8") != $chr && mb_strlen($str, "UTF-8") > 1;
  }

  /**
   * This is our training method, that parses the text and push the keywords to DB
   *
   * @param $label
   * @param $text
   */
  public function train($label, $text)
  {
    $keywords = $this->parse($text);
    $this->getStorage()
      ->insertLabel($label);
    foreach ($keywords as $k) {
      $this->getStorage()
	->insertWord($k, $label);
    }
  }

  /**
   *
   * This is the preprocess function!!!!!
   *
   * We need to parse the text from some sort of tokenization
   *
   * We keep only alphanumeric strings that starts with an uppercase
   *
   * @param $text
   *
   * @return array
   */
  private function parse($text)
  {
    $unwantedChars = array(
      ',', '!', '?', '.', ']', '[', '!', '"', '#', '$', '%', '&', '\'', '(', ')', '*', '+', '/', ':', ';', '<',
      '=', '>', '?', '^', '{', '|', '}', '~', '-', '@', '\', ', '_', '`'
    );
    $str   = str_replace($unwantedChars, ' ', $text);
    $array = explode(" ", $str);
    $array = array_map('trim', $array);
    $array = array_unique($array);
    $array = array_values($array);
    $clean = array();
    foreach ($array as $k)
      //if ($this->startsWithUppercase($k))
	$clean[ ] = $k;
    return array_values($clean);
  }
  
  /*
   * This is the guessing method, which uses Bayes Theorem to calculate probabilities
   */
  public function guess($text)
  {
    echo __LINE__;
    var_dump(microtime(TRUE));
    $scores = array();
    $words  = $this->parse($text);
    echo __LINE__;
    //var_dump($words); return;
    $labels = $this->getStorage()
      ->getDistinctLabels();
    echo __LINE__;
    //var_dump($labels);
return;
    foreach ($labels as $label) {
      echo $label;
    echo __LINE__;
      var_dump(microtime(TRUE));
      $logSum = 0;
      foreach ($words as $word) {
	echo $word;
	var_dump(microtime(TRUE));
	$wordTotalCount = $this->getStorage()
	  ->getWordCount($word);
	if ($wordTotalCount == 0) {
	  continue;
	} else {
	  $wordProbability        = $this->getStorage()
	    ->getWordProbabilityWithLabel($word, $label);
	  $wordInverseProbability = $this->getStorage()
	    ->getInverseWordProbabilityWithLabel($word, $label);
	  /**
	   * Prevent division with zero
	   */
	  if ($wordProbability + $wordInverseProbability == 0)
	    continue;
	  $wordicity = $this->getWordicity($wordTotalCount, $wordProbability, $wordInverseProbability);
	}
	/**
	 * logs to avoid "floating point underflow",
	 */
	$logSum += (log(1 - $wordicity) - log($wordicity));
      }
      /**
       * undo the log function and get back to 0-1 range
       */
      $scores[ $label ] = 1 / (1 + exp($logSum));
    }
    echo __LINE__;
    var_dump(microtime(TRUE));
    return $scores;
  }

  public function getWordicity($wordTotalCount, $wordProbability, $wordInverseProbability)
  {
    $denominator = $wordProbability + $wordInverseProbability;
    /**
     * Bayes Theorem using the above parameters
     *
     * the probability that this document is a particular LABEL
     * given that a particular WORD is in it
     *
     */
    $wordicity = $wordProbability / $denominator;
    /*
     * here 0.5 is the weight, higher training data in the db means higher weight
     */
    $wordicity = ((10 * 0.5) + ($wordTotalCount * $wordicity)) / (10 + $wordTotalCount);
    if ($wordicity == 0)
      $wordicity = 0.01;
    else if ($wordicity == 1)
      $wordicity = 0.99;
    return $wordicity;
  }

  /**
   * Check if text is of the given label
   *
   * @param $label
   * @param $text
   */
  public function is($label, $text)
  {
    $scores = $this->guess($text);
    $value = max($scores);
    return $label == array_search($value, $scores);
  }

  /**
   * @return StorageAdapter
   */
  public function getStorage()
  {
    return $this->storage;
  }
}
