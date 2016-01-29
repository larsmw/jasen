<?php

class Events {
  /// Events management
  protected static $events;

  /*
   * @param string $namespace The event class, you may specify wild cards.
   * @param string $event The event, you may specify wild cards.
   * @param string $handler Handling function
   * @param int $priority Value determining the order of execution.
   */
  public static function register($namespace, $event, callable $handler, $priority = 500) {
    // Turn highchair/elgg style wildcards into correct ones.
    $namespace = str_replace('*', '.*', $namespace);
    $namespace = str_replace('/', '\/', $namespace);
    $namespace = '/^' . str_replace('all', '.*', $namespace) . '$/';
    $event = str_replace('*', '.*', $event);
    $event = str_replace('/', '\/', $event);
    $event = '/^' . str_replace('all', '.*', $event) . '$/';

    if (!is_callable($handler))
      throw new EventDispatcherException("Handler $handler for $namespace/$event doesn't appear to be callable.");
    if (!isset(self::$events))
      self::$events = array();
    if (!isset(self::$events[$namespace]))
      self::$events[$namespace] = array();
    if (!isset(self::$events[$namespace][$event]))
      self::$events[$namespace][$event] = array();
    while (isset(self::$events[$namespace][$event][$priority]))
      $priority++;
    self::$events[$namespace][$event][$priority] = $handler;
    ksort(self::$events[$namespace][$event]);
    
    //dbg(self::$events[$namespace][$event]);
    //dbg($handler);
    return true;
  }

  /**
   * Trigger an event.
   * 
   * A triggered event will have its return value placed in $parameters['return'] automatically. If a triggered event sets $parameters['halt'] to true
   * then the execution chain is stopped, and the current value of $parameters['return'] is returned.
   *
   * @param string $namespace The event namespace
   * @param string $event The event
   * @param array $parameters Associated array of parameters. 
   * @return mixed
   */
  public static function trigger($namespace, $event, array $parameters = NULL) {       
    $merged = array();
    if (!$parameters)
      $parameters = array();
    $default = array(
		     'halt' => false,
		     'return' => null
		     );

    $merged_parameters = array_merge($default, $parameters);
    if (!self::$events)
      self::$events = array();
    // Get events we're triggering
    foreach (self::$events as $namespace_key => $event_list) {
      // Does the namespace being triggered match a registered namespace?
      if (preg_match($namespace_key, $namespace)) {
	foreach (self::$events[$namespace_key] as $event_key => $function_list) {
	  // Does the event being triggered match a registered event
	  if (preg_match($event_key, $event)) {
	    // Now add and prioritise events
	    foreach ($function_list as $priority => $function) {
	      // Adjust priority to free slot
	      while (isset($merged[$priority]))
		$priority++;
	      $merged[$priority] = $function;
	    }
	  }
	}
      }
    }
    // Now sort on priority and execute 
    ksort($merged); 
    foreach ($merged as $function) {
      $result = null;
      $result = call_user_func_array($function, array($namespace, $event, &$merged_parameters));
      if (isset($result)) 
	$merged_parameters['page'] = array_merge_recursive($merged_parameters['page'] ,$result);
      if ( (isset($merged_parameters['halt'])) && ($merged_parameters['halt']))
	return $merged_parameters['page'];
    }
    if ( isset($merged_parameters['page'])) {
      foreach($merged_parameters['page'] as $k => $a) {
	if (is_array($a))
	  $merged_parameters['page'][$k] = implode($a);
      }
    }
    if (isset($merged_parameters['page']))
      return $merged_parameters['page'];
  }
  
  /**
   * Return whether a given event has a handler.
   * @param type $namespace
   * @param type $event
   * @return bool
   */
  public static function exists($namespace, $event) { 
    if (isset(self::$events[$namespace][$event]))
      return is_array(self::$events[$namespace][$event]);
    return false;
  }
}

/**
 * Parent exception
 */
class EventDispatcherException extends \Exception{}

