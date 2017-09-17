<?php

use MatthiasMullie\Minify;

class LexCalls
{
	static function check()
	{
		if ( isset($_SESSION['message']) )
		{
			$c = $_SESSION['message'];
			unset($_SESSION['message']);

			return '<div class="box '.$c[0].'">'.$c[1].'</div>';
		}
	}

	static function pagename()
	{
		global $page;

		if ( strlen($page['name']) > 0 )
			return $page['name'];
		else
			return 'Error';
	}

	static function img($image, $quality = 80)
	{
		$sonic = GlobalConfig('sonicwulf');

		$imgPath = PATH.$sonic->get('resources/img');
		$buildPath = PATH.$sonic->get('resources/build');
		$cachePath = PATH.$sonic->get('resources/dataIMG');
		$buildURL = $sonic->get('resources/buildURL');

		$decode = json_decode(file_get_contents($cachePath), true);

		if ( $decode !== null )
		{
			if ( isset($decode[$image]) )
			{
				$curr = filemtime($imgPath.$image);

				if ( $decode[$image][0] === $curr )
					return $decode[$image][2];
			}
		}
		else
			throw new SonicException('Malformed IMG compression cache', 838);

		$info = getimagesize($imgPath.$image);

		$fname = base64_encode(uniqid().'_'.rand(0, 200)).'__'.stripslashes($image);
		$destination_url = $buildPath.$fname;

		$result = false;

		if ($info['mime'] == 'image/jpeg')
		{
    		$imageRes = imagecreatefromjpeg($imgPath.$image);
			$result = imagejpeg($imageRes, $destination_url, $quality);
		}

		elseif ($info['mime'] == 'image/gif')
		{
    		$imageRes = imagecreatefromgif($imgPath.$image);
			$result = imagegif($imageRes, $destination_url, $quality);
		}

   		elseif ($info['mime'] == 'image/png')
   		{
        	$imageRes = imagecreatefrompng($imgPath.$image);
   			$result = imagepng($imageRes, $destination_url, $quality);
   		}

   		if ( !$result )
   			throw new SonicException('Could not compress image', 837);

   		$decode[$image][0] = filemtime($imgPath.$image);

   		$decode[$image][1] = $destination_url;
   		$decode[$image][2] = $buildURL.$fname;

   		if ( file_put_contents($cachePath, json_encode($decode)) )
			return $decode[$image][2];
		else
			throw new SonicException('Could not write to cache', 839);
	}

	static function linkCSS()
	{
		$sonic = GlobalConfig('sonicwulf');

		$cssPath = PATH.$sonic->get('resources/css');
		$buildPath = PATH.$sonic->get('resources/build');
		$cachePath = PATH.$sonic->get('resources/dataCSS');
		$buildURL = $sonic->get('resources/buildURL');

		$cssTmp = $sonic->get('resources/cssTmp');

		$lastUpdatedArr = Kesh::sfu($cssPath.'*.css');
		$lastUpdated = filemtime($lastUpdatedArr[0]);

		$lastUpdatedCache = file_get_contents($cachePath);
		$lastUpdatedCache = explode('||', $lastUpdatedCache);

		$minifiedPath = $buildPath.$lastUpdated.'__'.str_replace("=", '', base64_encode($lastUpdated)).'.min.css';
		$minifiedURL = $buildURL.$lastUpdated.'__'.str_replace("=", '', base64_encode($lastUpdated)).'.min.css';	

		if ( $lastUpdated > intval($lastUpdatedCache[0]) || !file_exists($minifiedPath) || count($lastUpdatedArr) !== intval($lastUpdatedCache[1]) )
		{
			$args = func_get_args();
			$minifier = new Minify\CSS;

			foreach ( $args as $i )
			{
				$path = $cssPath.$i;

				if ( file_exists($path) )
					$minifier->add($path);
				else
					throw new SonicException('No such file "'.$path.'"', 837);			
			}

			if ( $minifier->minify($minifiedPath) )
			{
				$d = glob($buildPath.'*.css');

				foreach ( $d as $i )
				{
					if ( $i !== $minifiedPath )
						unlink($i);
				}

				file_put_contents($cachePath, $lastUpdated.'||'.count($lastUpdatedArr));
				return Text::Bind($cssTmp, array("path" => $minifiedURL.'?'.$lastUpdated));
			}
			else
				return false;
		}
		else
			return Text::Bind($cssTmp, array("path" => $minifiedURL.'?'.$lastUpdated));
	}

	static function printSiteName()
	{
		return GlobalConfig('sonicwulf')->get('site/site_name');
	}

	static function linkJS()
	{
		$sonic = GlobalConfig('sonicwulf');

		$cssPath = PATH.$sonic->get('resources/js');
		$buildPath = PATH.$sonic->get('resources/build');
		$cachePath = PATH.$sonic->get('resources/dataJS');
		$buildURL = $sonic->get('resources/buildURL');

		$cssTmp = $sonic->get('resources/jsTmp');

		$lastUpdatedArr = Kesh::sfu($cssPath.'*.js');
		$lastUpdated = filemtime($lastUpdatedArr[0]);

		$lastUpdatedCache = file_get_contents($cachePath);
		$lastUpdatedCache = explode('||', $lastUpdatedCache);

		$minifiedPath = $buildPath.$lastUpdated.'__'.str_replace("=", '', base64_encode($lastUpdated)).'.min.js';
		$minifiedURL = $buildURL.$lastUpdated.'__'.str_replace("=", '', base64_encode($lastUpdated)).'.min.js';	

		if ( $lastUpdated > intval($lastUpdatedCache[0]) || !file_exists($minifiedPath) || count($lastUpdatedArr) !== intval($lastUpdatedCache[1]) )
		{
			$args = func_get_args();
			$minifier = new Minify\JS;

			foreach ( $args as $i )
			{
				$path = $cssPath.$i;

				if ( file_exists($path) )
					$minifier->add($path);				
			}

			if ( $minifier->minify($minifiedPath) )
			{
				$d = glob($buildPath.'*.js');

				foreach ( $d as $i )
				{
					if ( $i !== $minifiedPath )
						unlink($i);
				}

				file_put_contents($cachePath, $lastUpdated.'||'.count($lastUpdatedArr));
				return Text::Bind($cssTmp, array("path" => $minifiedURL.'?'.$lastUpdated));
			}
			else
				return false;
		}
		else
			return Text::Bind($cssTmp, array("path" => $minifiedURL.'?'.$lastUpdated));
	}
}