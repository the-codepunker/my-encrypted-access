<?php

	class CliHacker
	{
		/**
		 * receives a string and human readable styles 
		 * and returns a styled text to be displayed in
		 * the terminal...
		 * @param  string $str   [the string to be styled]
		 * @param  string $style [style to be applied]
		 * @return string        [styled string]
		 */
		static function style($str, $style)
    	{
	        $ANSI_CODES = array(
	            "off"        => 0,
	            "bold"       => 1,
	            "red"        => 31,
	            "green"      => 32,
	            "yellow"     => 33,
	        );
	        $color_attrs = explode("+", $style);
	        $ansi_str = "";
	        foreach ($color_attrs as $attr) {
	            $ansi_str .= "\033[" . $ANSI_CODES[$attr] . "m";
	        }
	        $ansi_str .= $str . "\033[" . $ANSI_CODES["off"] . "m";
	        return $ansi_str;
	    }

	    /**
	     * asks for password and get's it from stdin
	     *  - nothing is displayed in CLI -
	     * @return string
	     */
	    static function pass()
	    {
	    	echo "Type your password: " . PHP_EOL;

	    	$oldStyle = exec('stty -g'); //cache old style

	    	shell_exec('stty -echo'); //remove echo
		    	$key = rtrim(fgets(STDIN), "\n");
	    	exec('stty ' . $oldStyle); //put echo back

	    	return $key;
	    }

	}