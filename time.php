<?PHP
	function convert_date($raw_date){
		list($d,$m,$y) = explode("-", $raw_date);
		return sprintf('%4d-%02d-%02d',$y,$m,$d);
	}

	function convert_time($raw_time){
		return DATE("H:i:s", STRTOTIME($raw_time));
	}

	function convert_date_time($date, $time){
		list($d,$m,$y) = explode("-", $date);
		return sprintf('%4d-%02d-%02d',$y,$m,$d) . " " . DATE("H:i:s", STRTOTIME($time));
	}

	echo convert_date_time("12-02-2012", "03:01 PM");
?>