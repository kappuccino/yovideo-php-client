<?php

namespace YoVideo;

class Tools{

	/**
	 * https://gist.github.com/563670/6d3d934eb5ca9916e1fef8f8cc08f89ea90a025e
	 * Get a particular value back from the config array
	 * @param array $array The array to work with
	 * @param string $index The index to fetch in dot notation
	 * @return mixed
	 */
	public static function arrayGet($array, $index) {
		$index = explode('.', $index);
		return self::arrayValue($index, $array);
	}

	/**
	 * http://stackoverflow.com/questions/7851590/array-set-value-using-dot-notation
	 *
	 * @param array $arr
	 * @param       $path
	 * @param       $val
	 *
	 * @return mixed
	 */
	public static function arraySet(array &$arr, $path, $val){
		$loc = &$arr;

		foreach(explode('.', $path) as $step){
			$loc = &$loc[$step];
		}

		return $loc = $val;
	}

	/**
	 * Navigate through an array looking for a particular index
	 *
	 * @param array $index The index sequence we are navigating down
	 * @param array $value The portion of the config array to process
	 * @param null  $first_index
	 *
	 * @return mixed
	 */
	private static function arrayValue($index, $value, $first_index=NULL) {

		if(empty($first_index)) $first_index = $index;

		if(is_array($index) and count($index)) {
			$current_index = array_shift($index);
		}

		#	echo PHP_EOL;
		#	echo '... '.$current_index.PHP_EOL;
		#	var_dump($index);

		if(is_array($index) and count($index) and empty($value[$current_index])) {
			return NULL;
		}else
		if(is_array($index) and count($index) and is_array($value[$current_index]) and count($value[$current_index])) {
			return self::arrayValue($index, $value[$current_index], $first_index);
		}else{

			#		echo PHP_EOL;
			#		echo '> '.$current_index;
			#		echo PHP_EOL;

			return $value[$current_index];
		}
	}


	public static function arrayFlatten($array, $separator = '.', $parent = null){

		if(!is_array($array)) return $array;

		$_flattened = array();

		// Rewrite keys
		foreach ($array as $key => $value) {
			if($parent) $key = $parent.$separator.$key;
			$_flattened[$key] = self::arrayFlatten($value, $separator, $key);
		}

		// Flatten
		$flattened = array();
		foreach ($_flattened as $key => $value) {
			if(is_array($value)) $flattened = array_merge($flattened, $value);
			else $flattened[$key] = $value;
		}

		return $flattened;
	}

	/**
	 * Lazy debug function
	 * pre($var, $bis, $ter [, ...]);
	 */
	public static function pre(){

		echo '<pre style="text-align:left; background-color:#FFFFFF; color:#515151; padding:5px; border:1px solid #515151;">';

		for($i=0; $i<func_num_args(); $i++){
			(!is_array(func_get_arg($i)) && !is_object(func_get_arg($i)))
					? print(func_get_arg($i)."\n")
					: print_r(func_get_arg($i));
		}

		echo '</pre>';
	}

	public static function go($url){
		header('Location: '.$url);
		exit();
	}

	public static function memoryUsage(){
		return self::convert(memory_get_usage());
	}

	public static function convert($size){
		$unit=array('b','kb','mb','gb','tb','pb');
		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}

	static function date($f, $d){

		// Si on donne une date au formate 'YYYY-MM-DD'
		if(strpos($d, '-') !== false){
			$d = (new DateTime($d))->getTimestamp();
		}

		return utf8_encode(strftime($f, $d));
	}
}