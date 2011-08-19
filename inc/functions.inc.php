<?php
/**
 * Sort array by key with natural sort algo
 */
function natksort(&$array) {
	$keys = array_keys($array);
	natcasesort($keys);
	
	foreach ($keys as $k) {
		$new_array[$k] = $array[$k];
	}
	
	$array = $new_array;
	return true;
}
?>