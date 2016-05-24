<?php
/**
 * This is a basic templating engine.
 */

/**
 * The template class which transforms template files into a proper output.
 */
class Template {
  protected $file;
  protected $values = array();

  /**
   * @param string $file is the template-file that should be transformed.
   */
  public function __construct($file) {
    $this->file = $file;
  }

  /**
   * Sets a value for the key(s) in the template-file
   * @param string $key 
   * @param string $value
   */
  public function set($key, $value) {
      if(is_array($value) && isset($value[$key])) {
          $value = $value[$key];
      }
      $this->values[$key] = $value;
  }

  /**
   * Parse the input file and return a string with the transformed output.
   * @return string
   */
  public function output() {
    if (!file_exists($this->file)) {
      throw new Exception("Error loading template file ($this->file).");
    }
    $output = file_get_contents($this->file);
    foreach ($this->values as $key => $value) {
      //dbg($key);
      //dbg($value);
      if (is_array($value)) {
	throw new Exception("string expected : key:".var_export($value, TRUE) . "value:".var_export($value, TRUE));
      }
      $tagToReplace = "[@$key]";
            	$output = str_replace($tagToReplace, $value, $output);
    }
    $output = preg_replace('/\[@\w+]/i', '', $output);
    //dbg($output);
    return $output;
  }

  static public function merge($templates, $separator = "\n") {
    $output = "";
    
    foreach ($templates as $template) {
      $content = (get_class($template) !== "Template")
	? "Error, incorrect type - expected Template."
	: $template->output();
      $output .= $content . $separator;
    }
            
    return $output;
  }
}
