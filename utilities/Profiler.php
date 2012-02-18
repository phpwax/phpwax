<?php 
namespace Wax\Utilities;
use Wax\Core\Event;
use Wax\Template\Template;

class Profiler {

	/**
	 * @var  integer   maximium number of application stats to keep
	 */
	public static $rollover = 1000;

	// Collected benchmarks
	public static $_marks = array();
	public static $markers = array();
	
	// Current Benchmark: Allows state for nested or sequential start/stops
	public static $current_benchmark = array();

	/**
	 * Starts a new benchmark and returns a unique token.
	 *
	 * @param   string  group name
	 * @param   string  benchmark name
	 * @return  string
	 */
	public static function start($group, $name, $data=false, $trace = false) {
		static $counter = 0;

		// Create a unique token based on the counter
		$token = 'kp/'.base_convert($counter++, 10, 32);

		Profiler::$_marks[$token] = array
		(
			'group' => strtolower($group),
			'name'  => (string) $name,

			// Start the benchmark
			'start_time'   => microtime(TRUE),
			'start_memory' => memory_get_usage(),

			// Set the stop keys without values
			'stop_time'    => FALSE,
			'stop_memory'  => FALSE,
			'data'         => $data,
			'trace'        => $trace
		);

		return $token;
	}

	/**
	 * Stops a benchmark.
	 *
	 * @param   string  token
	 * @return  void
	 */
	public static function stop($token) {
		// Stop the benchmark
		Profiler::$_marks[$token]['stop_time']   = microtime(TRUE);
		Profiler::$_marks[$token]['stop_memory'] = memory_get_usage();
	}

	/**
	 * Deletes a benchmark.
	 *
	 * @param   string  token
	 * @return  void
	 */
	public static function delete($token) {
		// Remove the benchmark
		unset(Profiler::$_marks[$token]);
	}

	/**
	 * Returns all the benchmark tokens by group and name as an array.
	 *
	 * @return  array
	 */
	public static function groups() {
		$groups = array();

		foreach (Profiler::$_marks as $token => $mark) {
			// Sort the tokens by the group and name
			$groups[$mark['group']][$mark['name']][] = $token;
		}

		return $groups;
	}

	/**
	 * Gets the min, max, average and total of a set of tokens as an array.
	 *
	 * @param   array  profiler tokens
	 * @return  array  min, max, average, total
	 */
	public static function stats(array $tokens) {
		// Min and max are unknown by default
		$min = $max = array(
			'time'   => NULL,
			'memory' => NULL,
			);

		// Total values are always integers
		$total = array(
			'time' => 0,
			'memory' => 0);

		foreach ($tokens as $token) {
			// Get the total time and memory for this benchmark
			list($time, $memory) = Profiler::total($token);

			if ($max['time'] === NULL OR $time > $max['time'])
			{
				// Set the maximum time
				$max['time'] = $time;
			}

			if ($min['time'] === NULL OR $time < $min['time'])
			{
				// Set the minimum time
				$min['time'] = $time;
			}

			// Increase the total time
			$total['time'] += $time;

			if ($max['memory'] === NULL OR $memory > $max['memory'])
			{
				// Set the maximum memory
				$max['memory'] = $memory;
			}

			if ($min['memory'] === NULL OR $memory < $min['memory'])
			{
				// Set the minimum memory
				$min['memory'] = $memory;
			}

			// Increase the total memory
			$total['memory'] += $memory;
		}

		// Determine the number of tokens
		$count = count($tokens);

		// Determine the averages
		$average = array(
			'time' => $total['time'] / $count,
			'memory' => $total['memory'] / $count);

		return array(
			'min' => $min,
			'max' => $max,
			'total' => $total,
			'average' => $average);
	}

	/**
	 * Gets the total execution time and memory usage of a benchmark as a list.
	 *
	 * @param   string  token
	 * @return  array   execution time, memory
	 */
	public static function total($token) {
		// Import the benchmark data
		$mark = Profiler::$_marks[$token];

		if ($mark['stop_time'] === FALSE) {
			// The benchmark has not been stopped yet
			$mark['stop_time']   = microtime(TRUE);
			$mark['stop_memory'] = memory_get_usage();
		}

		return array
		(
			// Total time in seconds
			$mark['stop_time'] - $mark['start_time'],

			// Amount of memory in bytes
			$mark['stop_memory'] - $mark['start_memory'],
			// Data attached to benchmark
			$mark['data'],
			$mark["trace"]
		);
	}
	
	public function marker($name) {
	  self::$markers[] = array(
	    "name"        =>  $name,
	    "time"  =>  (microtime(TRUE) - WAX_START_TIME),
	    "memory"=>  (memory_get_usage() - WAX_START_MEMORY)
	  );
	}
	
	public function marker_stats() {
	  $total_time = 0;
	  $total_memory = 0;
	  foreach(Profiler::$markers as $marker) {
	    $total_time += $marker["time"];
	    $total_memory += $marker["memory"]; 
	  }
	  return array("markers"=>Profiler::$markers, "total_time"=>$total_time, "total_memory"=>$total_memory);
	}


  
  public function profile() {

    Event::add("wax.db_query", function(){
      Profiler::$current_benchmark[] = Profiler::start("Application", "Database Queries", Event::data()->queryString, debug_backtrace(false));
    });
    Event::add("wax.db_query_end", function() {Profiler::stop(array_pop(Profiler::$current_benchmark));});
    
    Event::add("wax.partial", function(){
      Profiler::$current_benchmark[] = Profiler::start("Application", "Partials", Event::data()->path);      
    });
    Event::add("wax.partial_render", function() {
      Profiler::stop(array_pop(Profiler::$current_benchmark));      
    });
    
    
    
    
    
    Event::add("wax.start", function(){Profiler::marker("Request Received");});
    Event::add("wax.init", function(){Profiler::marker("Application Initialised");});
    Event::add("wax.post_request", function(){Profiler::marker("Request Processed");});
    Event::add("wax.controller_global", function(){Profiler::marker("Controller Loaded");});
    Event::add("wax.action", function(){Profiler::marker("Action Ready");});
    Event::add("wax.layout", function(){Profiler::marker("View Parsed");});
    Event::add("wax.post_render", function(){Profiler::marker("Response Ready");});

    Event::add("wax.post_render", function(){
      if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') return true;
      $profile_view = new Template;
      $profile_view->add_path(FRAMEWORK_DIR."/template/builtin/profile");
      if(strpos(Event::data()->body,"</body>")===false) Event::data()->body.=$profile_view->parse();
      else Event::data()->body = preg_replace("/(.*)<\/body>(.*)/", "$1".$profile_view->parse()."</body>$2",Event::data()->body);
    });
    
  }
  
  

	

} // End Profiler