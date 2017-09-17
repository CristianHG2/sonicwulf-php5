<?php

class Text
{
	static function validate($string, $type)
	{
		switch ( $type )
		{
			case 'email':
				return filter_var($string, FILTER_VALIDATE_EMAIL);
				break;
			case 'alpha':
				return ctype_alpha($string);
				break;
			case 'alnum':
				return ctype_alnum($string);
				break;
			case 'num':
				return is_numeric($string);
				break;
		}
	}

	static function crypt($text, $salt = null)
	{
		return crypt($text, $salt);
	}

	static function Bind($format, $array, $separator = '{$1}', $recursiveDump = false, $clearUnexistant = false)
	{
		if ( !preg_match_all('/(.*)\$1(.*)/', $separator, $matches) )
		{
			Kesh::Warn('Could not retrieve Bind separators for Text::Bind');
			return false;
		}

		$separators = array($matches[1][0], $matches[2][0]);
		$regex = '/'.preg_quote($separators[0]).'(.*)'.preg_quote($separators[1]).'/U';

		if ( !is_array($array) )
		{
			Kesh::Warn('Second argument for Text::Bind must be an associative array');
			return false;
		}

		if ( !preg_match_all($regex, $format, $matches) )
		{
			Kesh::Warn('Could not execute regular expression on format string for Text::Bind');
			return false;
		}

		foreach ( $matches[1] as $i )
		{
			if ( isset($array[$i]) )
				$format = str_replace($separators[0].$i.$separators[1], $array[$i], $format);
			elseif ( $clearUnexistant )
				$format = str_replace($separators[0].$i.$separators[1], '', $format);
		}

		if ( !$recursiveDump )
			return $format;
		else
			return array($format, $separators, $regex);
	}
}